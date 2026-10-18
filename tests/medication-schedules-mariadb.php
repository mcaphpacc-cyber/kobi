<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/medication-schedules.php';
define('SSC_RECOVERY_LIBRARY',true);require __DIR__.'/saved-symptom-checks-recovery.php';
const MS_TEST_DB='kobi_medication_schedule_recovery_20261014';
$db=new App\Core\Database();$pdo=$db->connection();
sscAssert($pdo->query('SELECT DATABASE()')->fetchColumn()==='medical1','Unexpected source.');
$proof=json_decode(file_get_contents(__DIR__.'/.medication-schedules-recovery/verified.json'),true,512,JSON_THROW_ON_ERROR);
sscAssert($proof['status']==='RECOVERY_VERIFIED'&&$proof['recovery_database']===MS_TEST_DB,'Missing recovery certificate.');
$pdo->exec('USE '.sscIdent(MS_TEST_DB));sscAssert($pdo->query('SELECT DATABASE()')->fetchColumn()===MS_TEST_DB,'Isolation guard failed.');
$auth=new ScheduleTestAuth();$profileService=new App\Services\HealthProfileService(new App\Repositories\HealthProfileRepository($db),$auth);
$medicineRepo=new App\Repositories\HealthMedicineRepository($db);
$medicineService=new App\Services\HealthMedicineService($medicineRepo,$profileService);
$repo=new App\Repositories\MedicationScheduleRepository($db);
$service=new App\Services\MedicationScheduleService($repo,$medicineService,$profileService,$auth,new App\Services\ApiHealthProfileService(new App\Repositories\HealthProfileRepository($db)));
if(($argv[1]??'')==='worker'){
    echo "READY\n";flush();
    try{$service->mark((int)$argv[2],(int)$argv[3],(int)$argv[4],gmdate('Y-m-d'),$argv[5]);echo "MARKED\n";}
    catch(RuntimeException $e){if($e->getMessage()==='This occurrence is no longer available for today. Reload Due Today.'){echo "DENIED\n";}else{throw $e;}}
    exit;
}
$start=$scheduleChecks;$mode=$pdo->query('SELECT @@sql_mode,@@global.sql_mode')->fetch();$profileId=null;
$spawn=function(int $profile,int $medicine,int $slot,string $state): array {
    $process=proc_open([PHP_BINARY,__FILE__,'worker',(string)$profile,(string)$medicine,(string)$slot,$state],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    msCheck(is_resource($process),'worker starts');return [$process,$pipes];
};
$finish=function(array $worker,string $expected): void {
    [$process,$pipes]=$worker;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    msCheck(proc_close($process)===0 && $err==='' && str_contains($out,$expected),'concurrent worker result');
};
try{
    $pdo->exec("INSERT INTO health_profiles (profile_type,full_name,created_by) VALUES ('family','Step45B isolated synthetic profile',1)");$profileId=(int)$pdo->lastInsertId();
    $stmt=$pdo->prepare("INSERT INTO health_profile_members (health_profile_id,user_id,role) VALUES (?,1,'owner')");$stmt->execute([$profileId]);
    $medicine=$medicineService->createMedicine($profileId,['brand_name'=>'Step45B isolated medicine']);
    $input=['local_time'=>'08:00','timezone'=>'UTC','starts_on'=>gmdate('Y-m-d'),'ends_on'=>'','is_active'=>'1'];
    msCheck($service->dueToday($profileId)===[],'unscheduled');
    $slot=$service->save($profileId,$medicine,$input);$slot2=$service->save($profileId,$medicine,array_replace($input,['local_time'=>'20:00']));
    msCheck(count($service->dueToday($profileId))===2,'two slots roundtrip');
    $service->mark($profileId,$medicine,$slot,gmdate('Y-m-d'),'taken');$service->mark($profileId,$medicine,$slot,gmdate('Y-m-d'),'skipped');
    msCheck($repo->checkoff($slot,gmdate('Y-m-d'))['state']==='skipped' && (int)$pdo->query('SELECT COUNT(*) FROM health_medicine_schedule_checkoffs')->fetchColumn()===1,'one checkoff correction');
    $a=$spawn($profileId,$medicine,$slot2,'taken');$b=$spawn($profileId,$medicine,$slot2,'skipped');$finish($a,'MARKED');$finish($b,'MARKED');
    msCheck((int)$pdo->query('SELECT COUNT(*) FROM health_medicine_schedule_checkoffs')->fetchColumn()===2,'concurrent marks remain unique');
    $replacement=$service->save($profileId,$medicine,array_replace($input,['local_time'=>'09:00']),$slot);
    $history=$service->history($profileId,1)['rows'];msCheck(count($history)===2 && in_array('08:00',array_column($history,'local_time'),true),'retired slot historical time');
    $auth->uid=2;
    foreach([fn()=>$service->slots($profileId,$medicine),fn()=>$service->save($profileId,$medicine,$input),fn()=>$service->save($profileId,$medicine,$input,$replacement),fn()=>$service->remove($profileId,$medicine,$replacement),fn()=>$service->history($profileId,1),fn()=>$service->mark($profileId,$medicine,$replacement,gmdate('Y-m-d'),'taken')] as $action){msDenied($action);}
    $auth->uid=1;
    $updateMember=$pdo->prepare("UPDATE health_profile_members SET role=? WHERE health_profile_id=? AND user_id=1");$updateMember->execute(['viewer',$profileId]);
    msCheck(count($service->dueToday($profileId))===2,'viewer read');msDenied(fn()=>$service->remove($profileId,$medicine,$replacement));
    $updateMember->execute(['editor',$profileId]);$service->mark($profileId,$medicine,$replacement,gmdate('Y-m-d'),'taken');msCheck(true,'editor mark');
    $updateMember->execute(['owner',$profileId]);
    foreach([['local_time'=>'24:00'],['timezone'=>''],['is_active'=>2],['ends_on'=>'1000-01-01'],['health_medicine_id'=>2147483647]] as $bad){
        $row=['health_medicine_id'=>$medicine,'local_time'=>'12:00','timezone'=>'UTC','starts_on'=>gmdate('Y-m-d'),'ends_on'=>null,'is_active'=>1,'created_by'=>1,'created_at'=>gmdate('Y-m-d H:i:s'),'updated_at'=>gmdate('Y-m-d H:i:s')];
        try{$repo->create(array_replace($row,$bad));throw new LogicException('MariaDB accepted invalid schedule.');}catch(PDOException $e){msCheck(true,'CHECK/FK reject');}
    }
    foreach(['TAKEN','invalid',''] as $state){
        try{$repo->mark(['schedule_id'=>$replacement,'scheduled_on'=>gmdate('Y-m-d'),'state'=>$state,'medicine_label'=>'invalid','recorded_by'=>1,'recorded_at'=>gmdate('Y-m-d H:i:s'),'created_at'=>gmdate('Y-m-d H:i:s'),'updated_at'=>gmdate('Y-m-d H:i:s')]);throw new LogicException('Invalid state accepted.');}catch(PDOException $e){msCheck(true,'binary state CHECK');}
    }
    // Hold the medicine lock; a real service request must re-evaluate after stop commits.
    $pdo->beginTransaction();$pdo->query('SELECT id FROM health_medicines WHERE id='.$medicine.' FOR UPDATE')->fetch();
    $worker=$spawn($profileId,$medicine,$replacement,'taken');
    do{$line=fgets($worker[1][1]);}while($line!==false && trim($line)!=='READY');
    msCheck($line!==false,'worker reaches mark boundary');
    $pdo->exec("UPDATE health_medicines SET status='stopped',stopped_on=CURRENT_DATE WHERE id=".$medicine);
    $pdo->exec('UPDATE health_medicine_schedule_slots SET is_active=0 WHERE health_medicine_id='.$medicine);$pdo->commit();
    $finish($worker,'DENIED');msCheck($service->dueToday($profileId)===[],'stop excludes all');
    $medicineService->updateMedicine($profileId,$medicine,['brand_name'=>'Step45B isolated medicine','status'=>'current']);
    msCheck($service->dueToday($profileId)===[],'reactivation does not restore');
    $newSlot=$service->save($profileId,$medicine,$input);
    $medicineService->updateMedicine($profileId,$medicine,['brand_name'=>'Step45B isolated medicine','status'=>'historical']);
    msCheck((int)$repo->slot($medicine,$newSlot)['is_active']===0,'real medication repository deactivates slots');
    msCheck(count($service->history($profileId,1)['rows'])===3,'history retained after stop');
    $medicineService->deleteMedicine($profileId,$medicine);
    msCheck($repo->slots($medicine)===[] && $service->history($profileId,1)['rows']===[],'existing delete cascades schedules/history');
    msCheck($mode===$pdo->query('SELECT @@sql_mode,@@global.sql_mode')->fetch(),'SQL mode unchanged');
}finally{
    if($pdo->inTransaction()){$pdo->rollBack();}
    if($profileId!==null){$stmt=$pdo->prepare('DELETE FROM health_profiles WHERE id=? AND full_name=?');$stmt->execute([$profileId,'Step45B isolated synthetic profile']);}
}
msCheck((int)$pdo->query('SELECT COUNT(*) FROM health_medicine_schedule_slots')->fetchColumn()===0 && (int)$pdo->query('SELECT COUNT(*) FROM health_medicine_schedule_checkoffs')->fetchColumn()===0,'isolated fixtures cleaned');
file_put_contents(__DIR__.'/.medication-schedules-recovery/mariadb-passed.json',json_encode(['status'=>'PASS','checks'=>$scheduleChecks-$start,'artifact_sha256'=>hash_file('sha256',dirname(__DIR__).'/database/medication-schedules.sql')],JSON_PRETTY_PRINT));
echo 'PASS: '.($scheduleChecks-$start)." isolated MariaDB checks, including real concurrent connections; fixtures removed.\n";

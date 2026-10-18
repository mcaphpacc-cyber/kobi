<?php
declare(strict_types=1);
// Shared cases for the guarded disposable schedule HTTPS harness; also its concurrent worker.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$checkoffWorker=realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__;
if($checkoffWorker)require __DIR__.'/api-auth-fixture.php';

class CheckoffDbClock extends App\Services\MedicationScheduleService {
    public ?string $instant=null;
    protected function now():DateTimeImmutable {return $this->instant===null?parent::now():new DateTimeImmutable($this->instant);}
}
function checkoffFixtureApi(App\Core\Database $db,?string $instant=null,?App\Repositories\MedicationScheduleRepository $repository=null):array {
    if($db->connection()->query('SELECT DATABASE()')->fetchColumn()!==API_TEST_DATABASE)throw new LogicException('Disposable database required');
    $profiles=new App\Services\ApiHealthProfileService(new App\Repositories\HealthProfileRepository($db));
    // Any accidental browser-auth dependency fails rather than borrowing a fixture browser user.
    $auth=(new ReflectionClass(App\Services\AuthService::class))->newInstanceWithoutConstructor();
    $browserProfiles=new App\Services\HealthProfileService(new App\Repositories\HealthProfileRepository($db),$auth);
    $repo=$repository??new App\Repositories\MedicationScheduleRepository($db);
    $clock=new CheckoffDbClock($repo,new App\Services\HealthMedicineService(new App\Repositories\HealthMedicineRepository($db),$browserProfiles),$browserProfiles,$auth,$profiles);
    $clock->instant=$instant;
    return [new App\Services\ApiMedicationScheduleService($profiles,$repo,$clock),$clock];
}
class CheckoffRollbackRepository extends App\Repositories\MedicationScheduleRepository {
    public bool $failCommit=false;
    public function mark(array $data):void {parent::mark($data);if(!$this->failCommit)throw new RuntimeException('Synthetic failure after upsert');}
    protected function commit():bool {return false;}
}
class CheckoffWorkerRepository extends App\Repositories\MedicationScheduleRepository {
    public function withMedicine(int $profile,int $medicine,int $user,callable $action):mixed {
        // Barrier after initial profile/slot resolution, immediately before transactional admission.
        echo "LOCKING\n";flush();return parent::withMedicine($profile,$medicine,$user,$action);
    }
}
function checkoffRawHttp(string $path,?string $token,int $expected,string $raw,string $type='application/json',string $method='POST'):array {
    global $pem;
    $headers=['Connection: close','Content-Type: '.$type];if($token!==null)$headers[]='Authorization: Bearer '.$token;
    $c=curl_init('https://localhost:18866/api/v1/'.$path);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_POSTFIELDS=>$raw,
        CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_CAINFO=>$pem,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>10]);
    $response=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);$n=curl_getinfo($c,CURLINFO_HEADER_SIZE);
    ih(is_string($response)&&curl_errno($c)===0&&curl_getinfo($c,CURLINFO_SSL_VERIFYRESULT)===0,'Raw request verified TLS');curl_close($c);
    ih($status===$expected,'Raw request status expected '.$expected.' observed '.$status);
    $head=substr($response,0,$n);$text=substr($response,$n);$json=json_decode($text,true,16,JSON_THROW_ON_ERROR);
    ih($json['success']===false&&$json['meta']===['api_version'=>'v1'],'Safe error envelope');
    ih(str_contains(strtolower($head),'cache-control: no-store')&&!preg_match('/^(?:Set-Cookie|Access-Control-Allow-Origin):/im',$head),'No cache/cookie/CORS');
    ih(!preg_match('/SQLSTATE|Stack trace|PRIVATE|recorded_by|storage_path/',$text),'No private errors');
    if($expected===405)ih(preg_match('/^Allow: POST\s*$/im',$head)===1,'New route allows only POST');
    return $json;
}
function medicationCheckoffHttpCases(PDO $p,App\Core\Database $db,array $a,array $b,array $device,string $rateKey,string $storage):void {
    global $checks;
    $start=$checks;$mode=$p->query('SELECT @@sql_mode AS session_mode,@@global.sql_mode AS global_mode')->fetch();
    $tables=['users','health_profiles','health_profile_members','health_medicines','health_medicine_schedule_slots','health_conditions','health_allergies','health_procedures'];
    $before=[];foreach($tables as $t)$before[$t]=$p->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll();
    $existing=$p->query('SELECT * FROM health_medicine_schedule_checkoffs WHERE schedule_id IN (1,2) ORDER BY id')->fetchAll();
    $ddl=[];foreach(['health_medicine_schedule_slots','health_medicine_schedule_checkoffs'] as $t)$ddl[$t]=$p->query('SHOW CREATE TABLE '.$t)->fetch();
    $path='health-profiles/1/medication-schedules/60/checkoff';
    foreach([null,'malformed','ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=')] as $token)identityHttp($path,$token,401,'POST',['state'=>'taken'],['Cookie: PHPSESSID=not-an-api-identity']);
    foreach([[],['state'=>null],['state'=>'TAKEN'],['state'=>'missed'],['state'=>'partial'],['state'=>'pending'],['state'=>' taken'],['state'=>'taken','scheduled_on'=>gmdate('Y-m-d')],['state'=>'taken','timezone'=>'UTC'],['state'=>'taken','user_id'=>'100002'],['state'=>'taken','health_medicine_id'=>'5']] as $body)identityHttp($path,$a['access_token'],422,'POST',$body);
    foreach(['{','[]','{"state":true}','{"state":1}','{"state":[]}','{"state":"taken","state":"skipped"}','{"state":"taken","st\\u0061te":"skipped"}'] as $raw)checkoffRawHttp($path,$a['access_token'],400,$raw);
    checkoffRawHttp($path,$a['access_token'],415,'state=taken','application/x-www-form-urlencoded');
    checkoffRawHttp($path,$a['access_token'],413,str_repeat(' ',8193));
    checkoffRawHttp($path,$a['access_token'],405,'','application/json','GET');
    identityHttp($path.'?state=taken',$a['access_token'],400,'POST',['state'=>'taken']);
    foreach(['0','01','2147483648','-1'] as $id){identityHttp('health-profiles/1/medication-schedules/'.$id.'/checkoff',$a['access_token'],422,'POST',['state'=>'taken']);identityHttp('health-profiles/'.$id.'/medication-schedules/60/checkoff',$a['access_token'],422,'POST',['state'=>'taken']);}
    foreach([['health-profiles/2/medication-schedules/62/checkoff',$a,404],['health-profiles/1/medication-schedules/62/checkoff',$a,404],['health-profiles/1/medication-schedules/60/checkoff',$b,404],['health-profiles/3/medication-schedules/63/checkoff',$a,403],['health-profiles/4/medication-schedules/65/checkoff',$a,404],['health-profiles/5/medication-schedules/66/checkoff',$a,404],['health-profiles/1/medication-schedules/2147483647/checkoff',$a,404]] as [$route,$issued,$status])identityHttp($route,$issued['access_token'],$status,'POST',['state'=>'taken']);
    ih($p->query('SELECT COUNT(*) FROM health_medicine_schedule_checkoffs WHERE schedule_id=60')->fetchColumn()===0,'All denied requests leave occurrence absent');
    $row=null;
    foreach(['taken','taken','skipped','skipped','taken','skipped'] as $state){
        $data=identityHttp($path,$a['access_token'],200,'POST',['state'=>$state]);
        ih($data===['id'=>'60','health_medicine_id'=>'2','local_time'=>'23:59','timezone'=>'UTC','scheduled_on'=>gmdate('Y-m-d'),'state'=>$state],'Exact committed Due Today projection and string IDs');
        $rows=$p->query('SELECT * FROM health_medicine_schedule_checkoffs WHERE schedule_id=60')->fetchAll();
        ih(count($rows)===1&&$rows[0]['state']===$state,'Real unique occurrence upsert');
        if($row!==null)ih($rows[0]['id']===$row['id']&&$rows[0]['created_at']===$row['created_at']&&$rows[0]['medicine_label']===$row['medicine_label'],'Correction preserves identity/original label/created time');
        $row=$rows[0];ih((int)$row['recorded_by']===100001&&!$p->inTransaction(),'Bearer actor and committed transaction');
    }
    identityHttp('health-profiles/7/medication-schedules/64/checkoff',$a['access_token'],200,'POST',['state'=>'skipped']);
    ih($p->query('SELECT state FROM health_medicine_schedule_checkoffs WHERE schedule_id=64')->fetchColumn()==='skipped','Editor unset to skipped');
    foreach([55,56,57,58,59,61] as $slot)identityHttp('health-profiles/1/medication-schedules/'.$slot.'/checkoff',$a['access_token'],409,'POST',['state'=>'taken']);

    // Deterministic real MariaDB domain checks supplement live HTTP's actual clock.
    [$api,$clock]=checkoffFixtureApi($db,'2026-10-17T00:30:00Z');$actor=new App\Services\ApiAuthPrincipal(100001,1);
    foreach([1001=>'2026-10-17',1002=>'2026-10-16',1003=>'2026-10-17'] as $slot=>$day)ih($api->checkoff($actor,'8',(string)$slot,['state'=>'taken'])['scheduled_on']===$day,'Server local date/inclusive endpoints/late time against MariaDB');
    foreach([1004,1005,1006,1007,1008,1009] as $slot){try{$api->checkoff($actor,'8',(string)$slot,['state'=>'taken']);throw new LogicException('Ineligible accepted');}catch(App\Services\ApiAuthException $e){ih($e->status===409,'Real eligibility denial');}}
    $clock->instant='2026-10-17T04:00:00Z';try{$api->checkoff($actor,'8','1002',['state'=>'skipped']);throw new LogicException('Expired local day accepted');}catch(App\Services\ApiAuthException $e){ih($e->status===409,'New York local midnight ends occurrence');}
    $clock->instant='2026-10-18T00:00:00Z';try{$api->checkoff($actor,'8','1001',['state'=>'skipped']);throw new LogicException('Expired day accepted');}catch(App\Services\ApiAuthException $e){ih($e->status===409,'UTC local midnight ends occurrence');}
    // Actual upsert followed by failure and a false commit must both roll back.
    $snapshot=$p->query('SELECT * FROM health_medicine_schedule_checkoffs ORDER BY id')->fetchAll();
    $failing=new CheckoffRollbackRepository($db);[$failApi]=checkoffFixtureApi($db,'2026-10-17T00:30:00Z',$failing);
    foreach([false,true] as $commit){$failing->failCommit=$commit;try{$failApi->checkoff($actor,'8','1001',['state'=>'skipped']);throw new LogicException('Failure accepted');}catch(RuntimeException $e){ih($e->getMessage()===($commit?'Unable to commit medication schedule.':'Synthetic failure after upsert'),'Failure propagated without success');}ih(!$p->inTransaction()&&$snapshot===$p->query('SELECT * FROM health_medicine_schedule_checkoffs ORDER BY id')->fetchAll(),'Real transaction rollback, no partial mutation');}

    $spawn=function(string $slot,string $state):array {
        $process=proc_open([PHP_BINARY,__FILE__,'--worker','1',$slot,$state],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));
        ih(is_resource($process),'Concurrent process starts');ih(trim((string)fgets($pipes[1]))==='READY','Worker ready');fwrite($pipes[0],"GO\n");fclose($pipes[0]);
        ih(trim((string)fgets($pipes[1]))==='LOCKING','Worker passed initial authorization and reached transaction');return [$process,$pipes];
    };
    $finish=function(array $worker,int $status):void {
        [$process,$pipes]=$worker;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        ih(proc_close($process)===0&&$err===''&&trim($out)===(string)$status,'Concurrent committed/denied result');
    };
    $one=$spawn('54','taken');$two=$spawn('54','skipped');$finish($one,200);$finish($two,200);
    ih((int)$p->query('SELECT COUNT(*) FROM health_medicine_schedule_checkoffs WHERE schedule_id=54')->fetchColumn()===1,'Real concurrent connections retain unique occurrence');
    $p->beginTransaction();$p->query('SELECT id FROM health_medicines WHERE id=2 FOR UPDATE')->fetch();
    $worker=$spawn('60','taken');
    $p->exec("UPDATE health_medicines SET status='stopped' WHERE id=2");$p->exec('UPDATE health_medicine_schedule_slots SET is_active=0 WHERE health_medicine_id=2');$p->commit();$finish($worker,409);
    ih($p->query('SELECT state FROM health_medicine_schedule_checkoffs WHERE schedule_id=60')->fetchColumn()==='skipped','Stop serializes with mark and preserves prior checkoff');
    // Restore only synthetic fixture parents so the unrelated-state comparison remains exact.
    $p->exec("UPDATE health_medicines SET status='current',updated_at='".$before['health_medicines'][1]['updated_at']."' WHERE id=2");
    $restore=$p->prepare('UPDATE health_medicine_schedule_slots SET is_active=?,updated_at=? WHERE id=?');
    foreach($before['health_medicine_schedule_slots'] as $s)if((int)$s['health_medicine_id']===2)$restore->execute([$s['is_active'],$s['updated_at'],$s['id']]);
    $member=array_values(array_filter($before['health_profile_members'],fn($r)=>(int)$r['health_profile_id']===1&&(int)$r['user_id']===100001))[0];
    $p->beginTransaction();$p->query('SELECT id FROM health_profile_members WHERE id='.(int)$member['id'].' FOR UPDATE')->fetch();
    $worker=$spawn('60','taken');
    $p->exec("UPDATE health_profile_members SET status='revoked' WHERE id=".(int)$member['id']);$p->commit();$finish($worker,403);
    $restoreMember=$p->prepare('UPDATE health_profile_members SET status=?,updated_at=? WHERE id=?');$restoreMember->execute([$member['status'],$member['updated_at'],$member['id']]);
    ih($p->query('SELECT state FROM health_medicine_schedule_checkoffs WHERE schedule_id=60')->fetchColumn()==='skipped','Membership revocation is rechecked under the mutation lock');
    // Direct SQL integrity backstops remain effective in the existing non-strict mode.
    $constraintSnapshot=$p->query('SELECT * FROM health_medicine_schedule_checkoffs ORDER BY id')->fetchAll();
    foreach(['TAKEN','missed',''] as $bad){
        try{$repoForCheck=new App\Repositories\MedicationScheduleRepository($db);$repoForCheck->mark(['schedule_id'=>60,'scheduled_on'=>gmdate('Y-m-d'),'state'=>$bad,'medicine_label'=>'Synthetic','recorded_by'=>100001,'recorded_at'=>gmdate('Y-m-d H:i:s'),'created_at'=>gmdate('Y-m-d H:i:s'),'updated_at'=>gmdate('Y-m-d H:i:s')]);throw new LogicException('Invalid state accepted by database');}
        catch(PDOException $e){ih((int)$e->errorInfo[1]===4025,'Existing binary state CHECK rejects invalid state');}
    }
    ih($constraintSnapshot===$p->query('SELECT * FROM health_medicine_schedule_checkoffs ORDER BY id')->fetchAll(),'Rejected SQL states leave checkoffs unchanged');
    $p->exec("UPDATE health_medicine_schedule_slots SET timezone='Mars/Zone',updated_at=updated_at WHERE id=60");
    identityHttp($path,$a['access_token'],500,'POST',['state'=>'taken']);
    $p->exec("UPDATE health_medicine_schedule_slots SET timezone='UTC',updated_at=updated_at WHERE id=60");
    ih($constraintSnapshot===$p->query('SELECT * FROM health_medicine_schedule_checkoffs ORDER BY id')->fetchAll(),'Corrupt stored timezone fails safely before any write');
    // Reread from a separate browser service, retaining normal browser authorization and history.
    $auth=new App\Services\AuthService(new App\Repositories\UserRepository($db),new ApiFixtureSession(),new App\Services\UserAuthTokenService(new App\Repositories\UserAuthTokenRepository($db)),$db);
    $browserProfiles=new App\Services\HealthProfileService(new App\Repositories\HealthProfileRepository($db),$auth);$repo=new App\Repositories\MedicationScheduleRepository($db);
    $browser=new App\Services\MedicationScheduleService($repo,new App\Services\HealthMedicineService(new App\Repositories\HealthMedicineRepository($db),$browserProfiles),$browserProfiles,$auth,new App\Services\ApiHealthProfileService(new App\Repositories\HealthProfileRepository($db)));
    $due=array_column($browser->dueToday(1),'state','id');ih($due[60]==='skipped','Browser sees API correction');
    $history=array_column($browser->history(1,1)['rows'],'state','schedule_id');ih($history[60]==='skipped','Browser history sees API state');
    // Existing limiter controls this mutating route without exempting it as logout.
    $sid=$p->prepare('SELECT id FROM user_api_sessions WHERE public_id=?');$sid->execute([$a['session_id']]);$bucket=hash_hmac('sha256',"access_family\0".$sid->fetchColumn(),$rateKey);
    $file=$storage.'/cache/api-auth/counters.json';$counters=json_decode(file_get_contents($file),true);$count=$counters[$bucket]['count'];
    identityHttp($path,$a['access_token'],200,'POST',['state'=>'skipped']);$counters=json_decode(file_get_contents($file),true);ih($counters[$bucket]['count']===$count+1,'One ordinary limiter admission per write');
    $counters[$bucket]['count']=120;file_put_contents($file,json_encode($counters));$before429=$p->query('SELECT * FROM health_medicine_schedule_checkoffs ORDER BY id')->fetchAll();identityHttp($path,$a['access_token'],429,'POST',['state'=>'taken']);ih($before429===$p->query('SELECT * FROM health_medicine_schedule_checkoffs ORDER BY id')->fetchAll(),'Rate-limited write has no effects');$counters[$bucket]['count']=0;file_put_contents($file,json_encode($counters));
    foreach($tables as $t)ih($before[$t]===$p->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(),'Unrelated fixture data unchanged: '.$t);
    ih($existing===$p->query('SELECT * FROM health_medicine_schedule_checkoffs WHERE schedule_id IN (1,2) ORDER BY id')->fetchAll(),'Existing unrelated checkoffs unchanged');
    foreach($ddl as $t=>$definition){
        $after=$p->query('SHOW CREATE TABLE '.$t)->fetch();
        // Inserts/upserts advance the table counter, not its schema/constraints.
        $definition['Create Table']=preg_replace('/ AUTO_INCREMENT=[0-9]+/','',$definition['Create Table']);
        $after['Create Table']=preg_replace('/ AUTO_INCREMENT=[0-9]+/','',$after['Create Table']);
        ih($definition===$after,'Schema constraints unchanged (apart from runtime auto-increment counter): '.$t);
    }
    ih($mode===$p->query('SELECT @@sql_mode AS session_mode,@@global.sql_mode AS global_mode')->fetch(),'SQL mode unchanged');
    echo 'Medication Checkoff isolated MariaDB/verified HTTPS: '.($checks-$start)." checks passed, including real concurrent connections.\n";
}

if($checkoffWorker){
    if(($argv[1]??'')!=='--worker'||count($argv)!==5)throw new LogicException('Guarded worker arguments required');
    $db=apiFixtureConnect();[$api]=checkoffFixtureApi($db,null,new CheckoffWorkerRepository($db));echo "READY\n";flush();if(trim((string)fgets(STDIN))!=='GO')exit(1);
    try{$api->checkoff(new App\Services\ApiAuthPrincipal(100001,1),$argv[2],$argv[3],['state'=>$argv[4]]);echo "200\n";}
    catch(App\Services\ApiAuthException $e){echo $e->status."\n";}
}

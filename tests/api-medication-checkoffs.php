<?php
declare(strict_types=1);
// Database-free: reuse the unchanged browser domain/clock fixtures.
require __DIR__.'/medication-schedules.php';

class CheckoffProfiles extends App\Repositories\HealthProfileRepository {
    public array $roles=[1=>'owner',2=>'editor',3=>'viewer'];
    public function __construct() {}
    public function findAccessible(int $id,int $user):?array {
        return in_array($id,[10,11],true)&&isset($this->roles[$user])
            ? ['id'=>$id,'full_name'=>'Synthetic','profile_type'=>'family','role'=>$this->roles[$user]] : null;
    }
}
class CheckoffRows extends App\Repositories\MedicationScheduleRepository {
    public array $records=[], $marks=[], $medicines=[], $reads=[];
    public bool $committed=false, $failCommit=false, $revokeOnLock=false;
    public function __construct(public CheckoffProfiles $profiles) {}
    public function slotInProfile(int $profile,int $id):?array {
        $this->reads[]=[$profile,$id];$row=$this->records[$id]??null;
        return $row&&$this->medicines[$row['health_medicine_id']]['health_profile_id']===$profile?$row:null;
    }
    public function slot(int $medicine,int $id):?array {
        $row=$this->records[$id]??null;return $row&&$row['health_medicine_id']===$medicine?$row:null;
    }
    public function withMedicine(int $profile,int $medicine,int $user,callable $action):mixed {
        $before=$this->marks;$this->committed=false;
        try {
            if($this->revokeOnLock||!in_array($this->profiles->findAccessible($profile,$user)['role']??null,['owner','editor'],true))
                throw new RuntimeException('You do not have permission to modify this health record.');
            $result=$action($this->medicines[$medicine]);
            if($this->failCommit)throw new RuntimeException('Synthetic commit failure');
            $this->committed=true;return $result;
        } catch(Throwable $e) {$this->marks=$before;throw $e;}
    }
    public function mark(array $data):void {
        $key=$data['schedule_id'].':'.$data['scheduled_on'];$old=$this->marks[$key]??null;
        if($old)$data=array_replace($data,['medicine_label'=>$old['medicine_label'],'created_at'=>$old['created_at']]);
        $this->marks[$key]=$data;
    }
    public function profileSlots(int $profile):array {
        $rows=[];foreach($this->records as $row){$m=$this->medicines[$row['health_medicine_id']];if($m['health_profile_id']===$profile)$rows[]=$row+$m;}return $rows;
    }
    public function checkoff(int $slot,string $day):?array {return $this->marks[$slot.':'.$day]??null;}
}
$checks=0;
function ck(bool $ok,string $label):void {global $checks;if(!$ok)throw new LogicException($label);++$checks;}
function ckDenied(callable $action,int $status):void {try{$action();throw new LogicException('Expected denial');}catch(App\Services\ApiAuthException $e){ck($e->status===$status,'Expected safe status '.$status);}}
$profiles=new CheckoffProfiles();$apiProfiles=new App\Services\ApiHealthProfileService($profiles);$rows=new CheckoffRows($profiles);
$auth=new ScheduleTestAuth();$auth->uid=null;$browserProfiles=new ScheduleTestProfiles($auth);
$domain=new ScheduleClockService($rows,new ScheduleTestMedicines($browserProfiles),$browserProfiles,$auth,$apiProfiles);
$api=new App\Services\ApiMedicationScheduleService($apiProfiles,$rows,$domain);$actor=new App\Services\ApiAuthPrincipal(1,1);
$rows->medicines=[20=>['id'=>20,'health_profile_id'=>10,'brand_name'=>'PRIVATE initial label','status'=>'current','started_on'=>null],21=>['id'=>21,'health_profile_id'=>11,'brand_name'=>'Other','status'=>'current','started_on'=>null]];
$base=['id'=>1,'health_medicine_id'=>20,'local_time'=>'23:59','timezone'=>'UTC','starts_on'=>'2026-10-14','ends_on'=>'2026-10-14','is_active'=>1];
$rows->records=[1=>$base,2=>array_replace($base,['id'=>2]),3=>array_replace($base,['id'=>3,'health_medicine_id'=>21])];
$unchanged=$rows->medicines;$untouched=$rows->records;
foreach([['taken','taken'],['taken','taken'],['skipped','skipped'],['skipped','skipped'],['taken','taken'],['skipped','skipped']] as [$request,$expected]){
    $result=$api->checkoff($actor,'10','1',['state'=>$request]);
    ck($rows->committed,'Response after committed operation');
    ck($result===['id'=>'1','health_medicine_id'=>'20','local_time'=>'23:59','timezone'=>'UTC','scheduled_on'=>'2026-10-14','state'=>$expected],'Exact six-field projection and later-today eligibility');
    ck(count($rows->marks)===1&&$rows->marks['1:2026-10-14']['state']===$expected,'Unique deterministic state');
}
$api->checkoff(new App\Services\ApiAuthPrincipal(2,2),'10','2',['state'=>'skipped']);ck(count($rows->marks)===2,'Editor unset to skipped');
ck($rows->marks['2:2026-10-14']['recorded_by']===2,'Bearer actor only, no browser session');
$original=$rows->marks['1:2026-10-14'];$rows->medicines[20]['brand_name']='Renamed';$domain->instant='2026-10-14T13:00:00Z';
$api->checkoff($actor,'10','1',['state'=>'taken']);
ck($rows->marks['1:2026-10-14']['medicine_label']===$original['medicine_label']&&$rows->marks['1:2026-10-14']['created_at']===$original['created_at'],'Initial label and created time retained');
ck($rows->marks['1:2026-10-14']['recorded_at']==='2026-10-14 13:00:00','Correction records server time');
$rows->medicines=$unchanged;$snapshot=$rows->marks;
foreach([[],['state'=>null],['state'=>true],['state'=>[]],['state'=>'TAKEN'],['state'=>'partial'],['state'=>''],['state'=>' taken'],['state'=>'skipped','scheduled_on'=>'2026-10-14'],['state'=>'taken','actor'=>9]] as $input)ckDenied(fn()=>$api->checkoff($actor,'10','1',$input),422);
foreach(['0','01','-1','+1','1.0','1e1','2147483648',''] as $id)ckDenied(fn()=>$api->checkoff($actor,'10',$id,['state'=>'taken']),422);
ckDenied(fn()=>$api->checkoff($actor,'999','1',['state'=>'taken']),404);
ckDenied(fn()=>$api->checkoff(new App\Services\ApiAuthPrincipal(9,9),'10','1',['state'=>'taken']),404);
foreach(['3','999'] as $id)ckDenied(fn()=>$api->checkoff($actor,'10',$id,['state'=>'taken']),404);
ckDenied(fn()=>$api->checkoff(new App\Services\ApiAuthPrincipal(3,3),'10','1',['state'=>'taken']),403);
$rows->revokeOnLock=true;ckDenied(fn()=>$api->checkoff($actor,'10','1',['state'=>'taken']),403);$rows->revokeOnLock=false;
foreach([['is_active'=>0],['starts_on'=>'2026-10-15','ends_on'=>null],['starts_on'=>'2026-10-01','ends_on'=>'2026-10-13']] as $bad){$rows->records[1]=array_replace($base,$bad);ckDenied(fn()=>$api->checkoff($actor,'10','1',['state'=>'taken']),409);}$rows->records[1]=$base;
foreach(['stopped','historical'] as $status){$rows->medicines[20]['status']=$status;ckDenied(fn()=>$api->checkoff($actor,'10','1',['state'=>'taken']),409);}$rows->medicines=$unchanged;
$rows->medicines[20]['started_on']='2026-10-15';ckDenied(fn()=>$api->checkoff($actor,'10','1',['state'=>'taken']),409);$rows->medicines=$unchanged;
ck($rows->marks===$snapshot,'Denied requests have no checkoff effects');
$rows->failCommit=true;try{$api->checkoff($actor,'10','1',['state'=>'skipped']);throw new LogicException('Commit failure accepted');}catch(RuntimeException $e){ck($e->getMessage()==='Synthetic commit failure','Failed commit not success');}$rows->failCommit=false;
ck($rows->marks===$snapshot,'Rollback on commit failure');
$rows->records[1]['local_time']='invalid';try{$api->checkoff($actor,'10','1',['state'=>'skipped']);throw new LogicException('Bad projection accepted');}catch(UnexpectedValueException){ck($rows->marks===$snapshot,'Projection failure rolls back before response');}$rows->records[1]=$base;
foreach([1=>'owner',2=>'editor',3=>'viewer'] as $user=>$role){$list=$api->collection(new App\Services\ApiAuthPrincipal($user,$user),'10',[],true);ck($list['meta']['can_record_checkoffs']===($role!=='viewer'),'Narrow capability '.$role);}
ck($api->collection($actor,'11',['page'=>'999'],true)['meta']['can_record_checkoffs']===true,'Capability on empty beyond-end page');
$rows->records[1]=array_replace($base,['timezone'=>'America/New_York','starts_on'=>'2026-10-13','ends_on'=>'2026-10-13']);$domain->instant='2026-10-14T00:30:00Z';
$result=$api->checkoff($actor,'10','1',['state'=>'taken']);ck($result['scheduled_on']==='2026-10-13','Slot local yesterday when UTC is tomorrow');
$domain->instant='2026-10-14T04:00:00Z';ckDenied(fn()=>$api->checkoff($actor,'10','1',['state'=>'skipped']),409);
$rows->records[1]=array_replace($base,['timezone'=>'Asia/Kolkata','starts_on'=>'2026-10-15','ends_on'=>'2026-10-15']);$domain->instant='2026-10-14T19:00:00Z';
ck($api->checkoff($actor,'10','1',['state'=>'taken'])['scheduled_on']==='2026-10-15','Slot local tomorrow when UTC is yesterday');
$rows->records[1]=array_replace($base,['timezone'=>'America/New_York','local_time'=>'01:30','starts_on'=>'2026-11-01','ends_on'=>'2026-11-01']);
$domain->instant='2026-11-01T05:30:00Z';$api->checkoff($actor,'10','1',['state'=>'taken']);$count=count($rows->marks);
$domain->instant='2026-11-01T06:30:00Z';$api->checkoff($actor,'10','1',['state'=>'skipped']);ck(count($rows->marks)===$count,'DST fold one slot/local day');
ck($rows->records[2]===$untouched[2]&&$rows->records[3]===$untouched[3]&&$rows->medicines===$unchanged,'Unrelated schedules and medicine data unchanged');
ck($rows->marks['2:2026-10-14']['state']==='skipped'&&$rows->marks['2:2026-10-14']['recorded_by']===2,'Other checkoff unchanged');
ck(session_status()===PHP_SESSION_NONE,'No browser session');
$htaccess=file_get_contents(dirname(__DIR__).'/public/.htaccess');
ck(preg_match('/RewriteRule (\S+) - \[E=HTTP_AUTHORIZATION:/',$htaccess,$rule)===1,'Scoped forwarding rule found');
foreach(['api/v1/health-profiles/10/medication-schedules/1/checkoff','api/v1/health-profiles/10/medication-schedules/due-today','api/v1/me','api/v1/auth/logout'] as $path)ck(preg_match('~'.$rule[1].'~',$path)===1,'Expected scoped Authorization path');
foreach(['api/v1/health-profiles/10/medication-schedules/1/checkoff/extra','api/v1/health-profiles/10/medication-schedules/1/history','api/v1/diseases/migraine','account/health-records/10'] as $path)ck(preg_match('~'.$rule[1].'~',$path)===0,'No unrelated Authorization forwarding');
echo "Medication Checkoff API unit: $checks checks passed; database-free.\n";

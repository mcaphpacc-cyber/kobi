<?php
declare(strict_types=1);
// Reuse unchanged browser schedule assertions and deterministic clock fixtures.
require __DIR__.'/medication-schedules.php';
class ApiScheduleProfiles extends App\Repositories\HealthProfileRepository {
    public function __construct(){}
    public function findAccessible(int $profileId,int $userId):?array{return $userId===1&&in_array($profileId,[10,11],true)?['id'=>$profileId,'full_name'=>'PRIVATE','profile_type'=>'self','role'=>'owner']:null;}
}
class ApiScheduleRows extends ScheduleMemoryRepository {
    public array $reads=[];
    public function profileSlots(int $id):array{$this->reads[]=['due',$id];return $id===10?parent::profileSlots($id):[];}
    public function countApiSlots(int $id):int{$this->reads[]=['count',$id];return $id===10?count($this->records):0;}
    public function listApiSlots(int $id,int $limit,int $offset):array{$this->reads[]=['list',$id,$limit,$offset];$rows=$id===10?array_values($this->records):[];usort($rows,fn($a,$b)=>[-$a['is_active'],$a['local_time'],$a['id']]<=>[-$b['is_active'],$b['local_time'],$b['id']]);return array_slice($rows,$offset,$limit);}
}
class ApiScheduleSql extends App\Repositories\MedicationScheduleRepository {
    public array $queries=[];public function __construct(){}
    protected function fetch(string $sql,array $params=[]):?array{$this->queries[]=[$sql,$params];return ['total'=>'3'];}
    protected function fetchAll(string $sql,array $params=[]):array{$this->queries[]=[$sql,$params];return [];}
}
$checks=0;
function sc(bool $pass,string $label):void{global $checks;if(!$pass)throw new LogicException($label);++$checks;}
function scheduleApiDenied(callable $action,int $status):void{try{$action();throw new LogicException('Expected API denial');}catch(App\Services\ApiAuthException $e){sc($e->status===$status,'Safe API denial');}}
$auth=new ScheduleTestAuth();$browserProfiles=new ScheduleTestProfiles($auth);$medicines=new ScheduleTestMedicines($browserProfiles);
$rows=new ApiScheduleRows($medicines);$profiles=new App\Services\ApiHealthProfileService(new ApiScheduleProfiles());
$domain=new ScheduleClockService($rows,$medicines,$browserProfiles,$auth,$profiles);$domain->instant='2026-10-17T00:30:00Z';
$api=new App\Services\ApiMedicationScheduleService($profiles,$rows,$domain);$actor=new App\Services\ApiAuthPrincipal(1,1);
$base=['health_medicine_id'=>20,'local_time'=>'08:00','timezone'=>'UTC','starts_on'=>'2026-10-17','ends_on'=>null,'is_active'=>1,'status'=>'current','started_on'=>null,'brand_name'=>'B','notes'=>'PRIVATE','created_by'=>1,'recorded_at'=>'PRIVATE','access_token'=>'PRIVATE','documents'=>['PRIVATE']];
foreach(range(1,10) as $id)$rows->records[$id]=['id'=>$id]+$base;
$rows->records[1]=array_replace($rows->records[1],['local_time'=>'23:59','ends_on'=>'2026-10-17']);
$rows->records[3]=array_replace($rows->records[3],['timezone'=>'America/New_York','starts_on'=>'2026-10-16','ends_on'=>'2026-10-16','brand_name'=>'A']);
$rows->records[4]['is_active']=0;$rows->records[5]['starts_on']='2026-10-18';$rows->records[6]['ends_on']='2026-10-16';
$rows->records[7]['status']='stopped';$rows->records[8]['status']='historical';$rows->records[9]['started_on']='2026-10-18';
$rows->records[10]=array_replace($rows->records[10],['timezone'=>'Asia/Kolkata','local_time'=>'12:00']);
$rows->marks=['1:2026-10-17'=>['state'=>'taken','recorded_by'=>99],'2:2026-10-17'=>['state'=>'skipped','recorded_at'=>'PRIVATE']];
$before=serialize([$rows->records,$rows->marks]);
$listing=$api->collection($actor,'10',[]);sc($listing['meta']===['page'=>1,'page_size'=>20,'total'=>10,'has_more'=>false],'Default page and total include inactive/future/stopped configuration');
foreach($listing['data'] as $r){sc(array_keys($r)===['id','health_medicine_id','local_time','timezone','starts_on','ends_on','is_active'],'Exact schedule whitelist');sc(is_string($r['id'])&&$r['health_medicine_id']==='20'&&is_bool($r['is_active']),'IDs strings and active boolean');}
sc(end($listing['data'])['id']==='4'&&!end($listing['data'])['is_active'],'Inactive slot retained after active slots');
$due=$api->collection($actor,'10',[],true);
sc(array_column($due['data'],'id')===['3','2','10','1'],'Canonical time/brand/id order');
sc(array_column($due['data'],'state')===[null,'skipped',null,'taken'],'Unmarked and completed states retained');
sc($due['data'][0]['scheduled_on']==='2026-10-16'&&$due['data'][1]['scheduled_on']==='2026-10-17','Each timezone supplies its local date');
foreach($due['data'] as $r)sc(array_keys($r)===['id','health_medicine_id','local_time','timezone','scheduled_on','state'],'Exact Due whitelist');
sc(end($due['data'])['local_time']==='23:59','Later time included');
sc($before===serialize([$rows->records,$rows->marks]),'No record or checkoff writes');
sc(array_column($domain->dueToday(10),'id')===array_map('intval',array_column($due['data'],'id')),'Browser and bearer share canonical assembly');
$auth->uid=null;sc($api->collection($actor,'10',[],true)===$due,'No browser login dependency');
try{$domain->dueToday(10);throw new LogicException('Browser authentication bypass');}catch(RuntimeException $e){sc($e->getMessage()==='Authentication required.','Browser guard unchanged');}$auth->uid=1;
$page=$api->collection($actor,'10',['page_size'=>'1','page'=>'2'],true);sc($page['data']===[$due['data'][1]]&&$page['meta']['has_more'],'Page after eligibility/sort');
$api->collection($actor,'10',['page_size'=>'50','page'=>'10000']);sc(end($rows->reads)===['list',10,50,499950],'Max bounds passed to repository');
foreach([false,true] as $isDue)sc($api->collection($actor,'11',[],$isDue)===['data'=>[],'meta'=>['page'=>1,'page_size'=>20,'total'=>0,'has_more'=>false]+($isDue?['can_record_checkoffs'=>true]:[])],'Empty authorized profile, additive Due capability');
$reads=$rows->reads;
foreach([false,true] as $isDue){scheduleApiDenied(fn()=>$api->collection(new App\Services\ApiAuthPrincipal(2,2),'10',[],$isDue),404);scheduleApiDenied(fn()=>$api->collection($actor,'999',[],$isDue),404);scheduleApiDenied(fn()=>$api->collection($actor,'01',[],$isDue),422);}
foreach([['page_size'=>'51'],['page'=>'0'],['page'=>'01'],['page'=>'10001'],['timezone'=>'UTC'],['date'=>'2026-10-17'],['user_id'=>'2']] as $q)scheduleApiDenied(fn()=>$api->collection($actor,'10',$q,true),422);
foreach([['page'=>[]],['page'=>null],['page'=>"\xff"]] as $q)scheduleApiDenied(fn()=>$api->collection($actor,'10',$q),400);
sc($rows->reads===$reads,'Profile and pagination denial before schedule data reads');
$domain->instant='2026-10-17T04:00:00Z';sc(!in_array('3',array_column($api->collection($actor,'10',[],true)['data'],'id'),true),'New York local midnight ends prior local day');
$domain->instant='2026-10-18T00:00:00Z';sc(!in_array('1',array_column($api->collection($actor,'10',[],true)['data'],'id'),true),'End boundary becomes expired after local midnight');
$rows->records=[11=>array_replace($base,['id'=>11,'timezone'=>'America/New_York','local_time'=>'01:30','starts_on'=>'2026-11-01','ends_on'=>'2026-11-01'])];$rows->marks=['11:2026-11-01'=>['state'=>'taken']];
$domain->instant='2026-11-01T05:30:00Z';$fold=$api->collection($actor,'10',[],true);$domain->instant='2026-11-01T06:30:00Z';sc(count($fold['data'])===1&&$fold===$api->collection($actor,'10',[],true),'DST fold one local-day occurrence');
$rows->records[11]=array_replace($rows->records[11],['local_time'=>'02:30','starts_on'=>'2026-03-08','ends_on'=>'2026-03-08']);$rows->marks=[];$domain->instant='2026-03-08T07:30:00Z';sc(count($api->collection($actor,'10',[],true)['data'])===1,'DST gap retains daily wall-time entry');
foreach([['timezone'=>'Mars/Zone'],['local_time'=>'08:00:00'],['starts_on'=>'2026-02-30'],['is_active'=>2]] as $bad){$original=$rows->records[11];$rows->records[11]=array_replace($original,$bad);try{$api->collection($actor,'10',[]);throw new LogicException('Invalid stored value accepted');}catch(UnexpectedValueException){sc(true,'Invalid stored output fails safely');}$rows->records[11]=$original;}
$rows->marks=['11:2026-03-08'=>['state'=>'TAKEN']];try{$api->collection($actor,'10',[],true);throw new LogicException('Invalid state accepted');}catch(UnexpectedValueException){sc(true,'State domain exact');}
$sql=new ApiScheduleSql();sc($sql->countApiSlots(10)===3,'Integer count');$sql->listApiSlots(10,999,-1);
foreach($sql->queries as [$query,$params]){sc(str_contains($query,'JOIN health_medicines m ON m.id=s.health_medicine_id')&&str_contains($query,'m.health_profile_id=:profile')&&$params===['profile'=>10],'SQL profile binding');sc(!preg_match('/brand_name|notes|prescribed_by|created_by|SELECT \*/',$query),'Minimal SQL projection');}
sc(str_contains($sql->queries[1][0],'ORDER BY s.is_active DESC,s.local_time ASC,s.id ASC')&&str_contains($sql->queries[1][0],'LIMIT 50 OFFSET 0'),'Slot ordering and bound defense');
sc(session_status()===PHP_SESSION_NONE,'No session');
echo "Medication Schedule API unit: $checks checks passed; existing browser regressions reused.\n";

<?php
declare(strict_types=1);
require __DIR__.'/api-auth-unit-support.php';

class ConditionProfiles extends App\Repositories\HealthProfileRepository {
    public array $calls=[];
    public function __construct(){}
    public function findAccessible(int $profileId,int $userId):?array {
        $this->calls[]=[$profileId,$userId];
        return $userId===1&&in_array($profileId,[10,11],true)?['id'=>$profileId,'full_name'=>'Private profile','profile_type'=>'self']:null;
    }
}
class ConditionRows extends App\Repositories\HealthConditionRepository {
    public array $calls=[];public array $rows=[];
    public function __construct(){}
    public function countApiByProfile(int $profileId):int{$this->calls[]=['count',$profileId];return count($this->rows[$profileId]??[]);}
    public function listApiByProfile(int $profileId,int $limit,int $offset):array{$this->calls[]=['list',$profileId,$limit,$offset];return array_slice($this->rows[$profileId]??[],$offset,$limit);}
    public function findApiByIdInProfile(int $conditionId,int $profileId):?array{$this->calls[]=['detail',$conditionId,$profileId];foreach($this->rows[$profileId]??[] as $r)if((int)$r['id']===$conditionId)return $r;return null;}
    public function findById(int $id,int $healthProfileId):?array{throw new LogicException('API must use minimal scoped read');}
}
class ConditionSql extends App\Repositories\HealthConditionRepository {
    public array $queries=[];
    public function __construct(){}
    protected function fetch(string $sql,array $params=[]):?array{$this->queries[]=[$sql,$params];return ['total'=>'7'];}
    protected function fetchAll(string $sql,array $params=[]):array{$this->queries[]=[$sql,$params];return [];}
}
$checks=0;
function cc(bool $pass,string $label):void{global $checks;if(!$pass)throw new LogicException($label);++$checks;}
function conditionDenied(callable $action,int $status):void{try{$action();throw new LogicException('Expected denial');}catch(App\Services\ApiAuthException $e){cc($e->status===$status,'Safe error status');}}
$profiles=new ConditionProfiles();$rows=new ConditionRows();
$service=new App\Services\ApiHealthConditionService(new App\Services\ApiHealthProfileService($profiles),$rows);
$actor=new App\Services\ApiAuthPrincipal(1,1);
$dto=['id'=>'7','condition_name'=>'Recorded नाम <label>','status'=>'active','diagnosed_on'=>'2024-02-29','resolved_on'=>null];
$rows->rows[10]=[array_replace($dto,['id'=>7])+['health_profile_id'=>10,'doctor_hospital'=>'PRIVATE PROVIDER','notes'=>'PRIVATE NOTES','documents'=>['path'=>'PRIVATE'],'date_of_birth'=>'PRIVATE','role'=>'owner','sharing_token'=>'PRIVATE','access_token'=>'PRIVATE']];
cc($service->detail($actor,'10','7',[])===$dto,'Exact five-field string-ID/null/date projection');
cc($service->collection($actor,'10',[])===['data'=>[$dto],'meta'=>['page'=>1,'page_size'=>20,'total'=>1,'has_more'=>false]],'Default pagination envelope data/meta');
cc($service->collection($actor,'11',[])===['data'=>[],'meta'=>['page'=>1,'page_size'=>20,'total'=>0,'has_more'=>false]],'Empty list integer zero');
$service->collection($actor,'10',['page'=>'2','page_size'=>'50']);cc(end($rows->calls)===['list',10,50,50],'Maximum page size and offset');
$service->collection($actor,'10',['page'=>'10000','page_size'=>'50']);cc(end($rows->calls)===['list',10,50,499950],'Maximum page bounds');
$before=$rows->calls;
foreach(['0','01','-1','abc','2147483648','999'] as $id)conditionDenied(fn()=>$service->collection($actor,$id,[]),$id==='999'?404:422);
conditionDenied(fn()=>$service->detail(new App\Services\ApiAuthPrincipal(2,2),'10','7',[]),404);
cc($rows->calls===$before,'Profile denial before list/detail/count read');
foreach(['0','01','-1','+1','1.0','1e2',' 1','',str_repeat('9',30),'10001'] as $value)conditionDenied(fn()=>$service->collection($actor,'10',['page'=>$value]),422);
foreach(['0','01','51','100','-1'] as $value)conditionDenied(fn()=>$service->collection($actor,'10',['page_size'=>$value]),422);
foreach([['page'=>[]],['page'=>1],['page'=>null],['page'=>"\xff"]] as $input)conditionDenied(fn()=>$service->collection($actor,'10',$input),400);
foreach([['limit'=>'1'],['user_id'=>'2'],['status'=>'active']] as $input)conditionDenied(fn()=>$service->collection($actor,'10',$input),422);
cc($rows->calls===$before,'Invalid pagination never reads records');
conditionDenied(fn()=>$service->detail($actor,'10','7',['page'=>'1']),400);
foreach(['0','01','2147483648','abc'] as $id)conditionDenied(fn()=>$service->detail($actor,'10',$id,[]),422);
conditionDenied(fn()=>$service->detail($actor,'10','999',[]),404);
conditionDenied(fn()=>$service->detail($actor,'11','7',[]),404);
foreach(['active','chronic','resolved','historical'] as $status){$rows->rows[10][0]['status']=$status;cc($service->detail($actor,'10','7',[])['status']===$status,'Exact stored domain status');}
$rows->rows[10][0]['status']='';try{$service->detail($actor,'10','7',[]);throw new LogicException('Expected corrupt enum failure');}catch(UnexpectedValueException){cc(true,'Invalid stored enum fails safely');}
$rows->rows[10][0]['status']='active';
foreach(['2023-02-29','0000-00-00','2026-01-01 00:00:00','not-date',''] as $date){$rows->rows[10][0]['diagnosed_on']=$date;try{$service->detail($actor,'10','7',[]);throw new LogicException('Expected corrupt date failure');}catch(UnexpectedValueException){cc(true,'Invalid stored date fails safely');}}
$rows->rows[10][0]['diagnosed_on']=null;cc($service->detail($actor,'10','7',[])['diagnosed_on']===null,'Nullable diagnosis date');
$sql=new ConditionSql();cc($sql->countApiByProfile(10)===7,'Count normalized to integer');$sql->listApiByProfile(10,50,50);$sql->findApiByIdInProfile(7,10);
foreach($sql->queries as [$query,$params]){cc(str_contains($query,'health_profile_id = :profile')&&$params['profile']===10,'Every SQL read bound to profile');cc(!preg_match('/notes|doctor_hospital|JOIN|SELECT \*/i',$query),'No private columns or joins');}
cc(str_contains($sql->queries[2][0],'id = :id AND health_profile_id = :profile')&&$sql->queries[2][1]===['id'=>7,'profile'=>10],'Detail SQL binds condition and profile');
cc(str_contains($sql->queries[1][0],"WHEN 'active' THEN 1 WHEN 'chronic' THEN 2")&&str_contains($sql->queries[1][0],"WHEN 'historical' THEN 3 WHEN 'resolved' THEN 4")&&str_contains($sql->queries[1][0],'diagnosed_on DESC, id DESC LIMIT 50 OFFSET 50'),'Canonical ordering and pagination SQL');
$sql->listApiByProfile(10,999,-1);cc(str_contains(end($sql->queries)[0],'LIMIT 50 OFFSET 0'),'Repository bound defense');
cc(session_status()===PHP_SESSION_NONE,'No browser session');
echo "Medical Conditions API unit: $checks checks passed; no database/session/log writes.\n";

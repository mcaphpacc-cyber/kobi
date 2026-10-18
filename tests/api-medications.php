<?php
declare(strict_types=1);
require __DIR__.'/api-auth-unit-support.php';
class MedicationApiProfiles extends App\Repositories\HealthProfileRepository {
    public array $calls=[];
    public function __construct(){}
    public function findAccessible(int $profileId,int $userId):?array{$this->calls[]=[$profileId,$userId];return $userId===1&&in_array($profileId,[10,11],true)?['id'=>$profileId,'full_name'=>'Private profile','profile_type'=>'self']:null;}
}
class MedicationApiRows extends App\Repositories\HealthMedicineRepository {
    public array $calls=[];public array $rows=[];
    public function __construct(){}
    public function findApiByProfile(int $profileId):array{$this->calls[]=['list',$profileId];return $this->rows[$profileId]??[];}
    public function findApiByIdInProfile(int $id,int $profileId):?array{$this->calls[]=['detail',$id,$profileId];foreach($this->rows[$profileId]??[] as $r){if($r['id']===$id)return $r;}return null;}
    public function findById(int $id):?array{throw new LogicException('Unscoped detail must not be used');}
}
class MedicationApiSql extends App\Repositories\HealthMedicineRepository {
    public array $queries=[];public function __construct(){}
    protected function fetch(string $sql,array $params=[]):?array{$this->queries[]=[$sql,$params];return null;}
    protected function fetchAll(string $sql,array $params=[]):array{$this->queries[]=[$sql,$params];return [];}
}
$checks=0;function medicationCheck(bool $ok,string $name):void{global $checks;if(!$ok)throw new LogicException($name);++$checks;}
$profiles=new MedicationApiProfiles();$rows=new MedicationApiRows();$service=new App\Services\ApiHealthMedicineService(new App\Services\ApiHealthProfileService($profiles),$rows);$actor=new App\Services\ApiAuthPrincipal(1,1);
$expected=['id'=>'7','brand_name'=>'Recorded नाम','generic_name'=>null,'strength'=>'10 mg','dose'=>'recorded dose','frequency'=>null,'route'=>null,'status'=>'current','started_on'=>'2026-01-01','stopped_on'=>null];
$rows->rows[10]=[array_replace($expected,['id'=>7])+['notes'=>'private','prescribed_by'=>'private','health_profile_id'=>10,'mobile_number'=>'private','role'=>'owner','schedule'=>['private'],'access_token'=>'private','sharing_token'=>'private']];
medicationCheck($service->collection($actor,'10')===[$expected],'Collection exact ten fields, string ID and nulls');
medicationCheck($service->detail($actor,'10','7')===$expected,'Detail exact recorded projection');
medicationCheck($rows->calls===[['list',10],['detail',7,10]],'Existing repository receives authorized profile');
medicationCheck($service->collection($actor,'11')===[],'Empty authorized collection');
$before=$rows->calls;
foreach([new App\Services\ApiAuthPrincipal(2,2),new App\Services\ApiAuthPrincipal(3,3)] as $other){try{$service->detail($other,'10','7');throw new LogicException('Expected denial');}catch(App\Services\ApiAuthException $e){medicationCheck($e->status===404,'Other user profile denied');}}
medicationCheck($rows->calls===$before,'Profile denial precedes medication query');
foreach(['8','999'] as $id){try{$service->detail($actor,'10',$id);throw new LogicException('Expected missing');}catch(App\Services\ApiAuthException $e){medicationCheck([$e->status,$e->errorCode,$e->getMessage()]===[404,'NOT_FOUND','Medication not found.'],'Missing medicine safe error');}}
try{$service->detail($actor,'11','7');throw new LogicException('Expected cross-profile missing');}catch(App\Services\ApiAuthException $e){medicationCheck($e->status===404,'Other accessible profile cannot use same medicine ID');}
foreach(['0','01','-1','1.0','+1','abc','2147483648',str_repeat('9',100)] as $id){$before=count($rows->calls);try{$service->detail($actor,'10',$id);throw new LogicException('Expected invalid');}catch(App\Services\ApiAuthException $e){medicationCheck($e->status===422&&count($rows->calls)===$before,'ID checked before medicine query');}}
foreach(['current','stopped','historical'] as $status){$rows->rows[10][0]['status']=$status;medicationCheck($service->detail($actor,'10','7')['status']===$status,'Existing statuses preserved');}
$rows->rows[10][0]['status']='';try{$service->detail($actor,'10','7');throw new LogicException('Expected stored-value failure');}catch(UnexpectedValueException){medicationCheck(true,'Invalid stored enum fails safely');}
$sql=new MedicationApiSql();$sql->findApiByProfile(10);$sql->findApiByIdInProfile(7,10);
foreach($sql->queries as [$query,$params]){medicationCheck(!str_contains($query,'*')&&!str_contains($query,'notes')&&!str_contains($query,'prescribed_by'),'SQL explicit minimal columns');medicationCheck(str_contains($query,'health_profile_id=:profile')&&$params['profile']===10,'SQL constrained to profile');}
medicationCheck(str_contains($sql->queries[1][0],'id=:id AND health_profile_id=:profile')&&$sql->queries[1][1]===['id'=>7,'profile'=>10],'Detail binds child and profile together');
medicationCheck(session_status()===PHP_SESSION_NONE,'No browser session');
echo "Personal Medications API unit: $checks checks passed; no database/session/log writes.\n";

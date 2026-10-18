<?php
declare(strict_types=1);
require __DIR__.'/api-auth-unit-support.php';
class ProfileApiRepository extends App\Repositories\HealthProfileRepository {
    public array $rows=[];public array $calls=[];
    public function __construct(){}
    public function findAccessibleByUser(int $userId):array{$this->calls[]=['list',$userId];return $this->rows[$userId]??[];}
    public function findAccessible(int $profileId,int $userId):?array{$this->calls[]=['detail',$profileId,$userId];foreach($this->rows[$userId]??[] as $row){if($row['id']===$profileId)return $row;}return null;}
}
$checks=0;
function hpCheck(bool $ok,string $message):void{global $checks;if(!$ok)throw new LogicException($message);++$checks;}
$repo=new ProfileApiRepository();$service=new App\Services\ApiHealthProfileService($repo);$a=new App\Services\ApiAuthPrincipal(1,10);$b=new App\Services\ApiAuthPrincipal(2,20);
$repo->rows[1]=[['id'=>7,'full_name'=>'Private <name> नाम','profile_type'=>'family','date_of_birth'=>'2000-01-01','gender'=>'other','created_by'=>2,'role'=>'viewer','membership_status'=>'active','status'=>'active','mobile_number'=>'private','notes'=>'private','documents'=>['private'],'invitation_token'=>'private']];
$repo->rows[2]=[['id'=>8,'full_name'=>'Other profile','profile_type'=>'self']];
$expected=['id'=>'7','full_name'=>'Private <name> नाम','profile_type'=>'family'];
hpCheck($service->accessible($a)===[$expected],'List exact projection including string ID and no internal metadata');
hpCheck($service->detail($a,'7')===$expected,'Detail exact projection');
hpCheck($repo->calls===[['list',1],['detail',7,1]],'Existing methods receive only principal actor');
hpCheck($service->accessible(new App\Services\ApiAuthPrincipal(3,30))===[],'Empty list without profile creation');
foreach([[$a,'8'],[$b,'7'],[$a,'999']] as [$actor,$id]){try{$service->detail($actor,$id);throw new LogicException('Expected missing');}catch(App\Services\ApiAuthException $e){hpCheck([$e->status,$e->errorCode,$e->getMessage()]===[404,'NOT_FOUND','Health profile not found.'],'Unknown and inaccessible same error');}}
foreach(['0','01','-1','1.0','+1','abc','2147483648','9999999999999999999999'] as $id){try{$service->detail($a,$id);throw new LogicException('Expected invalid ID');}catch(App\Services\ApiAuthException $e){hpCheck($e->status===422,'ID validation before query');}}
foreach(['self','family','dependent'] as $type){$repo->rows[1][0]['profile_type']=$type;hpCheck($service->detail($a,'7')['profile_type']===$type,'Existing profile type');}
$repo->rows[1][0]['profile_type']='';try{$service->detail($a,'7');throw new LogicException('Expected stored data failure');}catch(UnexpectedValueException){hpCheck(true,'Invalid stored type fails closed');}
hpCheck(session_status()===PHP_SESSION_NONE,'No session');
echo "Health Profiles API unit: $checks checks passed; no database/session writes.\n";

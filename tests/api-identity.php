<?php
declare(strict_types=1);
require __DIR__.'/api-auth-unit-support.php';

class IdentityUsers extends App\Repositories\UserRepository {
    public array $rows=[]; public array $lookups=[];
    public function __construct() {}
    public function findById(int $id): ?array { $this->lookups[]=$id;return $this->rows[$id]??null; }
}
class IdentityLimit extends ApiNoLimit {
    public array $calls=[];
    public function hit(string $category,string $identity,int $limit,int $window):void { $this->calls[]=[$category,$identity,$limit,$window]; }
}
$checks=0;
function ia(bool $ok,string $name):void { global $checks;if(!$ok)throw new LogicException($name);++$checks; }
$clock=new ApiTestClock();$repo=new ApiMemoryRepository();$limit=new IdentityLimit();$users=new IdentityUsers();
$password=bin2hex(random_bytes(18));
foreach([1,2] as $id){
    $row=['id'=>$id,'email'=>'identity'.$id.'@example.invalid','password_hash'=>password_hash($password,PASSWORD_DEFAULT),'password_set_at'=>'2026-01-01','status'=>'active','email_verified_at'=>'2026-01-01','mobile_number'=>'+12025550123','name'=>'private name','health_records'=>['private'],'sharing'=>'private'];
    $repo->users[$id]=$row;$users->rows[$id]=$row;
}
$passwords=(new ReflectionClass(App\Services\AuthService::class))->newInstanceWithoutConstructor();
$service=new App\Services\ApiAuthService($repo,$passwords,$clock,$limit);
$guard=new App\Services\ApiAuthGuard($service,$limit);$projection=new App\Services\ApiIdentityService($users);
$profileRepository=(new ReflectionClass(App\Repositories\HealthProfileRepository::class))->newInstanceWithoutConstructor();
$profileProjection=new App\Services\ApiHealthProfileService($profileRepository);
$medicineRepository=(new ReflectionClass(App\Repositories\HealthMedicineRepository::class))->newInstanceWithoutConstructor();
$summaryAssembly=(new ReflectionClass(App\Services\MedicalSummaryService::class))->newInstanceWithoutConstructor();
$conditionRepository=(new ReflectionClass(App\Repositories\HealthConditionRepository::class))->newInstanceWithoutConstructor();
$scheduleRepository=(new ReflectionClass(App\Repositories\MedicationScheduleRepository::class))->newInstanceWithoutConstructor();
$scheduleDomain=(new ReflectionClass(App\Services\MedicationScheduleService::class))->newInstanceWithoutConstructor();
$savedProjection=(new ReflectionClass(App\Services\ApiSavedSymptomCheckService::class))->newInstanceWithoutConstructor();
$controller=new App\Controllers\ApiAuthController(new App\Core\Request(),$service,$guard,$projection,$profileProjection,new App\Services\ApiHealthMedicineService($profileProjection,$medicineRepository),new App\Services\ApiMedicalSummaryService($summaryAssembly),new App\Services\ApiHealthConditionService($profileProjection,$conditionRepository),new App\Services\ApiMedicationScheduleService($profileProjection,$scheduleRepository,$scheduleDomain),$savedProjection);
function identityCall(?string $header,array $query=[],?string $redirect=null,bool $https=true):array {
    global $controller;
    $_GET=$query;$_SERVER=['REQUEST_METHOD'=>'GET','HTTPS'=>$https?'on':'off','REMOTE_ADDR'=>'127.0.0.1','HTTP_COOKIE'=>'PHPSESSID=not-an-api-credential','HTTP_X_USER_ID'=>'2'];
    if($header!==null)$_SERVER['HTTP_AUTHORIZATION']=$header;
    if($redirect!==null)$_SERVER['REDIRECT_HTTP_AUTHORIZATION']=$redirect;
    ob_start();$controller->me();$raw=ob_get_clean();
    return [http_response_code(),json_decode($raw,true,16,JSON_THROW_ON_ERROR)];
}
$a=$service->login('identity1@example.invalid',$password,null);$device=$service->login('identity1@example.invalid',$password,null);$b=$service->login('identity2@example.invalid',$password,null);
foreach([[$a,1],[$device,1],[$b,2]] as [$issued,$id]){
    $before=serialize([$repo->users,$repo->sessions,$repo->tokens]);$limit->calls=[];
    [$status,$json]=identityCall('Bearer '.$issued['access_token']);
    ia($status===200,'Valid access success');
    ia($json===['success'=>true,'data'=>['id'=>(string)$id,'email'=>'identity'.$id.'@example.invalid','status'=>'active','email_verified'=>true],'meta'=>['api_version'=>'v1']],'Exact envelope and private field exclusion');
    ia(end($users->lookups)===$id,'Only principal selects repository account');
    ia($before===serialize([$repo->users,$repo->sessions,$repo->tokens]),'Identity does not mutate account/session/refresh data');
    ia(count($limit->calls)===1&&$limit->calls[0][0]==='access_family'&&array_slice($limit->calls[0],2)===[120,60],'One admission uses existing family budget');
}
$denied=['success'=>false,'error'=>['code'=>'UNAUTHENTICATED','message'=>'Authentication is required. Please sign in again.'],'meta'=>['api_version'=>'v1']];
foreach([null,'','Basic invalid','Bearer invalid','Bearer '.$a['refresh_token'],'Bearer ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=')] as $header){
    [$status,$j]=identityCall($header);ia($status===401&&$j===$denied,'Generic missing/malformed/unknown denial');
}
[$status,$j]=identityCall('Bearer '.$a['access_token'],[], 'Bearer '.$b['access_token']);ia($status===401&&$j===$denied,'Conflicting server credentials denied');
[$status,$j]=identityCall(null,[],'Bearer '.$a['access_token']);ia($status===200&&$j['data']['id']==='1','Existing redirect variable works');
[$status,$j]=identityCall('Bearer '.$a['access_token'],[],'Bearer '.$a['access_token']);ia($status===200,'Matching duplicate variables retain behavior');
[$status,$j]=identityCall('Bearer '.$a['access_token'],['user_id'=>'2']);ia($status===400&&$j['error']['code']==='BAD_REQUEST','Query cannot override identity');
[$status,$j]=identityCall(null,['user_id'=>'2']);ia($status===401,'Admission precedes query validation');
[$status,$j]=identityCall('Bearer '.$a['access_token'],[],null,false);ia($status===400,'HTTPS required');
$rotated=$service->refresh($a['refresh_token']);[$status,$j]=identityCall('Bearer '.$a['access_token']);ia($status===401&&$j===$denied,'Superseded access denied');
[$status,$j]=identityCall('Bearer '.$rotated['access_token']);ia($status===200,'Rotated successor accepted');
$service->logout($rotated['access_token']);[$status,$j]=identityCall('Bearer '.$rotated['access_token']);ia($status===401&&$j===$denied,'Revoked access denied');
[$status,$j]=identityCall('Bearer '.$device['access_token']);ia($status===200,'Single logout preserves other device');
$service->logoutAll($device['access_token'],$password);[$status,$j]=identityCall('Bearer '.$device['access_token']);ia($status===401&&$j===$denied,'Logout-all invalidates own devices');
[$status,$j]=identityCall('Bearer '.$b['access_token']);ia($status===200&&$j['data']['id']==='2','Other account unaffected');
$clock->time+=600;[$status,$j]=identityCall('Bearer '.$b['access_token']);ia($status===401&&$j===$denied,'Expired access generic denial');
$clock->time-=600;
foreach(['inactive','unverified','no_password','changed_stamp','deleted'] as $case){
    $old=$repo->users[2];
    if($case==='inactive')$repo->users[2]['status']='inactive';
    if($case==='unverified')$repo->users[2]['email_verified_at']=null;
    if($case==='no_password')$repo->users[2]['password_set_at']=null;
    if($case==='changed_stamp')$repo->users[2]['password_hash']='changed';
    if($case==='deleted')unset($repo->users[2]);
    [$status,$j]=identityCall('Bearer '.$b['access_token']);ia($status===401&&$j===$denied,'Account eligibility denial '.$case);$repo->users[2]=$old;
}
foreach(['missing','inactive','unverified'] as $case){
    $old=$users->rows[2];
    if($case==='missing')unset($users->rows[2]);
    if($case==='inactive')$users->rows[2]['status']='inactive';
    if($case==='unverified')$users->rows[2]['email_verified_at']=null;
    [$status,$j]=identityCall('Bearer '.$b['access_token']);ia($status===401&&$j===$denied,'Eligibility loss before projection denied');$users->rows[2]=$old;
}
ia(session_status()===PHP_SESSION_NONE,'No browser session started');
ia(!str_contains(implode('|',App\Core\Logger::$messages),$password),'No credential logging');
echo "API identity unit: $checks checks passed; no database/session/log writes.\n";

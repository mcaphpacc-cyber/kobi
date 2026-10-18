<?php
declare(strict_types=1);
namespace App\Core {
    final class Logger {public static array $messages=[];public static function error(string $message):void {self::$messages[]=$message;}}
}
namespace {
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
ob_start();
define('MEDICINE_BOOTSTRAP_LIBRARY',true);
require __DIR__.'/medicine-authority-bootstrap.php';
class OwnerWebRedirect extends Exception {public function __construct(public string $target){parent::__construct('Synthetic redirect');}}
function redirect(string $path):never {throw new OwnerWebRedirect($path);}
function url(string $path=''):string {return '/'.ltrim($path,'/');}
function csrfField():string {return '<input type="hidden" name="_csrf_token" value="'.htmlspecialchars((new OwnerWebSession())->csrfToken(),ENT_QUOTES,'UTF-8').'">';}
class OwnerWebSession extends App\Core\Session {public function start():void {}}
class OwnerWebController extends App\Controllers\MedicineOwnerBootstrapController {
    public array $rendered=[];
    protected function view(string $name,array $data=[]):void {$this->rendered=['view'=>$name,'data'=>$data];}
}
class OwnerWebMemory extends MedicineBootstrapMemory {
    public bool $ready=true,$ownerActive=true;
    public function ownerBootstrapState():array {return ['schema_ready'=>$this->ready,'initialized'=>$this->authorityInitialized()];}
    public function actor(int $id,bool $lock=false):?array {return $id===1?['id'=>1,'status'=>$this->ownerActive?'active':'inactive']:parent::actor($id,$lock);}
}
function ownerWebFixture():array {
    $_SESSION=[];
    [$original]=medicineFixture();$inner=new MedicineV1Inner();$inner->data=$original->data;$r=new OwnerWebMemory($inner);$inner->governance=$r;
    $p=new MedicineV1Policy(['owner_bootstrap'=>['owner_user_id'=>1,'administrator_user_id'=>1,'decision_reference'=>'SYNTHETIC_OWNER_CHOICE_A','policy_version'=>'initial-owner-v1']]);
    [$s,$a,$authority,$e]=medicineGovernanceObjects($r,$p);$c=new OwnerWebController($s,$a,new OwnerWebSession());return [$r,$s,$a,$authority,$p,$c];
}
function ownerWebInvoke(OwnerWebController $c,string $method='GET',array $post=[],array $get=[],?string $action=null):?string {
    $_SERVER['REQUEST_METHOD']=$method;$_GET=$get;$_POST=$post;$_SESSION['_csrf_token']='synthetic-web-csrf';http_response_code(200);$c->rendered=[];
    try{$c->{$action??($method==='GET'?'show':'store')}();return null;}catch(OwnerWebRedirect $e){return $e->target;}
}
function ownerWebPost():array {return ['_csrf_token'=>'synthetic-web-csrf','confirm'=>'establish_owner_administrator'];}
if(defined('OWNER_WEB_LIBRARY')){return;}
foreach(['GET','POST'] as $method){medicineTest('unauthenticated '.$method.' redirects with no writes',function()use($method){[$r,, $a,,,$c]=ownerWebFixture();$a->actorId=null;medicineCheck(ownerWebInvoke($c,$method,ownerWebPost())==='/login');medicineCheck($r->grantRows===[]&&$r->eventRows===[]);});}
medicineTest('owner GET is read-only and displays fixed confirmation with CSRF',function(){[$r,,,,,$c]=ownerWebFixture();ownerWebInvoke($c);medicineCheck($c->rendered['view']==='admin/medicine/owner-bootstrap');extract($c->rendered['data']);ob_start();require dirname(__DIR__).'/app/Views/admin/medicine/owner-bootstrap.php';$html=ob_get_clean();medicineCheck(str_contains($html,'Account 1 — KOBI Owner — Medicine Administrator — No Expiry')&&str_contains($html,'name="_csrf_token"')&&str_contains($html,'method="post"'));medicineCheck(!preg_match('/name="(?:account_id|user_id|role|authority|expiry|expires_at)"/',$html));medicineCheck($r->grantRows===[]&&$r->eventRows===[]);});
foreach([2,3,7,8] as $actor){medicineTest('other account denied '.$actor,function()use($actor){[$r,,$a,,,$c]=ownerWebFixture();$a->actorId=$actor;foreach(['GET','POST'] as $method){ownerWebInvoke($c,$method,ownerWebPost());medicineCheck(http_response_code()===403);}medicineCheck($r->grantRows===[]&&$r->eventRows===[]);});}
medicineTest('inactive owner rejected on GET and POST',function(){[$r,,,,,$c]=ownerWebFixture();$r->ownerActive=false;foreach(['GET','POST'] as $method){ownerWebInvoke($c,$method,ownerWebPost());medicineCheck(http_response_code()===403);}medicineCheck($r->grantRows===[]);});
foreach(['missing','other_owner','other_administrator','legacy_owner'] as $kind){medicineTest('explicit owner designation required '.$kind,function()use($kind){[$r,,,,$p,$c]=ownerWebFixture();if($kind==='missing'){$p->configuration=[];}elseif($kind==='legacy_owner'){$p->configuration['owner_user_ids']=[1];}else{$p->configuration['owner_bootstrap'][$kind==='other_owner'?'owner_user_id':'administrator_user_id']=2;}ownerWebInvoke($c,'POST',ownerWebPost());medicineCheck(http_response_code()===403&&$r->grantRows===[]&&$r->eventRows===[]);});}
foreach([null,'forged',[],42] as $token){medicineTest('missing invalid or malformed CSRF '.json_encode($token),function()use($token){[$r,,,,,$c]=ownerWebFixture();$post=ownerWebPost();if($token===null){unset($post['_csrf_token']);}else{$post['_csrf_token']=$token;}ownerWebInvoke($c,'POST',$post);medicineCheck(http_response_code()===403&&$r->grantRows===[]&&$r->eventRows===[]);});}
medicineTest('URL CSRF does not authorize POST',function(){[$r,,,,,$c]=ownerWebFixture();ownerWebInvoke($c,'POST',['confirm'=>'establish_owner_administrator'],['_csrf_token'=>'synthetic-web-csrf']);medicineCheck(http_response_code()===403&&$r->grantRows===[]);});
foreach(['account_id','user_id','role','authority','expiry','expires_at','owner_designation','reviewer_status','contributor_status','deployment_status','snapshot_json','rationale','capability','scope'] as $field){medicineTest('reject client authority or audit field '.$field,function()use($field){[$r,,,,,$c]=ownerWebFixture();ownerWebInvoke($c,'POST',ownerWebPost()+[$field=>'FORGED']);medicineCheck(http_response_code()===422&&$r->grantRows===[]&&$r->eventRows===[]);});}
medicineTest('POST query parameters rejected',function(){[$r,,,,,$c]=ownerWebFixture();ownerWebInvoke($c,'POST',ownerWebPost(),['account_id'=>2]);medicineCheck(http_response_code()===422&&$r->grantRows===[]);});
medicineTest('confirmation required and type checked',function(){foreach([null,'other',[]] as $value){[$r,,,,,$c]=ownerWebFixture();$post=ownerWebPost();$post['confirm']=$value;ownerWebInvoke($c,'POST',$post);medicineCheck(http_response_code()===422&&$r->grantRows===[]);}});
medicineTest('GET cannot invoke write action and POST cannot invoke display action',function(){[$r,,,,,$c]=ownerWebFixture();ownerWebInvoke($c,'GET',ownerWebPost(),[],'store');medicineCheck(http_response_code()===405);ownerWebInvoke($c,'POST',ownerWebPost(),[],'show');medicineCheck(http_response_code()===405&&$r->grantRows===[]&&$r->eventRows===[]);});
medicineTest('migration absent disables form and server execution',function(){[$r,,,,,$c]=ownerWebFixture();$r->ready=false;ownerWebInvoke($c);medicineCheck(!$c->rendered['data']['status']['schema_ready']);ownerWebInvoke($c,'POST',ownerWebPost());medicineCheck(http_response_code()===503&&$r->grantRows===[]&&$r->eventRows===[]);});
medicineTest('fixed authenticated POST uses service and creates only permanent owner and attributed audit',function(){[$r,$s,$a,$authority,,$c]=ownerWebFixture();$before=$r->inner->data;medicineCheck(ownerWebInvoke($c,'POST',ownerWebPost())==='/admin/medicine/owner-bootstrap');medicineCheck(count($r->grantRows)===1&&count($r->eventRows)===1);$g=$r->grantRows[1];$event=$r->eventRows[1];medicineCheck($g['user_id']===1&&$g['granted_by']===1&&$g['capability']==='administrator'&&$g['scope']==='catalog'&&$g['expires_at']===null);medicineCheck($event['actor_id']===1&&$event['action']==='grant'&&json_decode($event['snapshot_json'],true)['establishment']['owner_user_id']==='1');$authority->requireOperator(1,'administrator');foreach(['registry','deployment'] as $cap){medicineReject(fn()=>$authority->requireOperator(1,$cap),403);}foreach(['contributor','editor','reviewer'] as $cap){medicineReject(fn()=>$authority->requireGovernance(1,$cap,'composition'),403);}medicineCheck($r->inner->data===$before&&$r->pilotRows===[]);});
medicineTest('double submit refresh and revoked history stay closed',function(){[$r,$s,,,,$c]=ownerWebFixture();ownerWebInvoke($c,'POST',ownerWebPost());ownerWebInvoke($c);medicineCheck($c->rendered['data']['status']['initialized']&&$c->rendered['data']['success']);ownerWebInvoke($c);medicineCheck(!$c->rendered['data']['success']);ownerWebInvoke($c,'POST',ownerWebPost());medicineCheck(http_response_code()===409&&count($r->grantRows)===1&&count($r->eventRows)===1);$s->revokeAuthority('1','Synthetic revoke');ownerWebInvoke($c,'POST',ownerWebPost());medicineCheck(http_response_code()===409&&count($r->grantRows)===1&&count($r->eventRows)===2);});
medicineTest('ordinary self-grant and permanent delegated grant remain denied',function(){[$r,$s,,,,$c]=ownerWebFixture();ownerWebInvoke($c,'POST',ownerWebPost());medicineReject(fn()=>$s->grantAuthority(['user_id'=>1,'capability'=>'contributor','scope'=>'catalog','expires_at'=>'2030-01-01 00:00:00.000001','rationale'=>'Synthetic self']),403);medicineReject(fn()=>$s->grantAuthority(['user_id'=>2,'capability'=>'administrator','scope'=>'catalog','expires_at'=>null,'rationale'=>'Synthetic delegate']),422);medicineCheck(count($r->grantRows)===1);});
medicineTest('audit fault rolls back with safe failure and no secrets',function(){[$r,,,,,$c]=ownerWebFixture();$r->fault=true;ownerWebInvoke($c,'POST',ownerWebPost());medicineCheck(http_response_code()===503&&$r->grantRows===[]&&$r->eventRows===[]);medicineCheck(!str_contains(serialize($c->rendered),'PRIVATE')&&!str_contains(serialize($c->rendered),'PDO'));});
medicineTest('unexpected service exception never leaks',function(){[,,$a]=ownerWebFixture();$s=new class extends App\Services\MedicineGovernanceService {public function __construct(){}public function bootstrapOwnerFromSession():array {throw new RuntimeException('PRIVATE_SQL_PASSWORD_PATH_STACK');}};$c=new OwnerWebController($s,$a,new OwnerWebSession());ownerWebInvoke($c,'POST',ownerWebPost());medicineCheck(http_response_code()===500&&!str_contains(serialize($c->rendered),'PRIVATE'));medicineCheck(!str_contains(implode(' ',App\Core\Logger::$messages),'PASSWORD'));});
$failed=array_values(array_filter($medicineCases,fn($c)=>$c['status']==='FAIL'));ob_end_clean();echo json_encode(['cases'=>count($medicineCases),'assertions'=>$medicineAssertions,'failures'=>$failed,'database_connections'=>0],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;exit($failed?1:0);
}

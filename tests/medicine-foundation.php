<?php
declare(strict_types=1);

// Database-free service fixtures. No application bootstrap, sessions or normal DB.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) { $path = dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php'; if (is_file($path)) { require_once $path; } }
});

use App\Services\MedicineCanonicalizer;
use App\Services\MedicinePersistenceException;
use App\Services\MedicineIdentityService;
use App\Repositories\MedicineRepository;

class MedicineTestAuth extends App\Services\AuthService
{
    public function __construct(public ?int $actorId=1) {}
    public function id(): ?int { return $this->actorId; }
}
class MedicineTestAuthority extends App\Services\MedicineAuthorizationService
{
    public function __construct(public array $grants=[1=>true]) {}
    public function requireCatalog(int $actor,string $action):void { if (!($this->grants[$actor]??false)) { throw new MedicinePersistenceException('Medicine catalog access denied.',403); } }
    // Phase 3.5 synthetic fixtures deliberately isolate structural persistence;
    // Phase 3.8 has separate tests of the real governance eligibility boundary.
    public function requireCurrent(string $scope,string $id):void {}
    // Structural fixtures stay isolated; policy/pilot integration has its own suite.
    public function preparePilot(string $scope,array $references):void {}
    public function recordCreation(string $scope,string $id,int $actor):void {}
    public function requirePilotTarget(string $scope,string $id):void {}
}
class MedicineMemoryRepository extends MedicineRepository
{
    public array $data=[];
    public ?string $fault=null;
    public array $access=[11=>[1=>'owner',2=>'editor',4=>'viewer']];
    public function __construct() {}
    public function transaction(callable $action):mixed { $snapshot=serialize($this->data);try{return $action();}catch(Throwable $e){$this->data=unserialize($snapshot);throw $e;} }
    public function actor(int $id,bool $lock=false):?array { return in_array($id,[1,2,3,4],true)?['id'=>$id,'status'=>$id===3?'inactive':'active']:null; }
    public function lockProfile(int $profile,int $actor):void {}
    public function reference(string $tag,string $id,bool $lock=false):?array { foreach($this->data['refs'] as $r){if((string)($r[$tag==='S'?'substance_id':'form_id']??'')===$id){return $r;}}return null; }
    public function substance(string $id,bool $lock=false):?array { return $this->data['substances'][$id]??null; }
    public function form(string $id,bool $lock=false):?array { return $this->data['forms'][$id]??null; }
    public function identity(string $id,bool $lock=false):?array { return $this->data['identities'][$id]??null; }
    public function identityByHash(string $hash):?array { foreach($this->data['identities']??[] as $r){if($r['composition_hash']===$hash){return $r;}}return null; }
    public function ingredients(string $identity,bool $lock=false):array { $rows=[];foreach($this->data['ingredients']??[] as $r){if($r['medicine_identity_id']===$identity){$ref=$this->data['refs'][$r['chemical_ref_id']];$rows[]=$r+['substance_id'=>$ref['substance_id'],'form_id'=>$ref['form_id']];}}usort($rows,fn($a,$b)=>$a['display_order']<=>$b['display_order']);return $rows; }
    public function presentation(string $id,bool $lock=false):?array { return $this->data['presentations'][$id]??null; }
    public function presentationByHash(string $hash):?array { foreach($this->data['presentations']??[] as $r){if($r['presentation_hash']===$hash){return $r;}}return null; }
    public function strengths(string $id,bool $lock=false):array { return array_values(array_filter($this->data['strengths']??[],fn($r)=>$r['medicine_presentation_id']===$id)); }
    private function put(string $table,string $pk,array $row):string { $id=(string)(count($this->data[$table]??[])+101);$this->data[$table][$id]=$row+[$pk=>$id,'key_version'=>1,'identity_status'=>'candidate','metadata_revision'=>1,'verified_by'=>null,'verified_at'=>null,'verification_reference'=>null,'retired_by'=>null,'retired_at'=>null,'retirement_reason'=>null];if($this->fault===$table){throw new PDOException('PRIVATE_SQL_PATH_SECRET');}return $id; }
    public function insertReference(array $row):string { return $this->put('refs','chemical_ref_id',$row); }
    public function insertIdentity(array $row):string { return $this->put('identities','medicine_identity_id',$row); }
    public function insertIngredient(array $row):string { return $this->put('ingredients','medicine_ingredient_id',$row); }
    public function insertPresentation(array $row):string { return $this->put('presentations','medicine_presentation_id',$row); }
    public function insertStrength(array $row):void { $this->put('strengths','fixture_id',$row); }
    public function medication(int $id,bool $lock=false):?array { return $this->data['medications'][$id]??null; }
    public function link(int $id,bool $lock=false):?array { return $this->data['links'][$id]??null; }
    public function selections(int $id):array { return $this->data['selections'][$id]??[]; }
    public function clearSelections(int $id):void { $this->data['selections'][$id]=[]; }
    public function deleteLink(int $id):void { unset($this->data['links'][$id]); }
    public function saveLink(array $row,bool $exists):void { $id=$row['health_medicine_id'];$this->data['links'][$id]=$row;if($this->fault==='links'){throw new PDOException('PRIVATE_SQL_PATH_SECRET');} }
    public function insertSelection(array $row):void { $this->data['selections'][$row['health_medicine_id']][]=array_intersect_key($row,array_flip(['chemical_ref_id','display_order','observed_name']));if($this->fault==='selections'){throw new PDOException('PRIVATE_SQL_PATH_SECRET');} }
}
class MedicineTestProfiles extends App\Repositories\HealthProfileRepository
{
    public function __construct(private MedicineMemoryRepository $memory) {}
    public function findAccessible(int $profileId,int $userId):?array { $role=$this->memory->access[$profileId][$userId]??null;return $role===null?null:['id'=>$profileId,'role'=>$role,'status'=>'active','membership_status'=>'active']; }
}
function medicineFixture():array {
    $repo=new MedicineMemoryRepository();$auth=new MedicineTestAuth();$policy=new MedicineTestAuthority();$rules=new MedicineCanonicalizer();
    $verified=['identity_status'=>'verified','metadata_revision'=>1,'verified_by'=>2,'verified_at'=>'2026-10-09 00:00:00.000001','verification_reference'=>'Synthetic identity evidence'];
    $repo->data=['refs'=>['51'=>['chemical_ref_id'=>'51','substance_id'=>'2','form_id'=>null]+$verified,'52'=>['chemical_ref_id'=>'52','substance_id'=>'10','form_id'=>null]+$verified,'53'=>['chemical_ref_id'=>'53','substance_id'=>null,'form_id'=>'30']+$verified],
        'substances'=>['2'=>['substance_id'=>'2','is_active'=>1],'3'=>['substance_id'=>'3','is_active'=>1],'10'=>['substance_id'=>'10','is_active'=>1]],'forms'=>['30'=>['form_id'=>'30','substance_id'=>'3','is_active'=>1]],'identities'=>[],'ingredients'=>[],'presentations'=>[],'strengths'=>[],'links'=>[],'selections'=>[],'medications'=>[]];
    foreach([701=>11,702=>12] as $id=>$profile){$repo->data['medications'][$id]=['id'=>$id,'health_profile_id'=>$profile,'brand_name'=>'Original raw wording','generic_name'=>'Ambiguous text','strength'=>'Original strength','dose'=>'Original dose','frequency'=>null,'route'=>null,'status'=>'current','started_on'=>null,'stopped_on'=>null,'prescribed_by'=>null,'notes'=>'Private observation'];}
    $service=new MedicineIdentityService($repo,$rules,$auth,new MedicineTestProfiles($repo),$policy);return [$repo,$service,$auth,$policy,$rules];
}
function medicinePresentation(array $refs=['51','52'],string $amount='1'):array { return ['dosage_form_code'=>'tablet','route_code'=>'oral','release_code'=>'immediate','strengths'=>array_map(fn($id)=>['chemical_ref_id'=>$id,'ratio_numerator'=>$amount,'ratio_denominator'=>'3','numerator_unit_code'=>'g','denominator_unit_code'=>'unit'],$refs)]; }
function medicineAssociation(MedicineIdentityService $service,array $input):array { $snapshot=$service->medicationAssociation(11,701);return $input+['expected_record_digest'=>$snapshot['record_digest'],'expected_link_revision'=>$snapshot['link_revision']]; }
$medicineCases=[];$medicineAssertions=0;
function medicineCheck(bool $condition,string $message='Assertion failed'):void { global $medicineAssertions;$medicineAssertions++;if(!$condition){throw new RuntimeException($message);} }
function medicineReject(callable $action,int $code=422):MedicinePersistenceException { try{$action();}catch(MedicinePersistenceException $e){medicineCheck($e->getCode()===$code,'Wrong safe error code '.$e->getCode());medicineCheck(!str_contains($e->getMessage(),'PRIVATE_'),'Private diagnostic disclosed');return $e;}throw new RuntimeException('Invalid input accepted'); }
function medicineTest(string $name,callable $action):void { global $medicineCases;try{$action();$medicineCases[]=['name'=>$name,'status'=>'PASS'];}catch(Throwable $e){$medicineCases[]=['name'=>$name,'status'=>'FAIL','error'=>$e->getMessage()];} }

if(defined('MEDICINE_TEST_LIBRARY')){return;}
$rules=new MedicineCanonicalizer();
medicineTest('fixed composition serialization and numeric/tag ordering',function()use($rules){$r=$rules->composition([['tag'=>'F','id'=>'1'],['tag'=>'S','id'=>'10'],['tag'=>'S','id'=>'2']]);medicineCheck($r['key']==='{"v":1,"ingredients":[["S","2"],["S","10"],["F","1"]]}');});
foreach([[],[['tag'=>'S','id'=>'2'],['tag'=>'S','id'=>'02']],array_fill(0,17,['tag'=>'S','id'=>'2'])] as $i=>$input){medicineTest('composition invalid '.$i,fn()=>medicineReject(fn()=>$rules->composition($input)));}
foreach([null,0,-1,1.5,true,[], '1e2','+2',' 2','9223372036854775808'] as $i=>$id){medicineTest('identifier rejection '.$i,fn()=>medicineReject(fn()=>$rules->id($id)));}
medicineTest('identifier exact beyond JavaScript integer precision',fn()=>medicineCheck($rules->id('9007199254740993')==='9007199254740993'));
medicineTest('exact decimal/unit equivalence',function()use($rules){$a=$rules->strength(['numerator_quantity'=>'0001.50000','numerator_unit'=>'mg','denominator_quantity'=>'3.0','denominator_unit'=>'mL']);$b=$rules->strength(['numerator_quantity'=>'0.0015','numerator_unit'=>'g','denominator_quantity'=>'0.003','denominator_unit'=>'L']);medicineCheck($a===$b && $a['ratio_numerator']==='1' && $a['ratio_denominator']==='2000');});
medicineTest('microgram explicit aliases',function()use($rules){foreach(['ug','µg','μg'] as $u){$r=$rules->strength(['numerator_quantity'=>'1','numerator_unit'=>$u,'denominator_quantity'=>'1','denominator_unit'=>'unit']);medicineCheck($r['ratio_denominator']==='1000000');}});
medicineTest('exact repeated fraction and reduced ratio',function()use($rules){$r=$rules->strength(['ratio_numerator'=>'2','ratio_denominator'=>'6','numerator_unit_code'=>'g','denominator_unit_code'=>'unit']);medicineCheck($r['ratio_numerator']==='1' && $r['ratio_denominator']==='3');});
foreach(['0','-1','1.5','1e2','+1','01',str_repeat('9',39),1,1.0,null,[]] as $i=>$n){medicineTest('coefficient rejection '.$i,fn()=>medicineReject(fn()=>$rules->strength(['ratio_numerator'=>$n,'ratio_denominator'=>'1','numerator_unit_code'=>'g','denominator_unit_code'=>'unit'])));}
medicineTest('38-digit coefficient boundary',fn()=>medicineCheck($rules->strength(['ratio_numerator'=>str_repeat('9',38),'ratio_denominator'=>'1','numerator_unit_code'=>'g','denominator_unit_code'=>'mL'])['ratio_numerator']===str_repeat('9',38)));
foreach(['0','-1','NaN','INF','1e3','1.',' .1','1000000000000','0.0000000000001',1.5,[],null] as $i=>$n){medicineTest('decimal rejection '.$i,fn()=>medicineReject(fn()=>$rules->strength(['numerator_quantity'=>$n,'numerator_unit'=>'g','denominator_quantity'=>'1','denominator_unit'=>'unit'])));}
medicineTest('12+12 exact input boundary',function()use($rules){$r=$rules->strength(['numerator_quantity'=>'999999999999.999999999999','numerator_unit'=>'g','denominator_quantity'=>'1','denominator_unit'=>'unit']);medicineCheck($r['ratio_numerator']==='999999999999999999999999' && $r['ratio_denominator']==='1000000000000');});
medicineTest('large exact cross-unit ratio remains within coefficient envelope',function()use($rules){$r=$rules->strength(['numerator_quantity'=>'999999999999.999999999989','numerator_unit'=>'g','denominator_quantity'=>'0.000000000019','denominator_unit'=>'ug']);medicineCheck($r['ratio_numerator']==='52631578947368421052631000000' && $r['ratio_denominator']==='1');});
foreach(['G','MG','g ','IU','percent','unknown',[]] as $i=>$unit){medicineTest('unsupported unit '.$i,fn()=>medicineReject(fn()=>$rules->strength(['numerator_quantity'=>'1','numerator_unit'=>$unit,'denominator_quantity'=>'1','denominator_unit'=>'unit'])));}
medicineTest('fractional dosage unit denominator rejected',fn()=>medicineReject(fn()=>$rules->strength(['numerator_quantity'=>'1','numerator_unit'=>'g','denominator_quantity'=>'1.5','denominator_unit'=>'unit'])));
medicineTest('dose cannot enter strength',fn()=>medicineReject(fn()=>$rules->strength(['ratio_numerator'=>'1','ratio_denominator'=>'1','numerator_unit_code'=>'g','denominator_unit_code'=>'unit','dose'=>'private'])));
medicineTest('NFC text normalization preserves chemical punctuation',fn()=>medicineCheck($rules->text("  Cafe\u{0301} (A)-2  ",500)==='Café (A)-2'));
foreach([['ingredients'=>[]],['ingredients'=>'name'],['ingredients'=>[['name'=>'guess']]],['ingredients'=>[['substance_id'=>'2','form_id'=>'30']]],['ingredients'=>[['substance_id'=>'999']]],['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'02']]],['ingredients'=>[['substance_id'=>1.2]]],['ingredients'=>[['substance_id'=>'2']],'brand_name'=>'Brand']] as $i=>$input){medicineTest('service invalid composition '.$i,function()use($input){[$r,$s]=medicineFixture();$before=serialize($r->data);medicineReject(fn()=>$s->createMedicine($input));medicineCheck(serialize($r->data)===$before);});}
medicineTest('single ingredient persistence stays candidate',function(){[$r,$s]=medicineFixture();$v=$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]);medicineCheck($v['created'] && $v['status']==='candidate' && count($r->data['ingredients'])===1);});
medicineTest('combination reorder and changed label reuse exact owner',function(){[$r,$s]=medicineFixture();$a=$s->createMedicine(['ingredients'=>[['substance_id'=>'10'],['substance_id'=>'2']],'display_name'=>'First']);$b=$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'10']],'display_name'=>'Another']);medicineCheck($a['medicine_identity_id']===$b['medicine_identity_id'] && !$b['created'] && count($r->data['ingredients'])===2);});
medicineTest('form reference derives parent, distinct from substance',function(){[$r,$s]=medicineFixture();$v=$s->createMedicine(['ingredients'=>[['form_id'=>'30']]]);medicineCheck(str_contains($r->data['identities'][$v['medicine_identity_id']]['composition_key'],'["F","30"]'));});
medicineTest('two presentations share one composition; exact duplicate reuse',function(){[$r,$s]=medicineFixture();$v=$s->createMedicine(['ingredients'=>[['substance_id'=>'10'],['substance_id'=>'2']],'presentation'=>medicinePresentation()]);$a=$s->createPresentation($v['medicine_identity_id'],medicinePresentation(['52','51']));$b=$s->createPresentation($v['medicine_identity_id'],medicinePresentation(['51','52'],'2'));medicineCheck(!$a['created'] && $b['created'] && count($r->data['identities'])===1 && count($r->data['presentations'])===2 && count($r->data['strengths'])===4);});
foreach(['dosage_form_code'=>'unknown','route_code'=>'cutaneous','release_code'=>'unknown','strengths'=>[]] as $field=>$value){medicineTest('invalid presentation '.$field,function()use($field,$value){[$r,$s]=medicineFixture();medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'10']],'presentation'=>array_replace(medicinePresentation(),[$field=>$value])]));medicineCheck($r->data['identities']===[] && $r->data['presentations']===[]);});}
foreach(['identities','ingredients','presentations','strengths'] as $stage){medicineTest('rollback after '.$stage,function()use($stage){[$r,$s]=medicineFixture();$r->fault=$stage;$before=serialize($r->data);medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'10']],'presentation'=>medicinePresentation()]),503);medicineCheck(serialize($r->data)===$before);});}
foreach(['candidate','retired','unknown'] as $state){medicineTest('unusable adapter '.$state,function()use($state){[$r,$s]=medicineFixture();$r->data['refs']['51']['identity_status']=$state;medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]),409);});}
medicineTest('inactive underlying substance rejected',function(){[$r,$s]=medicineFixture();$r->data['substances']['2']['is_active']=0;medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]));});
medicineTest('retired duplicate owner not restored or bypassed',function(){[$r,$s]=medicineFixture();$a=$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]);$r->data['identities'][$a['medicine_identity_id']]['identity_status']='retired';medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]),409);medicineCheck(count($r->data['identities'])===1);});
medicineTest('digest collision never overwrites owner',function(){[$r,$s]=medicineFixture();$a=$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]);$r->data['identities'][$a['medicine_identity_id']]['composition_key']='{"v":1,"ingredients":[["S","999"]]}';medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]),409);});
foreach([null,3,2] as $actor){medicineTest('catalog denies actor '.var_export($actor,true),function()use($actor){[$r,$s,$auth]=medicineFixture();$auth->actorId=$actor;medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]),403);});}
medicineTest('production catalog authority denies even active owner',function(){ $authority=(new ReflectionClass(App\Services\MedicineAuthorizationService::class))->newInstanceWithoutConstructor();medicineReject(fn()=>$authority->requireCatalog(1,'create_identity'),403);});
medicineTest('private selected IDs and original text preserved',function(){[$r,$s]=medicineFixture();$before=$r->data['medications'];$v=$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'10'],['substance_id'=>'2']]]));medicineCheck($v['link_revision']===1 && array_column($r->data['selections'][701],'chemical_ref_id')===['51','52'] && $r->data['medications']===$before && $r->data['identities']===[]);});
foreach([['actor'=>null,'profile'=>11,'med'=>701],['actor'=>3,'profile'=>11,'med'=>701],['actor'=>4,'profile'=>11,'med'=>701],['actor'=>1,'profile'=>12,'med'=>702],['actor'=>1,'profile'=>11,'med'=>702]] as $i=>$input){medicineTest('private authorization '.$i,function()use($input){[$r,$s,$a]=medicineFixture();$token=medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'2']]]);$a->actorId=$input['actor'];medicineReject(fn()=>$s->setMedicationAssociation($input['profile'],$input['med'],$token),403);});}
medicineTest('editor may save selections but cannot create catalog',function(){[$r,$s,$a]=medicineFixture();$a->actorId=2;$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'2']]]));medicineCheck($r->data['links'][701]['updated_by']===2);medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]),403);});
medicineTest('candidate identity cannot link privately',function(){[$r,$s]=medicineFixture();$v=$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]);medicineReject(fn()=>$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'composition','medicine_identity_id'=>$v['medicine_identity_id']])),409);});
foreach(['links','selections'] as $stage){medicineTest('private rollback '.$stage,function()use($stage){[$r,$s]=medicineFixture();$r->fault=$stage;$before=serialize($r->data);medicineReject(fn()=>$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'2']]])),503);medicineCheck(serialize($r->data)===$before);});}
medicineTest('raw record digest and revision stale rejection',function(){[$r,$s]=medicineFixture();$input=medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'2']]]);$s->setMedicationAssociation(11,701,$input);medicineReject(fn()=>$s->setMedicationAssociation(11,701,$input),409);$fresh=medicineAssociation($s,['association_mode'=>'text']);$r->data['medications'][701]['notes']='Changed privately';medicineReject(fn()=>$s->setMedicationAssociation(11,701,$fresh),409);});
medicineTest('delete/recreate association ABA token rejected',function(){[$r,$s]=medicineFixture();$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'2']]]));$old=medicineAssociation($s,['association_mode'=>'text']);$s->setMedicationAssociation(11,701,$old);$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'10']]]));medicineReject(fn()=>$s->setMedicationAssociation(11,701,$old),409);});
medicineTest('text fallback removes only sidecar and private selections',function(){[$r,$s]=medicineFixture();$original=$r->data['medications'];$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'2']]]));$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'text']));medicineCheck(!isset($r->data['links'][701]) && $r->data['selections'][701]===[] && $r->data['medications']===$original);});
medicineTest('owner spoofing fields rejected',function(){[$r,$s]=medicineFixture();medicineReject(fn()=>$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'text','created_by'=>2])));});
medicineTest('register reference never verifies or creates a substance',function(){[$r,$s]=medicineFixture();$r->data['substances']['20']=['substance_id'=>'20','is_active'=>1];$v=$s->registerChemicalReference(['substance_id'=>'20']);medicineCheck($v['status']==='candidate' && $v['created'] && $r->data['refs'][$v['chemical_ref_id']]['verified_by']===null && count($r->data['substances'])===4);});
medicineTest('no workflow or public exposure methods',function(){medicineCheck(!method_exists(MedicineIdentityService::class,'verify') && !method_exists(MedicineIdentityService::class,'publish'));});
medicineTest('inactive form parent rejected',function(){[$r,$s]=medicineFixture();$r->data['substances']['3']['is_active']=0;medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['form_id'=>'30']]]));});
medicineTest('verified adapter incomplete provenance rejected',function(){[$r,$s]=medicineFixture();$r->data['refs']['51']['verified_at']='2026-02-30 00:00:00.000001';medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]),409);});
medicineTest('stored partial identity rejected',function(){[$r,$s]=medicineFixture();$v=$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'10']]]);array_pop($r->data['ingredients']);medicineReject(fn()=>$s->createPresentation($v['medicine_identity_id'],medicinePresentation()),409);});
medicineTest('stored non-reduced strength rejected',function(){[$r,$s]=medicineFixture();$v=$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'10']],'presentation'=>medicinePresentation()]);$key=array_key_first($r->data['strengths']);$r->data['strengths'][$key]['ratio_numerator']='2';$r->data['strengths'][$key]['ratio_denominator']='6';medicineReject(fn()=>$s->createPresentation($v['medicine_identity_id'],medicinePresentation()),409);});
medicineTest('duplicate presentation uses current locking lookup despite stale discovery',function(){[$r,$s,$auth,$policy,$rules]=medicineFixture();$v=$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'10']],'presentation'=>medicinePresentation()]);$current=new class extends MedicineMemoryRepository {public function presentation(string $id,bool $lock=false):?array{return $lock?parent::presentation($id,true):null;}};$current->data=$r->data;$service=new MedicineIdentityService($current,$rules,$auth,new MedicineTestProfiles($current),$policy);$p=$service->createPresentation($v['medicine_identity_id'],medicinePresentation());medicineCheck(!$p['created'] && $p['medicine_presentation_id']===$v['presentation']['medicine_presentation_id']);});
$failed=array_values(array_filter($medicineCases,fn($c)=>$c['status']==='FAIL'));
echo json_encode(['distinct_cases'=>count($medicineCases),'assertions'=>$medicineAssertions,'failures'=>$failed],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
exit($failed?1:0);

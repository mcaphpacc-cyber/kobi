<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('MEDICINE_POLICY_LIBRARY', true);
require __DIR__.'/medicine-policy.php';

class MedicineProvisionalMemory extends MedicineV1Memory
{
    public array $provisionalRows=[];
    public bool $provisionalFault=false;
    public function reference(string $tag,string $id,bool $lock=false):?array {return $this->inner->reference($tag,$id,$lock);}
    public function provisionalLatest(string $id):?array {return isset($this->provisionalRows[$id])?end($this->provisionalRows[$id]):null;}
    public function appendProvisional(array $row):void {$this->provisionalRows[$row['substance_id']][$row['revision']]=$row;if($this->provisionalFault){throw new PDOException('PRIVATE_PROVISIONAL_SQL_PATH');}}
    public function transaction(callable $action):mixed {$before=$this->provisionalRows;try{return parent::transaction($action);}catch(Throwable $e){$this->provisionalRows=$before;throw $e;}}
}
function medicineProvisionalCandidates():array {return ['15'=>'Acetazolamide','54'=>'Allopurinol','319'=>'Carbamazepine','481'=>'Colchicine','2064'=>'Topiramate'];}
function medicineProvisionalFixture():array {
    [$old]=medicineV1Fixture();$r=new MedicineProvisionalMemory($old->inner);$r->grantRows=$old->grantRows;$r->eventRows=$old->eventRows;
    foreach(medicineProvisionalCandidates() as $id=>$name){$r->inner->data['substances'][$id]=['substance_id'=>(string)$id,'canonical_name'=>$name,'identity_status'=>'CANDIDATE','identity_type'=>'UNKNOWN','is_active'=>1,'is_searchable'=>1,'description'=>null];}
    $p=new MedicineV1Policy(['owner_user_ids'=>[8],'pilot_enabled'=>false,'pilot_internal_enabled'=>false,'evidence_sources'=>[]]);
    [$s,$a,$auth,$elig]=medicineGovernanceObjects($r,$p);return [$r,$s,$a,$auth,$elig,$p];
}
function medicineProvisionalInput(App\Services\MedicineGovernanceService $s,string $id):array {
    $token=$s->inspectProvisionalIdentityDraft($id);unset($token['target']);return ['substance_id'=>$id,'rationale'=>'SYNTHETIC preparation only']+$token;
}
if(defined('MEDICINE_PROVISIONAL_LIBRARY')){return;}

foreach(medicineProvisionalCandidates() as $id=>$name){medicineTest('separate unapproved revision and exact evidence for '.$id,function()use($id,$name){
    [$r,$s,$a,$auth,$elig]=$f=medicineProvisionalFixture();$id=(string)$id;$before=serialize($r->inner->data);$events=$r->eventRows;
    $input=medicineProvisionalInput($s,$id);$result=$s->prepareProvisionalIdentityDraft($input);$row=$r->provisionalLatest($id);$facts=json_decode($row['snapshot_json'],true);$evidence=json_decode($row['evidence_json'],true);
    medicineCheck($result['target']==='provisional:S:'.$id&&$result['state']==='draft'&&$result['revision']===1);
    foreach(['verified','pilot_eligible','pilot_enrolled','published'] as $key){medicineCheck($result[$key]===false);}
    medicineCheck($result['reviewer_id']===null&&$result['approval']===null);
    medicineCheck($facts['proposal']['canonical_name']===$name&&$facts['registry']['target']['identity_type']==='UNKNOWN');
    medicineCheck($facts['proposal']['chemical_form']===null&&$facts['proposal']['stereochemistry']===null&&$facts['proposal']['chemical_form_status']==='PENDING_REVIEW'&&$facts['proposal']['stereochemistry_status']==='PENDING_REVIEW');
    medicineCheck($facts['chemical_ref_id']===null&&$facts['canonical_identity_id']===null&&$facts['reviewer_id']===null);
    medicineCheck($row['snapshot_hash']===hash('sha256',$row['snapshot_json'],true)&&$row['evidence_hash']===hash('sha256',$row['evidence_json'],true));
    $package=(new App\Services\MedicineProvisionalIdentity())->package($id);
    foreach($evidence as $i=>$source){medicineCheck($source['source']['source_sha256']===$package['sources'][$i]['source_sha256']);medicineCheck($source['appraisal_status']==='NOT_APPRAISED'&&$source['target']===$result['target']&&$source['revision']==='1'&&$source['snapshot_sha256']===bin2hex($row['snapshot_hash']));}
    medicineCheck($evidence[0]['proposed_tier']==='1'&&$evidence[1]['proposed_tier']==='4');
    medicineCheck($before===serialize($r->inner->data)&&$events===$r->eventRows&&$r->pilotRows===[]);
    $again=$s->prepareProvisionalIdentityDraft($input);medicineCheck($again['created']===false&&count($r->provisionalRows[$id])===1);
    $a->actorId=2;medicineReject(fn()=>$s->verify('provisional_identity',$result['target'],[]),403);
    medicineReject(fn()=>$s->verify('composition',$result['target'],['expected_revision'=>1,'expected_snapshot_hash'=>str_repeat('0',64),'rationale'=>'Forged','evidence'=>medicineSyntheticEvidence()]),422);
    $a->actorId=8;medicineReject(fn()=>$s->enrollPilot('composition',$result['target'],['expected_revision'=>1,'expected_snapshot_hash'=>str_repeat('0',64),'expected_enrollment_event_id'=>'0','rationale'=>'Forged']),422);
    $a->actorId=1;medicineReject(fn()=>$s->internalPilot('composition',$result['target']),422);
});}
foreach(['1','2224','15 OR 1=1','15; DROP TABLE users','-1','0'] as $id){medicineTest('invalid or unselected registry '.$id,function()use($id){[$r,$s]=medicineProvisionalFixture();medicineReject(fn()=>$s->inspectProvisionalIdentityDraft($id),in_array($id,['1','2224'],true)?403:422);medicineCheck($r->provisionalRows===[]);});}
foreach(['reviewer_id','qualification_reference','approval','evidence','locator','source','version','skipVerification','canonical_identity_id','chemical_form','kobi_substances','health_medicine_id'] as $field){medicineTest('reject client-controlled scientific/authority/SQL field '.$field,function()use($field){[$r,$s]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');$input[$field]="x'; DROP TABLE users; --";medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),422);medicineCheck($r->provisionalRows===[]);});}
foreach([0,2,7,8] as $actor){medicineTest('unauthorized draft actor '.$actor,function()use($actor){[$r,$s,$a]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');$a->actorId=$actor;medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),403);});}
foreach(['scope','expired','revoked'] as $case){medicineTest('contributor grant fails '.$case,function()use($case){[$r,$s]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');if($case==='scope'){$r->grantRows['1']['scope']='presentation';}elseif($case==='expired'){$r->grantRows['1']['expires_at']='2020-01-01 00:00:00.000001';}else{$r->grantRows['1']['revoked_at']='2026-01-01 00:00:00.000001';}medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),403);});}
medicineTest('editor preparation with scoped grant, no pilot intake permission',function(){[$r,$s,$a]=medicineProvisionalFixture();$a->actorId=4;$result=$s->prepareProvisionalIdentityDraft(medicineProvisionalInput($s,'54'));medicineCheck($result['state']==='draft'&&$r->provisionalLatest('54')['actor_id']===4);});
medicineTest('registry drift stale tokens, new revision and old evidence history',function(){[$r,$s]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');$s->prepareProvisionalIdentityDraft($input);$old=$r->provisionalLatest('15');$r->inner->data['substances']['15']['description']='Changed synthetic facts';medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),409);$fresh=medicineProvisionalInput($s,'15');$s->prepareProvisionalIdentityDraft($fresh);medicineCheck($r->provisionalLatest('15')['revision']===2&&$r->provisionalRows['15'][1]===$old);$fresh['expected_revision']=0;medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($fresh),409);});
foreach(['expected_registry_sha256','expected_package_sha256','expected_revision'] as $field){medicineTest('stale input '.$field,function()use($field){[$r,$s]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');$input[$field]=$field==='expected_revision'?99:str_repeat('0',64);medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),409);});}
medicineTest('activity round trip changes snapshot even when current row matches',function(){[$r,$s]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');$r->registryActivity('S','15',0);$r->registryActivity('S','15',1);medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),409);});
foreach(['withdrawn','invalidated'] as $state){medicineTest('terminal '.$state.' history cannot be restored',function()use($state){[$r,$s,$a]=medicineProvisionalFixture();$s->prepareProvisionalIdentityDraft(medicineProvisionalInput($s,'15'));$a->actorId=4;$s->closeProvisionalIdentityDraft('15',['expected_revision'=>1,'state'=>$state,'rationale'=>'SYNTHETIC terminal safety action']);$old=$r->provisionalRows;$a->actorId=1;medicineReject(fn()=>$s->prepareProvisionalIdentityDraft(medicineProvisionalInput($s,'15')),409);$a->actorId=4;medicineReject(fn()=>$s->closeProvisionalIdentityDraft('15',['expected_revision'=>2,'state'=>'draft','rationale'=>'Restore']),409);medicineCheck($r->provisionalRows===$old);});}
medicineTest('closure remains possible after registry becomes inactive',function(){[$r,$s,$a]=medicineProvisionalFixture();$s->prepareProvisionalIdentityDraft(medicineProvisionalInput($s,'15'));$r->registryActivity('S','15',0);$a->actorId=4;$s->closeProvisionalIdentityDraft('15',['expected_revision'=>1,'state'=>'withdrawn','rationale'=>'SYNTHETIC inactive closure']);medicineCheck($r->provisionalLatest('15')['state']==='withdrawn');});
medicineTest('audit failure rolls back and suppresses storage diagnostics',function(){[$r,$s]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');$r->provisionalFault=true;$e=medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),503);medicineCheck($r->provisionalRows===[]&&!str_contains($e->getMessage(),'SQL_PATH'));});
medicineTest('malformed retry revisions fail without coercion',function(){foreach([[],true,'0 OR 1=1','01',-1] as $value){[$r,$s]=medicineProvisionalFixture();$input=medicineProvisionalInput($s,'15');$s->prepareProvisionalIdentityDraft($input);$input['expected_revision']=$value;medicineReject(fn()=>$s->prepareProvisionalIdentityDraft($input),409);}});
medicineTest('unauthorized closure and stale closure cannot change a revision',function(){[$r,$s,$a]=medicineProvisionalFixture();$s->prepareProvisionalIdentityDraft(medicineProvisionalInput($s,'15'));$input=['expected_revision'=>1,'state'=>'withdrawn','rationale'=>'SYNTHETIC closure'];medicineReject(fn()=>$s->closeProvisionalIdentityDraft('15',$input),403);$a->actorId=4;$input['expected_revision']=0;medicineReject(fn()=>$s->closeProvisionalIdentityDraft('15',$input),409);medicineCheck($r->provisionalLatest('15')['state']==='draft');});
medicineTest('canonical withdrawn or invalidated history cannot use provisional preparation as recovery',function(){foreach(['withdrawn','invalidated'] as $state){[$r,$s]=medicineProvisionalFixture();$r->inner->data['refs']['51']=['chemical_ref_id'=>'51','substance_id'=>'15','form_id'=>null];$r->eventRows['99']=['id'=>'99','scope'=>'chemical','subject'=>'51','new_state'=>$state];medicineReject(fn()=>$s->inspectProvisionalIdentityDraft('15'),409);}});
$failures=array_values(array_filter($medicineCases,fn($r)=>$r['status']==='FAIL'));
echo json_encode(['cases'=>count($medicineCases),'assertions'=>$medicineAssertions,'failures'=>$failures,'database_connections'=>0],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
exit($failures?1:0);

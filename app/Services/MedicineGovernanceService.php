<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\MedicineGovernanceRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDOException;
use Throwable;

/** Controlled governance commands; no seed authority or public publication. */
class MedicineGovernanceService
{
    public function __construct(private MedicineGovernanceRepository $repository,private MedicineAuthorizationService $authority,private MedicineEligibilityService $eligibility,private MedicineGovernancePolicy $policy,private MedicineCanonicalizer $rules,private AuthService $auth) {}
    private function now():string {return (new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');}
    private function actor():int {$actor=$this->auth->id();if(!$actor||($this->repository->actor($actor,true)['status']??null)!=='active'){throw new MedicinePersistenceException('Medicine governance access denied.',403);}return $actor;}
    private function command(callable $action):mixed
    {
        try{return $this->repository->transaction($action);}catch(MedicinePersistenceException $e){throw $e;}catch(PDOException $e){throw new MedicinePersistenceException('Medicine governance storage is temporarily unavailable.',503);}catch(Throwable $e){throw new MedicinePersistenceException('Medicine governance operation failed.',500);}
    }
    private function text(mixed $value):string {$text=$this->rules->text($value,4000);if($text===null){throw new MedicinePersistenceException('Medicine decision rationale is required.',422);}return $text;}
    private function credentialText(mixed $value):string {
        if(!is_string($value)||trim($value)===''||strlen($value)>512||preg_match('/[<>\x00-\x1f\x7f\x27\x22;]/',$value)){throw new MedicinePersistenceException('Medicine credential field is invalid.',422);}return trim($value);
    }
    /** Administrator recording is pending only; it creates no reviewer authority. */
    public function recordReviewerQualification(array $input):array {
        return $this->command(function()use($input){$actor=$this->actor();$this->authority->requireOperator($actor,'administrator');
            $fields=['user_id','expected_revision','scope','profession','registration','jurisdiction','authority','renewal_due','rationale'];$this->rules->fields($input,$fields,$fields);
            $user=(int)$this->rules->id($input['user_id']);if(($this->repository->actor($user,true)['status']??null)!=='active'){throw new MedicinePersistenceException('Reviewer subject is unavailable.',403);}
            if(!in_array($input['profession'],['doctor','pharmacist'],true)||!in_array($input['scope'],['chemical','composition','presentation','catalog'],true)){throw new MedicinePersistenceException('Reviewer profession or scope is invalid.',422);}
            $latest=$this->repository->qualificationLatest($user);$this->revision($input['expected_revision'],(int)($latest['revision']??0));
            if($input['renewal_due']!==null&&$this->policy->date($input['renewal_due'])<=$this->now()){throw new MedicinePersistenceException('Credential renewal is overdue.',422);}
            $credential=['profession'=>$input['profession'],'registration'=>$this->credentialText($input['registration']),'jurisdiction'=>$this->credentialText($input['jurisdiction']),'authority'=>$this->credentialText($input['authority']),'renewal_due'=>$input['renewal_due'],'status'=>'pending','verified_at'=>null,'verifier'=>null];
            $s=['user_id'=>$user,'scope'=>$input['scope'],'credential'=>$credential,'verification'=>null,'rationale'=>$this->text($input['rationale'])];
            return $this->qualificationEvent($user,(int)($latest['revision']??0)+1,'pending',$s,$actor);
        });
    }
    /** Verification requires an independently supplied, exact deployment attestation. */
    public function decideReviewerQualification(array $input):array {
        return $this->command(function()use($input){$actor=$this->actor();$this->authority->requireOperator($actor,'administrator');
            $fields=['user_id','expected_revision','state','verification_reference','rationale'];$this->rules->fields($input,$fields,$fields);$user=(int)$this->rules->id($input['user_id']);
            if(($this->repository->actor($user,true)['status']??null)!=='active'||$actor===$user){throw new MedicinePersistenceException('Independent qualification verification is required.',403);}
            $latest=$this->repository->qualificationLatest($user);if(!$latest){throw new MedicinePersistenceException('Reviewer qualification is unavailable.',409);}$this->revision($input['expected_revision'],(int)$latest['revision']);
            $state=$input['state'];$allowed=['pending'=>['verified','rejected'],'verified'=>['suspended','expired'],'rejected'=>[],'suspended'=>[],'expired'=>[]];
            if(!is_string($state)||!in_array($state,$allowed[$latest['state']]??[],true)){throw new MedicinePersistenceException('Qualification transition is unavailable.',409);}
            if(!hash_equals(hash('sha256',$latest['snapshot_json'],true),$latest['snapshot_hash'])){throw new MedicinePersistenceException('Qualification history is inconsistent.',409);}
            $s=json_decode($latest['snapshot_json'],true,32,JSON_THROW_ON_ERROR);
            if($state==='verified'){
                $ref=$this->credentialText($input['verification_reference']);$record=$this->policy->credentialVerification($ref,$user,$s['scope'],bin2hex($latest['snapshot_hash']),$actor);
                $s['credential']['status']='verified';$s['credential']['verified_at']=$record['verified_at'];$s['credential']['verifier']=$actor;
                $s['verification']=['reference'=>$ref,'provenance_sha256'=>$record['provenance_sha256'],'valid_until'=>$record['valid_until']];
                $test=['qualification_reference'=>json_encode($s['credential'],JSON_THROW_ON_ERROR),'qualification_assessed_by'=>$actor,'qualification_scope'=>$s['scope'],'expires_at'=>$s['credential']['renewal_due']??$record['valid_until']];$this->policy->requireQualification($test,$s['scope']);
            }else{
                if($input['verification_reference']!==null){throw new MedicinePersistenceException('Unexpected verification input.',422);}
                if($state==='expired'&&($s['credential']['renewal_due']===null||$s['credential']['renewal_due']>$this->now())&&$s['verification']['valid_until']>$this->now()){throw new MedicinePersistenceException('Qualification has not expired.',409);}
                $s['credential']['status']=$state;
            }
            $s['rationale']=$this->text($input['rationale']);return $this->qualificationEvent($user,(int)$latest['revision']+1,$state,$s,$actor);
        });
    }
    private function qualificationEvent(int $user,int $revision,string $state,array $snapshot,int $actor):array {
        $json=$this->eligibility->json($snapshot);if(strlen($json)>8192){throw new MedicinePersistenceException('Qualification record is too large.',422);}$id=$this->repository->appendQualification(['user_id'=>$user,'revision'=>$revision,'state'=>$state,'snapshot_json'=>$json,'snapshot_hash'=>hash('sha256',$json,true),'actor_id'=>$actor,'occurred_at'=>$this->now()]);
        // Credential values never enter returned journal metadata or error messages.
        return ['qualification_event_id'=>$id,'revision'=>$revision,'state'=>$state,'snapshot_sha256'=>hash('sha256',$json),'authority_granted'=>false];
    }
    /** Private administrator service boundary for a later separately authorized grant. No HTTP listing. */
    public function prepareReviewerAuthority(mixed $userId):array {
        return $this->command(function()use($userId){$actor=$this->actor();$this->authority->requireOperator($actor,'administrator');$user=(int)$this->rules->id($userId);
            if(($this->repository->actor($user,true)['status']??null)!=='active'){throw new MedicinePersistenceException('Reviewer subject is unavailable.',403);}
            $j=$this->repository->qualificationLatest($user);if(!$j||$j['state']!=='verified'){throw new MedicinePersistenceException('Verified reviewer qualification is required.',403);}
            $snapshot=json_decode($j['snapshot_json'],true,32,JSON_THROW_ON_ERROR);$c=$snapshot['credential'];$c+=['qualification_event_id'=>(string)$j['id'],'qualification_sha256'=>bin2hex($j['snapshot_hash'])];
            $reference=json_encode($c,JSON_THROW_ON_ERROR);$this->policy->requireQualificationBinding($this->repository,['user_id'=>$user,'qualification_reference'=>$reference,'qualification_assessed_by'=>$c['verifier'],'qualification_scope'=>$snapshot['scope'],'expires_at'=>$c['renewal_due']??$snapshot['verification']['valid_until']],$snapshot['scope']);
            return ['qualification_reference'=>$reference,'qualification_scope'=>$snapshot['scope'],'authority_granted'=>false];
        });
    }
    private function appraisalSources(array $package):array {
        $sources=[];foreach($package['sources'] as $source){$sources[]=['source'=>$source['proposed_evidence'],'capture_sha256'=>$source['source_sha256'],'retrieved_at'=>$source['accessed_at_utc'],'policy'=>$this->policy->evidenceConfiguration($source['proposed_evidence'],'chemical')];}return $sources;
    }
    /** Private administrator metadata read; historical acceptance never becomes current approval. */
    public function inspectProvisionalAppraisal(mixed $substanceId):array {
        return $this->command(function()use($substanceId){$actor=$this->actor();$this->authority->requireOperator($actor,'administrator');$id=$this->rules->id($substanceId);$model=new MedicineProvisionalIdentity();$model->candidate($id);
            $reviewer=$this->repository->appraisalReviewer($id);$grant=$reviewer===null?null:$this->authority->requireGovernance($reviewer,'reviewer','chemical');
            $registry=$this->provisionalRegistry($id);$row=$this->repository->provisionalLatest($id);if(!$row||$row['state']!=='draft'){throw new MedicinePersistenceException('Current provisional draft is required.',409);}
            $last=$this->repository->appraisalLatest($id,(int)$row['revision']);if(!$last){return ['state'=>'pending','current'=>false,'candidate_approved'=>false];}
            if(!hash_equals(hash('sha256',$last['snapshot_json'],true),$last['snapshot_hash'])){throw new MedicinePersistenceException('Appraisal history is inconsistent.',409);}
            $s=json_decode($last['snapshot_json'],true,64,JSON_THROW_ON_ERROR);$facts=json_decode($row['snapshot_json'],true,64,JSON_THROW_ON_ERROR);$package=$model->package($id);
            if($facts['registry']!=$registry||$s['target']!=='provisional:S:'.$id||(string)$s['provisional_revision']!==(string)$row['revision']
                ||$s['target_snapshot_sha256']!==bin2hex($row['snapshot_hash'])||$s['evidence_sha256']!==bin2hex($row['evidence_hash'])
                ||$this->eligibility->json($s['sources'])!==$this->eligibility->json($this->appraisalSources($package))){throw new MedicinePersistenceException('Appraisal target or evidence changed.',409);}
            if($grant===null||(int)$last['reviewer_id']!==$reviewer){throw new MedicinePersistenceException('Appraisal changed during inspection.',409);}$qualification=$this->policy->requireQualificationBinding($this->repository,$grant,'chemical');
            if((string)$grant['id']!==(string)$last['authority_grant_id']||$s['reviewer_qualification']!=$qualification){throw new MedicinePersistenceException('Appraisal qualification changed.',409);}
            return ['appraisal_event_id'=>$last['id'],'state'=>$last['state'],'current'=>in_array($last['state'],['accepted','rejected'],true),'candidate_approved'=>false,'pilot_eligible'=>false,'published'=>false];
        });
    }
    /** Preparation-only decision. It cannot create a canonical identity or approve/enroll it. */
    public function appraiseProvisionalEvidence(mixed $substanceId,array $input):array {
        return $this->command(function()use($substanceId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,'reviewer','chemical');$qualification=$this->policy->requireQualificationBinding($this->repository,$grant,'chemical');
            $fields=['expected_revision','expected_snapshot_hash','expected_evidence_hash','expected_decision_revision','state','purpose','mapping','rationale'];$this->rules->fields($input,$fields,$fields);
            $id=$this->rules->id($substanceId);$model=new MedicineProvisionalIdentity();$model->candidate($id);$registry=$this->provisionalRegistry($id);$row=$this->repository->provisionalLatest($id);
            if(!$row||$row['state']!=='draft'){throw new MedicinePersistenceException('Current provisional draft is required.',409);}$this->revision($input['expected_revision'],(int)$row['revision']);
            foreach(['snapshot','evidence'] as $field){$hash=$row[$field.'_hash'];if(!is_string($input['expected_'.$field.'_hash'])||!hash_equals(bin2hex($hash),$input['expected_'.$field.'_hash'])||!hash_equals(hash('sha256',$row[$field.'_json'],true),$hash)){throw new MedicinePersistenceException('Provisional evidence snapshot is stale.',409);}}
            if((int)$row['actor_id']===$actor){throw new MedicinePersistenceException('Independent evidence appraisal is required.',403);}
            // All substantive proposal authors are excluded, including prior revisions.
            foreach($this->repository->provisionalHistory($id) as $prior){if((int)$prior['actor_id']===$actor){throw new MedicinePersistenceException('Independent evidence appraisal is required.',403);}}
            $facts=json_decode($row['snapshot_json'],true,64,JSON_THROW_ON_ERROR);$package=$model->package($id);
            if($facts['registry']!=$registry||$facts['sources']!=$package['sources']||$facts['package_sha256']!==MedicineProvisionalIdentity::PACKAGE_SHA256){throw new MedicinePersistenceException('Provisional source or registry facts changed.',409);}
            if($input['purpose']!=='identity'||!in_array($input['state'],['pending','accepted','rejected','withdrawn'],true)||!in_array($input['mapping'],['pending','confirmed','rejected'],true)
                ||($input['state']==='accepted'&&$input['mapping']!=='confirmed')||($input['state']==='pending'&&$input['mapping']!=='pending')||($input['state']==='rejected'&&$input['mapping']!=='rejected')){throw new MedicinePersistenceException('Identity appraisal input is invalid.',422);}
            $last=$this->repository->appraisalLatest($id,(int)$row['revision']);$this->revision($input['expected_decision_revision'],(int)($last['decision_revision']??0));
            if($last&&($last['state']!=='pending'||$input['state']==='pending')&&$input['state']!=='withdrawn'){throw new MedicinePersistenceException('Appraisal is closed; prepare a fresh revision.',409);}
            if($input['state']==='withdrawn'&&(!$last||$last['state']==='withdrawn'||(int)$last['reviewer_id']!==$actor)){throw new MedicinePersistenceException('Appraisal withdrawal is unavailable.',409);}
            $sources=$this->appraisalSources($package);
            // Recheck time-based expiry immediately before recording, under the same subject lock.
            $grant=$this->authority->requireGovernance($actor,'reviewer','chemical');$qualification=$this->policy->requireQualificationBinding($this->repository,$grant,'chemical');
            $s=['target'=>'provisional:S:'.$id,'provisional_revision'=>$row['revision'],'target_snapshot_sha256'=>bin2hex($row['snapshot_hash']),'evidence_sha256'=>bin2hex($row['evidence_hash']),'sources'=>$sources,'purpose'=>'identity','mapping'=>$input['mapping'],'state'=>$input['state'],'reviewer_id'=>$actor,'reviewer_qualification'=>$qualification,'authority_grant_id'=>$grant['id'],'rationale'=>$this->text($input['rationale']),'canonical_approval'=>false];$json=$this->eligibility->json($s);
            $event=$this->repository->appendAppraisal(['substance_id'=>$id,'provisional_revision'=>$row['revision'],'decision_revision'=>(int)($last['decision_revision']??0)+1,'state'=>$input['state'],'reviewer_id'=>$actor,'qualification_event_id'=>$qualification['qualification_event_id'],'authority_grant_id'=>$grant['id'],'snapshot_json'=>$json,'snapshot_hash'=>hash('sha256',$json,true),'occurred_at'=>$this->now()]);
            return ['appraisal_event_id'=>$event,'state'=>$input['state'],'candidate_approved'=>false,'pilot_eligible'=>false,'published'=>false];
        });
    }
    private function provisionalGrant(int $actor):array
    {
        try{return $this->authority->requireGovernance($actor,'contributor','composition');}
        catch(MedicinePersistenceException $e){if($e->getCode()!==403){throw $e;}}
        return $this->authority->requireGovernance($actor,'editor','composition');
    }
    private function provisionalRegistry(string $id):array
    {
        $model=new MedicineProvisionalIdentity();$name=$model->candidate($id);
        $registry=$this->eligibility->registrySnapshot(['substance_id'=>$id,'form_id'=>null]);
        $row=$registry['target'];
        if(($row['canonical_name']??null)!==$name||($row['identity_status']??null)!=='CANDIDATE'||($row['identity_type']??null)!=='UNKNOWN'){
            throw new MedicinePersistenceException('Medicine provisional registry candidate changed.',409);
        }
        // A new table cannot be used to erase protected canonical history for this candidate.
        $ref=$this->repository->reference('S',$id,true);
        if($ref){if(($ref['identity_status']??null)==='retired'){throw new MedicinePersistenceException('Medicine provisional recovery requires a fresh-evidence policy.',409);}
            $this->eligibility->requireLifecycle('chemical',(string)$ref['chemical_ref_id']);
            foreach($this->repository->events('chemical',(string)$ref['chemical_ref_id']) as $event){
                if(in_array($event['new_state'],['invalidated','retired'],true)){throw new MedicinePersistenceException('Medicine provisional recovery requires a fresh-evidence policy.',409);}
            }
        }
        return $registry;
    }
    /** Private preparation tokens only; does not invoke the pilot intake or verified catalog path. */
    public function inspectProvisionalIdentityDraft(mixed $substanceId):array
    {
        return $this->command(function()use($substanceId){$actor=$this->actor();$this->provisionalGrant($actor);$id=$this->rules->id($substanceId);
            $registry=$this->provisionalRegistry($id);$latest=$this->repository->provisionalLatest($id);(new MedicineProvisionalIdentity())->package($id);
            return ['target'=>'provisional:S:'.$id,'expected_revision'=>(int)($latest['revision']??0),
                'expected_registry_sha256'=>hash('sha256',$this->eligibility->json($registry)),
                'expected_package_sha256'=>MedicineProvisionalIdentity::PACKAGE_SHA256];});
    }
    /** Append-only unapproved proposal; no canonical ID, reviewer decision, or eligibility is produced. */
    public function prepareProvisionalIdentityDraft(array $input):array
    {
        return $this->command(function()use($input){$actor=$this->actor();$grant=$this->provisionalGrant($actor);
            $fields=['substance_id','expected_revision','expected_registry_sha256','expected_package_sha256','rationale'];$this->rules->fields($input,$fields,$fields);
            $id=$this->rules->id($input['substance_id']);$registry=$this->provisionalRegistry($id);$latest=$this->repository->provisionalLatest($id);
            if($latest&&$latest['state']!=='draft'){throw new MedicinePersistenceException('Medicine provisional recovery requires a fresh-evidence policy.',409);}
            $model=new MedicineProvisionalIdentity();$package=$model->package($id);$reason=$this->text($input['rationale']);
            if(!is_string($input['expected_registry_sha256'])||!hash_equals(hash('sha256',$this->eligibility->json($registry)),$input['expected_registry_sha256'])
                ||!is_string($input['expected_package_sha256'])||!hash_equals(MedicineProvisionalIdentity::PACKAGE_SHA256,$input['expected_package_sha256'])){
                throw new MedicinePersistenceException('Medicine provisional source snapshot is stale.',409);
            }
            $facts=$model->facts($id,$registry,$package);$json=$this->eligibility->json($facts);$hash=hash('sha256',$json,true);$current=(int)($latest['revision']??0);
            // Exact retry may return its committed result; it cannot resurrect an older revision.
            if($latest&&$latest['snapshot_json']===$json&&hash_equals($latest['snapshot_hash'],$hash)){
                $expected=$input['expected_revision'];if((is_string($expected)||is_int($expected))&&(string)$expected===(string)($current-1)){$this->revision($expected,$current-1);}else{$this->revision($expected,$current);}
                return $this->provisionalResult($id,$current,false);
            }
            $this->revision($input['expected_revision'],$current);$revision=$current+1;
            $evidence=$this->eligibility->json($model->evidence($facts,$revision,bin2hex($hash)));
            $this->repository->appendProvisional(['substance_id'=>$id,'revision'=>$revision,'state'=>'draft','snapshot_json'=>$json,'snapshot_hash'=>$hash,
                'evidence_json'=>$evidence,'evidence_hash'=>hash('sha256',$evidence,true),'actor_id'=>$actor,'authority_grant_id'=>$grant['id'],'rationale'=>$reason,'occurred_at'=>$this->now()]);
            return $this->provisionalResult($id,$revision,true);});
    }
    private function provisionalResult(string $id,int $revision,bool $created):array
    {
        return ['target'=>'provisional:S:'.$id,'revision'=>$revision,'state'=>'draft','created'=>$created,'verified'=>false,
            'reviewer_id'=>null,'approval'=>null,'pilot_eligible'=>false,'pilot_enrolled'=>false,'published'=>false];
    }
    /** Terminal safety action remains possible after registry/evidence loss; no restoration API. */
    public function closeProvisionalIdentityDraft(mixed $substanceId,array $input):array
    {
        return $this->command(function()use($substanceId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,'steward','composition');
            $this->rules->fields($input,['expected_revision','state','rationale'],['expected_revision','state','rationale']);$id=$this->rules->id($substanceId);$model=new MedicineProvisionalIdentity();$model->candidate($id);
            $this->repository->registry('S',$id);$latest=$this->repository->provisionalLatest($id);
            if(!$latest||$latest['state']!=='draft'||!in_array($input['state'],['withdrawn','invalidated'],true)){throw new MedicinePersistenceException('Medicine provisional transition is unavailable.',409);}
            $this->revision($input['expected_revision'],(int)$latest['revision']);$revision=(int)$latest['revision']+1;
            $facts=json_decode($latest['snapshot_json'],true,64,JSON_THROW_ON_ERROR);
            $evidence=$this->eligibility->json($model->evidence($facts,$revision,bin2hex($latest['snapshot_hash'])));
            $this->repository->appendProvisional(['substance_id'=>$id,'revision'=>$revision,'state'=>$input['state'],'snapshot_json'=>$latest['snapshot_json'],'snapshot_hash'=>$latest['snapshot_hash'],
                'evidence_json'=>$evidence,'evidence_hash'=>hash('sha256',$evidence,true),'actor_id'=>$actor,'authority_grant_id'=>$grant['id'],'rationale'=>$this->text($input['rationale']),'occurred_at'=>$this->now()]);
            return ['target'=>'provisional:S:'.$id,'revision'=>$revision,'state'=>$input['state'],'published'=>false];});
    }
    private function revision(mixed $expected,int $current):void {if((!is_string($expected)&&!is_int($expected))||!preg_match('/\A(?:0|[1-9][0-9]{0,9})\z/D',(string)$expected)||(string)$expected!==(string)$current||$current>=4294967295){throw new MedicinePersistenceException('Medicine governance revision is stale.',409);}}
    private function evidence(mixed $input,string $scope,bool $authoritative=true):array
    {
        if(!is_array($input)){throw new MedicinePersistenceException('Identity evidence is required.',422);}
        $this->rules->fields($input,['kind','issuer','record','version','locator','source_sha256'],['kind','issuer','record','version','locator','source_sha256']);
        if($input['kind']!=='identity'||!is_string($input['source_sha256'])||!preg_match('/\A[a-f0-9]{64}\z/D',$input['source_sha256'])){throw new MedicinePersistenceException('Supported identity evidence is required.',422);}
        foreach(['issuer','record','version','locator'] as $field){$input[$field]=$this->text($input[$field]);}if($authoritative){$this->policy->requireEvidence($input,$scope);}return $input;
    }
    private function event(string $scope,string $id,int $actor,?array $grant,string $action,int $revision,?string $old,string $state,string $reason,array $evidence,array $snapshot,bool $independent=false):string
    {
        $json=$this->eligibility->json($snapshot);return $this->repository->appendEvent($scope,$id,['actor_id'=>$actor,'authority_grant_id'=>$grant['id']??null,'action'=>$action,'revision'=>$revision,'previous_state'=>$old,'new_state'=>$state,'rationale'=>$reason,'evidence_json'=>json_encode($evidence,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'snapshot_json'=>$json,'snapshot_hash'=>hash('sha256',$json,true),'independent'=>$independent?1:0,'occurred_at'=>$this->now()]);
    }
    /** Session-bound fixed owner entry point status; this never establishes authority. */
    public function ownerBootstrapStatus():array {
        return $this->command(function(){
            $actor=$this->auth->id();$decision=$this->policy->bootstrapDecision();
            if($actor!==1||$decision['owner_user_id']!==1||$decision['administrator_user_id']!==1
                ||!$this->policy->bootstrapOwner($actor)||($this->repository->actor($actor,false)['status']??null)!=='active'){
                throw new MedicinePersistenceException('Medicine owner bootstrap access denied.',403);
            }
            return $this->repository->ownerBootstrapState();
        });
    }
    /** No client authority/account/expiry input is accepted at this boundary. */
    public function bootstrapOwnerFromSession():array {
        $status=$this->ownerBootstrapStatus();
        if($status['initialized']){throw new MedicinePersistenceException('Medicine authority has already been initialized.',409);}
        if(!$status['schema_ready']){throw new MedicinePersistenceException('Medicine owner bootstrap setup is unavailable.',503);}
        $decision=$this->policy->bootstrapDecision();
        return $this->bootstrapAdministrator(['user_id'=>1,'capability'=>'administrator','scope'=>'catalog','expires_at'=>null,
            'decision_reference'=>$decision['decision_reference'],'rationale'=>'Owner establishes the initial Medicine Administrator under the recorded owner decision.']);
    }
    /** One-time owner establishment; never an exception in ordinary self-grant issuance. */
    public function bootstrapAdministrator(array $input):array {
        return $this->command(function()use($input){
            $actor=$this->actor();$decision=$this->policy->bootstrapDecision();
            if(!$this->policy->bootstrapOwner($actor)){throw new MedicinePersistenceException('Medicine owner bootstrap access denied.',403);}
            $fields=['user_id','capability','scope','expires_at','rationale','decision_reference'];$this->rules->fields($input,$fields,$fields);
            $target=(int)$this->rules->id($input['user_id']);
            if($target!==$decision['administrator_user_id']||$input['decision_reference']!==$decision['decision_reference']){throw new MedicinePersistenceException('Medicine owner decision does not match.',403);}
            if($input['capability']!=='administrator'||$input['scope']!=='catalog'||$input['expires_at']!==null){throw new MedicinePersistenceException('Invalid initial owner authority.',422);}
            $reason=$this->text($input['rationale']);
            // actor() locked the one fixed owner row: concurrent bootstraps serialize here.
            if($this->repository->authorityInitialized()){throw new MedicinePersistenceException('Medicine authority has already been initialized.',409);}
            $row=['user_id'=>$target,'capability'=>'administrator','scope'=>'catalog','is_active'=>1,'qualification_reference'=>null,'qualification_assessed_by'=>null,'qualification_scope'=>null,'granted_by'=>$actor,'granted_at'=>$this->now(),'expires_at'=>null];
            $id=$this->repository->insertGrant($row);
            $snapshot=$row+['establishment'=>['mechanism'=>'initial_owner_bootstrap','owner_user_id'=>$actor,'policy_version'=>$decision['policy_version'],'decision_reference'=>$decision['decision_reference']]];
            $event=$this->event('grant',$id,$actor,null,'grant',1,null,'active',$reason,[],$snapshot);
            return ['grant_id'=>$id,'event_id'=>$event,'initialized'=>true,'capability'=>'administrator','scope'=>'catalog','expires_at'=>null];
        });
    }
    public function grantAuthority(array $input):array
    {
        return $this->command(function()use($input){$actor=$this->actor();$this->authority->requireOperator($actor,'administrator');
            $this->rules->fields($input,['user_id','capability','scope','expires_at','qualification_reference','qualification_scope','rationale'],['user_id','capability','scope','expires_at','rationale']);
            $user=(int)$this->rules->id($input['user_id']);if($user===$actor||($this->repository->actor($user,true)['status']??null)!=='active'){throw new MedicinePersistenceException('Medicine grant subject is invalid.',403);}
            if(!in_array($input['capability'],['contributor','editor','reviewer','publisher','steward','administrator','registry','deployment'],true)||!in_array($input['scope'],['chemical','composition','presentation','catalog'],true)){throw new MedicinePersistenceException('Invalid Medicine authority scope.',422);}
            if(in_array($input['capability'],['administrator','registry','deployment'],true)&&(!$this->policy->mayDelegateOperator($actor)||$input['scope']!=='catalog')){throw new MedicinePersistenceException('Only an approved owner may delegate Medicine operator authority.',403);}
            $time=is_string($input['expires_at'])?DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u',$input['expires_at'],new DateTimeZone('UTC')):false;
            if(!$time||$time->format('Y-m-d H:i:s.u')!==$input['expires_at']||$input['expires_at']<=$this->now()){throw new MedicinePersistenceException('Valid Medicine authority expiry is required.',422);}
            $qualification=$this->rules->text($input['qualification_reference']??null,4000);$scope=$input['qualification_scope']??null;
            if($input['capability']==='reviewer'&&($qualification===null||!in_array($scope,[$input['scope'],'catalog'],true))){throw new MedicinePersistenceException('Reviewer qualification is required.',403);}
            if($qualification!==null&&$input['capability']!=='reviewer'){throw new MedicinePersistenceException('Unexpected qualification fields.',422);}
            if($qualification===null&&$scope!==null){throw new MedicinePersistenceException('Incomplete qualification fields.',422);}
            $row=['user_id'=>$user,'capability'=>$input['capability'],'scope'=>$input['scope'],'is_active'=>1,'qualification_reference'=>$qualification,'qualification_assessed_by'=>$qualification===null?null:$actor,'qualification_scope'=>$scope,'granted_by'=>$actor,'granted_at'=>$this->now(),'expires_at'=>$input['expires_at']];
            if($input['capability']==='reviewer'){$this->policy->requireQualificationBinding($this->repository,$row,$input['scope']);}
            $id=$this->repository->insertGrant($row);$event=$this->event('grant',$id,$actor,null,'grant',1,null,'active',$this->text($input['rationale']),[],$row);return ['grant_id'=>$id,'event_id'=>$event];
        });
    }
    public function revokeAuthority(mixed $grantId,string $reason):array
    {
        return $this->command(function()use($grantId,$reason){$actor=$this->actor();$this->authority->requireOperator($actor,'administrator');$id=$this->rules->id($grantId);$row=$this->repository->grant($id);
            if($row&&in_array($row['capability'],['administrator','registry','deployment'],true)&&!$this->policy->mayDelegateOperator($actor)){throw new MedicinePersistenceException('Only an approved owner may revoke Medicine operator authority.',403);}
            if(!$row||$row['revoked_at']!==null){throw new MedicinePersistenceException('Medicine grant is unavailable or revoked.',409);}$reason=$this->text($reason);
            $this->repository->revokeGrant($id,$actor,$this->now(),$reason);$event=$this->event('grant',$id,$actor,null,'revoke',2,'active','revoked',$reason,[],$this->repository->grant($id));return ['grant_id'=>$id,'event_id'=>$event];
        });
    }
    /** Current snapshot is internal/scoped; callers supply digest and revision back to commands. */
    public function inspect(string $scope,mixed $targetId):array
    {
        return $this->command(function()use($scope,$targetId){$actor=$this->actor();$this->authority->requireGovernance($actor,'contributor',$scope);$id=$this->rules->id($targetId);$this->authority->requirePilotTarget($scope,$id);$snapshot=$this->eligibility->snapshot($scope,$id);$row=$this->repository->target($scope,$id);return ['revision'=>(int)$row['metadata_revision'],'snapshot_hash'=>hash('sha256',$this->eligibility->json($snapshot)),'snapshot'=>$snapshot];});
    }
    private function checkedSnapshot(string $scope,string $id,array $input):array
    {
        // Match adverse commands: lock the target before its lifecycle history.
        $this->repository->target($scope,$id);
        $this->eligibility->requireLifecycle($scope,$id);
        $this->authority->requirePilotTarget($scope,$id);
        $snapshot=$this->eligibility->snapshot($scope,$id);$row=$this->repository->target($scope,$id);$this->revision($input['expected_revision']??null,(int)$row['metadata_revision']);
        if(!is_string($input['expected_snapshot_hash']??null)||!hash_equals(hash('sha256',$this->eligibility->json($snapshot)),$input['expected_snapshot_hash'])){throw new MedicinePersistenceException('Medicine governance snapshot is stale.',409);}return [$row,$snapshot];
    }
    public function assessChemical(mixed $refId,array $input):array
    {
        return $this->command(function()use($refId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,'contributor','chemical');$id=$this->rules->id($refId);
            $this->rules->fields($input,['expected_revision','expected_snapshot_hash','state','rationale','candidate_identity_id'],['expected_revision','expected_snapshot_hash','state','rationale']);
            if(!in_array($input['state'],['candidate','unresolved'],true)){throw new MedicinePersistenceException('Reconciliation must remain candidate or unresolved.',422);}
            [$row,$snapshot]=$this->checkedSnapshot('chemical',$id,$input);$old=$this->repository->assessment($id);$candidate=null;
            if(isset($input['candidate_identity_id'])){$candidate=$this->rules->id($input['candidate_identity_id']);$found=false;foreach($this->repository->ingredients($candidate,true) as $member){if((string)$member['chemical_ref_id']===$id){$found=true;}}if(!$found){throw new MedicinePersistenceException('Reconciliation composition does not contain this reference.',422);}}
            $revision=(int)$row['metadata_revision']+1;$event=$this->event('chemical',$id,$actor,$grant,'assess',$revision,$old['assessment_state']??$row['identity_status'],$input['state'],$this->text($input['rationale']),[],$snapshot);
            $this->repository->changeTarget('chemical',$id,['identity_status'=>'candidate','metadata_revision'=>$revision,'updated_at'=>$this->now(),'verified_by'=>null,'verified_at'=>null,'verification_reference'=>null]);
            $this->assessment($id,$candidate,$revision,$input['state'],$snapshot,$event,$actor,$old!==null);return ['revision'=>$revision,'event_id'=>$event];
        });
    }
    private function assessment(string $id,?string $candidate,int $revision,string $state,array $snapshot,string $event,int $actor,bool $exists):void
    {
        $json=$this->eligibility->json($snapshot);$this->repository->saveAssessment(['chemical_ref_id'=>$id,'candidate_identity_id'=>$candidate,'revision'=>$revision,'assessment_state'=>$state,'registry_hash'=>hash('sha256',$json,true),'registry_snapshot'=>$json,'decision_event_id'=>$event,'updated_by'=>$actor,'updated_at'=>$this->now()],$exists);
    }
    public function editMetadata(string $scope,mixed $targetId,array $input):array
    {
        return $this->command(function()use($scope,$targetId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,'editor',$scope);$id=$this->rules->id($targetId);
            if(!in_array($scope,['composition','presentation'],true)){throw new MedicinePersistenceException('Chemical semantics cannot be edited here.',422);}
            $this->rules->fields($input,['expected_revision','expected_snapshot_hash','display_name','rationale'],['expected_revision','expected_snapshot_hash','display_name','rationale']);[$row,$snapshot]=$this->checkedSnapshot($scope,$id,$input);$label=$this->rules->text($input['display_name'],500);$revision=(int)$row['metadata_revision']+1;
            $this->repository->changeTarget($scope,$id,['display_name'=>$label,'identity_status'=>'candidate','metadata_revision'=>$revision,'updated_at'=>$this->now(),'verified_by'=>null,'verified_at'=>null,'verification_reference'=>null]);
            $snapshot=$this->eligibility->snapshot($scope,$id);$event=$this->event($scope,$id,$actor,$grant,'edit',$revision,$row['identity_status'],'candidate',$this->text($input['rationale']),[],$snapshot);return ['revision'=>$revision,'event_id'=>$event];
        });
    }
    /** Evidence attachment/submission is a draft decision, never an approval. */
    public function submit(string $scope,mixed $targetId,array $input):array {
        return $this->command(function()use($scope,$targetId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,$scope==='chemical'?'contributor':'editor',$scope);$id=$this->rules->id($targetId);
            $this->rules->fields($input,['expected_revision','expected_snapshot_hash','rationale','evidence'],['expected_revision','expected_snapshot_hash','rationale','evidence']);
            [$row,$snapshot]=$this->checkedSnapshot($scope,$id,$input);
            if($row['identity_status']!=='candidate'){throw new MedicinePersistenceException('Only Medicine candidates can be submitted.',409);}
            $assessment=$scope==='chemical'?$this->repository->assessment($id):null;
            if($scope==='chemical'&&(!$assessment||$assessment['assessment_state']!=='candidate')){throw new MedicinePersistenceException('Explicit candidate reassessment is required.',409);}
            $revision=(int)$row['metadata_revision']+1;$event=$this->event($scope,$id,$actor,$grant,'submit',$revision,'candidate','candidate',$this->text($input['rationale']),$this->evidence($input['evidence'],$scope,false),$snapshot);
            $this->repository->changeTarget($scope,$id,['metadata_revision'=>$revision,'updated_at'=>$this->now()]);
            if($assessment){$this->assessment($id,$assessment['candidate_identity_id']===null?null:(string)$assessment['candidate_identity_id'],$revision,'candidate',$snapshot,$event,$actor,true);}
            return ['revision'=>$revision,'event_id'=>$event,'published'=>false];
        });
    }
    public function reject(string $scope,mixed $targetId,array $input):array {
        return $this->command(function()use($scope,$targetId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,'reviewer',$scope);$id=$this->rules->id($targetId);
            $this->rules->fields($input,['expected_revision','expected_snapshot_hash','rationale'],['expected_revision','expected_snapshot_hash','rationale']);[$row,$snapshot]=$this->checkedSnapshot($scope,$id,$input);
            if($row['identity_status']!=='candidate'||(int)$row['created_by']===$actor){throw new MedicinePersistenceException('Independent candidate review is required.',403);}
            foreach($this->repository->events($scope,$id) as $prior){if(in_array($prior['action'],['create','assess','edit','submit'],true)&&(int)$prior['actor_id']===$actor){throw new MedicinePersistenceException('Independent candidate review is required.',403);}}
            $this->policy->requireSubmission($this->repository->latest($scope,$id),(int)$row['metadata_revision']);
            $revision=(int)$row['metadata_revision']+1;$event=$this->event($scope,$id,$actor,$grant,'reject',$revision,'candidate','unresolved',$this->text($input['rationale']),['reviewer_qualification'=>$this->policy->requireQualificationBinding($this->repository,$grant,$scope)],$snapshot,true);
            $this->repository->changeTarget($scope,$id,['identity_status'=>'candidate','metadata_revision'=>$revision,'updated_at'=>$this->now(),'verified_by'=>null,'verified_at'=>null,'verification_reference'=>null]);
            if($scope==='chemical'){$old=$this->repository->assessment($id);$this->assessment($id,$old&&$old['candidate_identity_id']!==null?(string)$old['candidate_identity_id']:null,$revision,'unresolved',$snapshot,$event,$actor,$old!==null);}
            return ['revision'=>$revision,'event_id'=>$event,'published'=>false];
        });
    }
    public function verify(string $scope,mixed $targetId,array $input):array
    {
        return $this->command(function()use($scope,$targetId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,'reviewer',$scope);$id=$this->rules->id($targetId);
            $this->rules->fields($input,['expected_revision','expected_snapshot_hash','rationale','evidence'],['expected_revision','expected_snapshot_hash','rationale','evidence']);[$row,$snapshot]=$this->checkedSnapshot($scope,$id,$input);
            if((int)$row['created_by']===$actor){throw new MedicinePersistenceException('Independent Medicine review is required.',403);}
            foreach($this->repository->events($scope,$id) as $event){if(in_array($event['action'],['assess','edit','create','submit'],true)&&(int)$event['actor_id']===$actor){throw new MedicinePersistenceException('Independent Medicine review is required.',403);}}
            $this->policy->requireSubmission($this->repository->latest($scope,$id),(int)$row['metadata_revision']);
            $assessment=$scope==='chemical'?$this->repository->assessment($id):null;
            // A fresh independent decision may replace current verification, never an unresolved/adverse assessment.
            if($scope==='chemical'&&(!$assessment||!in_array($assessment['assessment_state'],['candidate','verified'],true))){throw new MedicinePersistenceException('Chemical reconciliation is unresolved.',409);}
            $evidence=$this->evidence($input['evidence'],$scope);
            $this->policy->requireEvidenceFor($evidence,$scope,$id,hash('sha256',$this->eligibility->json($snapshot)));
            $evidence['reviewer_qualification']=$this->policy->requireQualificationBinding($this->repository,$grant,$scope);
            $revision=(int)$row['metadata_revision']+1;$event=$this->event($scope,$id,$actor,$grant,'verify',$revision,$row['identity_status'],'verified',$this->text($input['rationale']),$evidence,$snapshot,true);
            $this->repository->changeTarget($scope,$id,['identity_status'=>'verified','metadata_revision'=>$revision,'updated_at'=>$this->now(),'verified_by'=>$actor,'verified_at'=>$this->now(),'verification_reference'=>'Medicine governance event '.$event]);
            if($scope==='chemical'){$this->assessment($id,$assessment['candidate_identity_id']===null?null:(string)$assessment['candidate_identity_id'],$revision,'verified',$snapshot,$event,$actor,true);}
            return ['revision'=>$revision,'event_id'=>$event,'published'=>false];
        });
    }
    private function pilotOperation(string $scope,string $id):?array
    {
        $last=null;
        foreach($this->repository->events($scope,$id) as $event){if(in_array($event['action'],['pilot_enroll','pilot_disable'],true)){$last=$event;}}
        return $last;
    }
    private function pilotScope(string $scope):void
    {
        if(!in_array($scope,['chemical','composition'],true)){throw new MedicinePersistenceException('Medicine pilot scope is unavailable.',422);}
    }
    /** Approval-bound operator enrollment reuses intake and the immutable governance journal. */
    public function enrollPilot(string $scope,mixed $targetId,array $input):array
    {
        return $this->command(function()use($scope,$targetId,$input){
            $actor=$this->actor();$this->authority->requireOperator($actor,'deployment');$this->pilotScope($scope);
            $this->rules->fields($input,['expected_revision','expected_snapshot_hash','expected_enrollment_event_id','rationale'],['expected_revision','expected_snapshot_hash','expected_enrollment_event_id','rationale']);
            $id=$this->rules->id($targetId);[$row,$snapshot]=$this->checkedSnapshot($scope,$id,$input);$this->eligibility->requireCurrent($scope,$id);
            $last=$this->pilotOperation($scope,$id);
            if(!is_string($input['expected_enrollment_event_id'])||$input['expected_enrollment_event_id']!==(string)($last['id']??'0')){throw new MedicinePersistenceException('Medicine pilot enrollment is stale.',409);}
            $approval=$this->eligibility->decision($scope,$id);$pilot=$this->policy->pilot();
            if($last&&$last['action']==='pilot_enroll'&&(string)$last['revision']===(string)$row['metadata_revision']){throw new MedicinePersistenceException('Medicine revision is already enrolled.',409);}
            $grant=null;
            try{$grant=$this->authority->requireGovernance($actor,'deployment','catalog');}catch(MedicinePersistenceException $e){if($e->getCode()!==403){throw $e;}}
            $event=$this->event($scope,$id,$actor,$grant,'pilot_enroll',(int)$row['metadata_revision'],'verified','verified',$this->text($input['rationale']),[],['pilot_code'=>$pilot['code'],'approval_event_id'=>$approval['id'],'approved_snapshot_hash'=>$approval['snapshot_hash']]);
            return ['target_id'=>$id,'revision'=>(int)$row['metadata_revision'],'event_id'=>$event,'approval_event_id'=>$approval['id'],'published'=>false];
        });
    }
    /** Revocation must remain possible while intake/availability is disabled or review is invalid. */
    public function disablePilot(string $scope,mixed $targetId,array $input):array
    {
        return $this->command(function()use($scope,$targetId,$input){
            $actor=$this->actor();$this->authority->requireOperator($actor,'deployment');$this->pilotScope($scope);
            $this->rules->fields($input,['expected_revision','expected_enrollment_event_id','rationale'],['expected_revision','expected_enrollment_event_id','rationale']);
            $id=$this->rules->id($targetId);$row=$this->repository->target($scope,$id);$marker=$this->repository->pilotTarget($scope,$id);$last=$this->pilotOperation($scope,$id);
            if(!$row||!$marker||!$last||$last['action']!=='pilot_enroll'||!is_string($input['expected_enrollment_event_id'])||$input['expected_enrollment_event_id']!==(string)$last['id']){throw new MedicinePersistenceException('Medicine pilot enrollment is unavailable or stale.',409);}
            $this->revision($input['expected_revision'],(int)$row['metadata_revision']);
            $event=$this->event($scope,$id,$actor,null,'pilot_disable',(int)$row['metadata_revision'],$row['identity_status'],$row['identity_status'],$this->text($input['rationale']),[],['pilot_code'=>$marker['pilot_code'],'enrollment_event_id'=>$last['id']]);
            return ['target_id'=>$id,'event_id'=>$event,'active'=>false,'published'=>false];
        });
    }
    private function currentEnrollment(array $entry):array
    {
        $scope=$entry['scope'];$id=$this->rules->id($entry['target_id']);$this->repository->target($scope,$id);
        $this->authority->requirePilotTarget($scope,$id);$this->eligibility->requireCurrent($scope,$id);
        $decision=$this->eligibility->decision($scope,$id);$event=$this->pilotOperation($scope,$id);
        $data=$event?json_decode($event['snapshot_json'],true,16,JSON_THROW_ON_ERROR):null;
        if(!$event||$event['action']!=='pilot_enroll'||(string)$event['id']!==$entry['enrollment_event_id']||(string)$event['revision']!==$entry['revision']
            ||(string)$decision['revision']!==$entry['revision']||(string)$decision['id']!==$entry['approval_event_id']
            ||($data['pilot_code']??null)!==$this->policy->pilot()['code']||(string)($data['approval_event_id']??'')!==$entry['approval_event_id']
            ||($data['approved_snapshot_hash']??null)!==bin2hex($decision['snapshot_hash'])){throw new MedicinePersistenceException('Medicine pilot enrollment requires current operator approval.',409);}
        $this->authority->requireOperator((int)$event['actor_id'],'deployment');
        if($event['authority_grant_id']!==null){$grant=$this->authority->requireGovernance((int)$event['actor_id'],'deployment','catalog');if((string)$grant['id']!==(string)$event['authority_grant_id']){throw new MedicinePersistenceException('Medicine pilot operator enrollment requires renewal.',409);}}
        return $decision;
    }
    /** Internal service projection only; no route, public publication or credentials/history. */
    public function internalPilot(string $scope,mixed $targetId):array
    {
        return $this->command(function()use($scope,$targetId){
            $actor=$this->actor();$this->pilotScope($scope);$authorized=false;
            foreach(['contributor','editor','reviewer'] as $cap){try{$this->authority->requireGovernance($actor,$cap,$scope);$authorized=true;break;}catch(MedicinePersistenceException $e){if($e->getCode()!==403){throw $e;}}}
            if(!$authorized){throw new MedicinePersistenceException('Medicine pilot access denied.',403);}
            $id=$this->rules->id($targetId);$gate=$this->policy->activation();
            $this->authority->requireOperator($gate['administrator_id'],'administrator');$this->authority->requireOperator($gate['deployment_operator_id'],'deployment');
            $found=null;
            // The complete explicitly approved manifest must remain current on every internal read.
            $entries=$gate['enrollments'];usort($entries,fn($a,$b)=>strcmp($a['scope'],$b['scope'])?:strlen($a['target_id'])<=>strlen($b['target_id'])?:strcmp($a['target_id'],$b['target_id']));
            foreach($entries as $entry){$this->authority->requireGovernance($gate['reviewer_id'],'reviewer',$entry['scope']);$this->currentEnrollment($entry);if($entry['scope']===$scope&&$entry['target_id']===$id){$found=$entry;}}
            if(!$found){throw new MedicinePersistenceException('Medicine target is outside the active pilot.',403);}
            $row=$this->repository->target($scope,$id);
            return ['scope'=>$scope,'target_id'=>$id,'revision'=>(int)$row['metadata_revision'],'display_name'=>$row['display_name']??null,'internal'=>true,'published'=>false];
        });
    }
    public function invalidate(string $scope,mixed $targetId,array $input):array
    {
        return $this->command(function()use($scope,$targetId,$input){$actor=$this->actor();$grant=$this->authority->requireGovernance($actor,'steward',$scope);$id=$this->rules->id($targetId);
            $this->rules->fields($input,['expected_revision','state','rationale'],['expected_revision','state','rationale']);if(!in_array($input['state'],['invalidated','withdrawn','superseded','retired'],true)){throw new MedicinePersistenceException('Invalid Medicine adverse decision.',422);}
            $row=$this->repository->target($scope,$id);if(!$row||$row['identity_status']==='retired'){throw new MedicinePersistenceException('Medicine target is retired or unavailable.',409);}$this->revision($input['expected_revision'],(int)$row['metadata_revision']);
            $this->eligibility->requireLifecycle($scope,$id);
            // Adverse decisions must still work after registry/dependency loss.
            $snapshot=['target_id'=>$id,'key'=>isset($row['composition_hash'])?bin2hex($row['composition_hash']):(isset($row['presentation_hash'])?bin2hex($row['presentation_hash']):null),'reason'=>$this->text($input['rationale'])];
            $revision=(int)$row['metadata_revision']+1;$retired=$input['state']==='retired';$event=$this->event($scope,$id,$actor,$grant,$retired?'retire':'invalidate',$revision,$row['identity_status'],$input['state'],$snapshot['reason'],[],$snapshot);
            $changes=['identity_status'=>$retired?'retired':'candidate','metadata_revision'=>$revision,'updated_at'=>$this->now()];if($retired){$changes+=['retired_by'=>$actor,'retired_at'=>$this->now(),'retirement_reason'=>$snapshot['reason']];}$this->repository->changeTarget($scope,$id,$changes);
            if($scope==='chemical'){$old=$this->repository->assessment($id);$this->assessment($id,$old&&$old['candidate_identity_id']!==null?(string)$old['candidate_identity_id']:null,$revision,$input['state'],$snapshot,$event,$actor,$old!==null);}
            return ['revision'=>$revision,'event_id'=>$event,'published'=>false];
        });
    }
}

<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\MedicineGovernanceRepository;
use DateTimeImmutable;
use DateTimeZone;

/** Current dependency/evidence checks shared by governance and all new-use paths. */
class MedicineEligibilityService
{
    public function __construct(private MedicineGovernanceRepository $repository,private MedicineCanonicalizer $rules,private MedicineGovernancePolicy $policy) {}
    public function json(array $value):string
    {
        $normalize=function(mixed $v) use (&$normalize):mixed {if(!is_array($v)){return $v===null?null:(string)$v;}if(!array_is_list($v)){ksort($v);}foreach($v as $key=>&$item){$item=is_string($key)&&str_ends_with($key,'_hash')&&is_string($item)?bin2hex($item):$normalize($item);}unset($item);return $v;};
        $json=json_encode($normalize($value),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        if(strlen($json)>262144){throw new MedicinePersistenceException('Medicine governance snapshot is too large.',422);}return $json;
    }
    public function registrySnapshot(array $ref):array
    {
        if(($ref['substance_id']===null)===($ref['form_id']===null)){throw new MedicinePersistenceException('Chemical reference is invalid.',409);}
        $tag=$ref['substance_id']!==null?'S':'F';$id=$this->rules->id($ref['substance_id']??$ref['form_id']);
        $target=$this->repository->registry($tag,$id);if(!$target||(string)$target['is_active']!=='1'){throw new MedicinePersistenceException('Chemical registry target is unavailable.',409);}
        $parent=null;if($tag==='F'){$parent=$this->repository->registry('S',$this->rules->id($target['substance_id']));if(!$parent||(string)$parent['is_active']!=='1'){throw new MedicinePersistenceException('Chemical parent is unavailable.',409);}}
        return ['tag'=>$tag,'id'=>$id,'target'=>$target,'parent'=>$parent,'lifecycle_event_id'=>$this->repository->registryLifecycle($tag,$id),'parent_lifecycle_event_id'=>$parent===null?null:$this->repository->registryLifecycle('S',$this->rules->id($target['substance_id']))];
    }
    /** No restoration workflow exists: protected history cannot be erased by another state. */
    public function requireLifecycle(string $scope,string $id):void
    {
        foreach($this->repository->events($scope,$id) as $event){if(in_array($event['new_state'],['withdrawn','superseded'],true)){throw new MedicinePersistenceException('Medicine restoration requires an approved policy.',409);}}
    }
    public function snapshot(string $scope,string $id,bool $dependencies=true):array
    {
        if(!in_array($scope,['chemical','composition','presentation'],true)){throw new MedicinePersistenceException('Invalid Medicine scope.',422);}
        $row=$this->repository->target($scope,$id);if(!$row||!in_array($row['identity_status'],['candidate','verified'],true)){throw new MedicinePersistenceException('Medicine target is unavailable.',409);}
        if($scope==='chemical'){return ['chemical_ref_id'=>$id,'registry'=>$this->registrySnapshot($row)];}
        $identityId=$scope==='composition'?$id:$this->rules->id($row['medicine_identity_id']);
        if($scope==='presentation'&&$dependencies){$this->requireCurrent('composition',$identityId);}
        $identity=$this->repository->target('composition',$identityId);if(!$identity||!in_array($identity['identity_status'],['candidate','verified'],true)){throw new MedicinePersistenceException('Medicine composition is unavailable.',409);}
        $members=$this->repository->ingredients($identityId,true);$refs=[];$dependenciesSnapshot=[];
        foreach($members as $member){$refId=$this->rules->id($member['chemical_ref_id']);if($dependencies){$this->requireCurrent('chemical',$refId);}$ref=$this->repository->target('chemical',$refId);if(!$ref){throw new MedicinePersistenceException('Medicine ingredient is unavailable.',409);}
            $registry=$this->registrySnapshot($ref);$refs[]=['tag'=>$registry['tag'],'id'=>$registry['id'],'chemical_ref_id'=>$refId,'medicine_ingredient_id'=>$this->rules->id($member['medicine_ingredient_id'])];
            $dependenciesSnapshot[$refId]=['ref_revision'=>$ref['metadata_revision'],'assessment'=>$this->repository->assessment($refId),'registry'=>$registry];
        }
        $canonical=$this->rules->composition($refs);
        if((string)$identity['key_version']!=='1'||$identity['composition_key']!==$canonical['key']||!hash_equals($canonical['hash'],$identity['composition_hash'])){throw new MedicinePersistenceException('Medicine composition is inconsistent.',409);}
        $result=['identity_id'=>$identityId,'composition_key'=>$canonical['key'],'display_name'=>$identity['display_name'],'dependencies'=>$dependenciesSnapshot];
        if($scope==='composition'){return $result;}
        $byMember=[];foreach($canonical['references'] as $member){$byMember[$member['medicine_ingredient_id']]=$member['chemical_ref_id'];}
        $strengths=[];foreach($this->repository->strengths($id,true) as $strength){if((string)$strength['medicine_identity_id']!==$identityId||!isset($byMember[$strength['medicine_ingredient_id']])){throw new MedicinePersistenceException('Medicine strength scope is invalid.',409);}
            $input=array_intersect_key($strength,array_flip(['ratio_numerator','ratio_denominator','numerator_unit_code','denominator_unit_code','original_strength_text']));$ratio=$this->rules->strength($input);
            if($ratio['ratio_numerator']!==$strength['ratio_numerator']||$ratio['ratio_denominator']!==$strength['ratio_denominator']){throw new MedicinePersistenceException('Medicine strength is not canonical.',409);}
            $strengths[]=['chemical_ref_id'=>$byMember[$strength['medicine_ingredient_id']]]+$input;
        }
        $p=$this->rules->presentation($identityId,$canonical['references'],['dosage_form_code'=>$row['dosage_form_code'],'route_code'=>$row['route_code'],'release_code'=>$row['release_code'],'strengths'=>$strengths]);
        if((string)$row['key_version']!=='1'||$p['key']!==$row['presentation_key']||!hash_equals($p['hash'],$row['presentation_hash'])){throw new MedicinePersistenceException('Medicine presentation is inconsistent.',409);}
        return ['composition'=>$result,'presentation_key'=>$p['key'],'display_name'=>$row['display_name'],'identity_revision'=>$identity['metadata_revision']];
    }
    public function requireCurrent(string $scope,string $id):void
    {
        $row=$this->repository->target($scope,$id);$event=$this->decision($scope,$id);
        $this->requireLifecycle($scope,$id);
        if(!$row||$row['identity_status']!=='verified'||!$event||$event['action']!=='verify'||$event['new_state']!=='verified'||(string)$event['revision']!==(string)$row['metadata_revision']||(string)$event['independent']!=='1'
            ||(string)($row['verified_by']??'')!==(string)$event['actor_id']||($row['verification_reference']??null)!=='Medicine governance event '.$event['id']){
            throw new MedicinePersistenceException('Medicine identity requires current independent review.',409);
        }
        $grant=$this->repository->grant($this->rules->id($event['authority_grant_id']));
        $now=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        if(!$grant||$grant['capability']!=='reviewer'||(string)$grant['user_id']!==(string)$event['actor_id']||!in_array($grant['scope'],[$scope,'catalog'],true)||(string)$grant['is_active']!=='1'||$grant['revoked_at']!==null||$grant['revoked_by']!==null||$grant['revocation_reason']!==null||$grant['granted_at']>$now||$grant['expires_at']<=$now||($this->repository->actor((int)$grant['user_id'],true)['status']??null)!=='active'){
            throw new MedicinePersistenceException('Medicine review authority is no longer current.',409);
        }
        foreach(['granted_at','expires_at'] as $field){$time=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u',(string)$grant[$field],new DateTimeZone('UTC'));if(!$time||$time->format('Y-m-d H:i:s.u')!==$grant[$field]){throw new MedicinePersistenceException('Medicine review authority state is invalid.',409);}}
        if(empty($grant['qualification_assessed_by'])||!is_string($grant['qualification_reference'])||trim($grant['qualification_reference'])===''||!in_array($grant['qualification_scope'],[$scope,'catalog'],true)) {throw new MedicinePersistenceException('Medicine reviewer qualification is unavailable.',409);}
        if((int)$row['created_by']===(int)$event['actor_id']){throw new MedicinePersistenceException('Medicine review is not independent.',409);}
        foreach($this->repository->events($scope,$id) as $prior){if((int)$prior['id']<(int)$event['id']&&in_array($prior['action'],['assess','edit','create','submit'],true)&&(string)$prior['actor_id']===(string)$event['actor_id']){throw new MedicinePersistenceException('Medicine review is not independent.',409);}}
        $qualification=$this->policy->requireQualificationBinding($this->repository,$grant,$scope);$evidence=json_decode($event['evidence_json'],true,32,JSON_THROW_ON_ERROR);
        if(($evidence['reviewer_qualification']??null)!=$qualification){throw new MedicinePersistenceException('Medicine review qualification binding changed.',409);}
        $this->policy->requireEvidence($evidence,$scope);
        $snapshot=$this->json($this->snapshot($scope,$id));
        $this->policy->requireEvidenceFor($evidence,$scope,$id,hash('sha256',$snapshot));
        if(!hash_equals(hash('sha256',$snapshot,true),$event['snapshot_hash'])||$snapshot!==$event['snapshot_json']){throw new MedicinePersistenceException('Medicine evidence or registry facts changed. Re-review is required.',409);}
        if($scope==='chemical'){$assessment=$this->repository->assessment($id);if(!$assessment||$assessment['assessment_state']!=='verified'||(string)$assessment['revision']!==(string)$row['metadata_revision']||(string)$assessment['decision_event_id']!==(string)$event['id']||$assessment['registry_snapshot']!==$snapshot||!hash_equals(hash('sha256',$snapshot,true),$assessment['registry_hash'])){throw new MedicinePersistenceException('Chemical reconciliation is unresolved.',409);}}
    }
    /** Operational pilot events do not replace or revive a clinical decision. */
    public function decision(string $scope,string $id):?array
    {
        $decision=null;
        foreach($this->repository->events($scope,$id) as $event){
            if(!in_array($event['action'],['pilot_enroll','pilot_disable'],true)){$decision=$event;}
        }
        return $decision;
    }
}

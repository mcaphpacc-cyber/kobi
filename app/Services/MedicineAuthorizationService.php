<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MedicineGovernanceRepository;
use DateTimeImmutable;
use DateTimeZone;

/** Injectable fail-closed catalog boundary. Profile/Treatment grants do not apply. */
class MedicineAuthorizationService
{
    public function __construct(private MedicineGovernanceRepository $repository,private MedicineGovernancePolicy $policy,private MedicineEligibilityService $eligibility) {}

    public function requireCatalog(int $actor, string $action): void
    {
        if(!isset($this->policy,$this->repository)){throw new MedicinePersistenceException('Medicine catalog access denied.',403);}
        $scope=['register_reference'=>'chemical','create_identity'=>'composition','create_presentation'=>'presentation'][$action]??null;
        if($scope===null){throw new MedicinePersistenceException('Medicine catalog access denied.',403);}
        $pilot=$this->policy->pilot();
        if(!in_array($action,$pilot['actions'],true)){throw new MedicinePersistenceException('Medicine pilot action denied.',403);}
        $this->requireGovernance($actor,'contributor',$scope);
    }

    /** Controlled internal governance only; does not open catalog creation/publication. */
    public function requireGovernance(int $actor,string $capability,string $scope):array
    {
        $operator=in_array($capability,['administrator','registry','deployment'],true);
        if(!in_array($capability,['contributor','editor','reviewer','steward','administrator','registry','deployment'],true)||!in_array($scope,$operator?['catalog']:['chemical','composition','presentation'],true)
            ||($this->repository->actor($actor,true)['status']??null)!=='active'){throw new MedicinePersistenceException('Medicine governance access denied.',403);}
        foreach($this->repository->grants($actor,$capability,$scope) as $grant){
            if((string)$grant['user_id']!==(string)$actor||$grant['capability']!==$capability||!in_array($grant['scope'],[$scope,'catalog'],true)
                ||(string)$grant['is_active']!=='1'||$grant['revoked_at']!==null||$grant['revoked_by']!==null||$grant['revocation_reason']!==null){continue;}
            $now=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
            $permanent=$grant['expires_at']===null;
            if($permanent&&!$this->initialOwnerAdministrator($grant)){continue;}
            foreach($permanent?['granted_at']:['granted_at','expires_at'] as $field){$time=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u',(string)$grant[$field],new DateTimeZone('UTC'));if(!$time||$time->format('Y-m-d H:i:s.u')!==$grant[$field]){throw new MedicinePersistenceException('Medicine authority state is invalid.',403);}}
            if($grant['granted_at']>$now||(!$permanent&&$grant['expires_at']<=$now)){continue;}
            if($capability==='reviewer'){
                if(empty($grant['qualification_assessed_by'])||!is_string($grant['qualification_reference'])||trim($grant['qualification_reference'])===''||!in_array($grant['qualification_scope'],[$scope,'catalog'],true)){continue;}
                $this->policy->requireQualificationBinding($this->repository,$grant,$scope);
            }
            return $grant;
        }
        throw new MedicinePersistenceException('Medicine governance access denied.',403);
    }
    private function initialOwnerAdministrator(array $grant):bool {
        if($grant['capability']!=='administrator'||$grant['scope']!=='catalog'||(string)$grant['user_id']!==(string)$grant['granted_by']
            ||!$this->policy->bootstrapOwner((int)$grant['user_id'])||$grant['qualification_reference']!==null||$grant['qualification_assessed_by']!==null||$grant['qualification_scope']!==null){return false;}
        $decision=$this->policy->bootstrapDecision();
        foreach($this->repository->events('grant',(string)$grant['id']) as $event){
            if($event['action']!=='grant'||(string)$event['revision']!=='1'||(string)$event['actor_id']!==(string)$grant['user_id']
                ||!hash_equals(hash('sha256',$event['snapshot_json'],true),$event['snapshot_hash'])){continue;}
            $snapshot=json_decode($event['snapshot_json'],true);$d=$snapshot['establishment']??[];
            if(($d['mechanism']??null)!=='initial_owner_bootstrap'||(string)($d['owner_user_id']??'')!==(string)$grant['user_id']
                ||($d['decision_reference']??null)!==$decision['decision_reference']||($d['policy_version']??null)!==$decision['policy_version']){continue;}
            foreach(['user_id','capability','scope','granted_by','granted_at','expires_at','qualification_reference','qualification_assessed_by','qualification_scope'] as $field){if(!array_key_exists($field,$snapshot)||($snapshot[$field]===null)!==($grant[$field]===null)||(string)$snapshot[$field]!==(string)$grant[$field]){continue 2;}}
            return true;
        }
        return false;
    }

    public function requireCurrent(string $scope,string $id):void {$this->eligibility->requireCurrent($scope,$id);}
    /** This gate grants no registry/deployment execution API. */
    public function requireOperator(int $actor,string $capability):void {
        if(($this->repository->actor($actor,true)['status']??null)!=='active'){throw new MedicinePersistenceException('Medicine operator access denied.',403);}
        if($capability==='administrator'){
            try{$this->policy->requireAdministrator($actor);return;}catch(MedicinePersistenceException $e){if($e->getCode()!==403){throw $e;}}
        }elseif($capability==='registry'&&$this->policy->listed($actor,'registry_operator_user_ids')){return;}
        elseif($capability==='deployment'&&($this->policy->owner($actor)||$this->policy->listed($actor,'deployment_operator_user_ids'))){return;}
        elseif(!in_array($capability,['registry','deployment'],true)){throw new MedicinePersistenceException('Invalid Medicine operator capability.',403);}
        $this->requireGovernance($actor,$capability,'catalog');
    }
    public function preparePilot(string $scope,array $references):void {
        $pilot=$this->policy->pilot();$ids=$pilot['ids'];usort($ids,fn($a,$b)=>strlen($a)<=>strlen($b)?:strcmp($a,$b));
        // Lock the complete bounded pilot set to serialize quota checks and creation.
        foreach($ids as $id){$row=$this->repository->registry('S',$id);if(!$row||(string)$row['is_active']!=='1'){throw new MedicinePersistenceException('Medicine pilot registry target is unavailable.',409);}}
        foreach($references as $ref){if($ref['tag']!=='S'||!in_array($ref['id'],$ids,true)){throw new MedicinePersistenceException('Medicine reference is outside the approved pilot.',403);}}
        if(count($this->repository->pilotRecords($pilot['code'],$scope))>=$pilot['limit']){throw new MedicinePersistenceException('Medicine pilot quota reached.',409);}
    }
    public function requirePilotTarget(string $scope,string $id):void {
        if(!$this->policy->governedPilotRequired()){return;}
        $this->eligibility->requireLifecycle($scope,$id);
        $pilot=$this->policy->pilot();$row=$this->repository->pilotTarget($scope,$id);
        if(!$row||$row['pilot_code']!==$pilot['code']){throw new MedicinePersistenceException('Medicine target is outside the approved pilot.',403);}
        $snapshot=$this->eligibility->snapshot($scope,$id,false);
        $registry=$scope==='chemical'?[$snapshot['registry']]:array_column(($scope==='composition'?$snapshot:$snapshot['composition'])['dependencies'],'registry');
        foreach($registry as $ref){if($ref['tag']!=='S'||!in_array($ref['id'],$pilot['ids'],true)){throw new MedicinePersistenceException('Medicine pilot reference is no longer approved.',403);}}
    }
    public function recordCreation(string $scope,string $id,int $actor):void {
        $pilot=$this->policy->pilot();$grant=$this->requireGovernance($actor,'contributor',$scope);
        $snapshot=$this->eligibility->json($this->eligibility->snapshot($scope,$id,false));
        $event=$this->repository->appendEvent($scope,$id,['actor_id'=>$actor,'authority_grant_id'=>$grant['id'],'action'=>'create','revision'=>1,'previous_state'=>null,'new_state'=>'candidate','rationale'=>'Internal Medicine pilot '.$pilot['code'],'evidence_json'=>'[]','snapshot_json'=>$snapshot,'snapshot_hash'=>hash('sha256',$snapshot,true),'independent'=>0,'occurred_at'=>(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u')]);
        $this->repository->insertPilot($pilot['code'],$scope,$id,$event);
    }
}

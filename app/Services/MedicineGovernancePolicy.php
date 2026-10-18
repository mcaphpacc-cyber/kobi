<?php
declare(strict_types=1);
namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

/** Owner-controlled v1 configuration; never inferred from request roles or websites. */
class MedicineGovernancePolicy
{
    protected function settings():array { $value=function_exists('config')?config('medicine_governance'):null;return is_array($value)?$value:[]; }
    private function deny(string $message):never {throw new MedicinePersistenceException($message,403);}
    public function owner(int $actor):bool {return $this->listed($actor,'owner_user_ids');}
    /** Inert trust-root designation; confers no ordinary administrator/operator capability. */
    public function bootstrapDecision():array {
        $d=$this->settings()['owner_bootstrap']??null;
        if(($this->settings()['owner_user_ids']??[])!==[]||!is_array($d)||!is_int($d['owner_user_id']??null)||$d['owner_user_id']<1
            ||($d['administrator_user_id']??null)!==$d['owner_user_id']
            ||!is_string($d['decision_reference']??null)||trim($d['decision_reference'])===''
            ||($d['policy_version']??null)!=='initial-owner-v1'){$this->deny('Medicine owner bootstrap is not configured.');}
        return $d;
    }
    public function bootstrapOwner(int $actor):bool {
        try{return $actor===$this->bootstrapDecision()['owner_user_id'];}catch(MedicinePersistenceException $e){return false;}
    }
    public function mayDelegateOperator(int $actor):bool {return $this->owner($actor)||$this->bootstrapOwner($actor);}
    public function listed(int $actor,string $key):bool {
        $ids=$this->settings()[$key]??[];
        if(!is_array($ids)||!array_is_list($ids)||$actor<1){return false;}
        foreach($ids as $id){if(!is_int($id)||$id<1){return false;}}
        return in_array($actor,$ids,true);
    }
    public function requireAdministrator(int $actor):void {if(!$this->owner($actor)){$this->deny('Medicine administration access denied.');}}
    public function date(mixed $value):string {
        $time=is_string($value)?DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u',$value,new DateTimeZone('UTC')):false;
        if(!$time||$time->format('Y-m-d H:i:s.u')!==$value){$this->deny('Medicine policy date is invalid.');}return $value;
    }
    private function now():string {return (new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');}
    public function requireQualification(array $grant,string $scope):void {
        $raw=$grant['qualification_reference']??null;
        $credential=is_string($raw)?json_decode($raw,true,16):null;
        $fields=['profession','registration','jurisdiction','authority','status','verified_at','renewal_due','verifier'];
        if(!is_array($credential)||array_diff(array_keys($credential),array_merge($fields,['qualification_event_id','qualification_sha256']))||array_diff($fields,array_keys($credential))
            ||!in_array($credential['profession'],['doctor','pharmacist'],true)||$credential['status']!=='verified'
            ||(string)$credential['verifier']!==(string)($grant['qualification_assessed_by']??'')||empty($grant['qualification_assessed_by'])
            ||!in_array($grant['qualification_scope']??null,[$scope,'catalog'],true)){$this->deny('Verified Medicine reviewer credential is required.');}
        foreach(['registration','jurisdiction','authority'] as $field){if(!is_string($credential[$field])||trim($credential[$field])===''||strlen($credential[$field])>512){$this->deny('Medicine reviewer credential is incomplete.');}}
        if($this->date($credential['verified_at'])>$this->now()){$this->deny('Medicine credential verification is not current.');}
        if($credential['renewal_due']!==null){$due=$this->date($credential['renewal_due']);if($due<=$this->now()||$due<=$credential['verified_at']||($grant['expires_at']??'9999')>$due){$this->deny('Medicine reviewer credential has expired or needs renewal.');}}
    }
    /** No administrator-submitted assertion substitutes for deployment-controlled verification provenance. */
    public function credentialVerification(string $reference,int $user,string $scope,string $hash,int $verifier):array {
        $record=$this->settings()['reviewer_verifications'][$reference]??null;
        if(!is_array($record)||($record['user_id']??null)!==$user||($record['scope']??null)!==$scope||($record['credential_sha256']??null)!==$hash||($record['verifier_id']??null)!==$verifier
            ||!is_string($record['provenance_sha256']??null)||!preg_match('/\A[a-f0-9]{64}\z/D',$record['provenance_sha256'])
            ||$this->date($record['verified_at']??null)>$this->now()||$this->date($record['valid_until']??null)<=$this->now()){$this->deny('Independent credential verification provenance is required.');}
        return $record;
    }
    public function requireQualificationBinding(\App\Repositories\MedicineGovernanceRepository $repository,array $grant,string $scope):array {
        $this->requireQualification($grant,$scope);$c=json_decode($grant['qualification_reference'],true,16);
        $row=$repository->qualificationLatest((int)$grant['user_id']);
        if(!$row||$row['state']!=='verified'||(string)($c['qualification_event_id']??'')!==(string)$row['id']
            ||!hash_equals(hash('sha256',$row['snapshot_json'],true),$row['snapshot_hash'])||($c['qualification_sha256']??null)!==bin2hex($row['snapshot_hash'])){$this->deny('Current journal-bound reviewer qualification is required.');}
        $s=json_decode($row['snapshot_json'],true,32,JSON_THROW_ON_ERROR);$credential=$s['credential']??null;
        $copy=$c;unset($copy['qualification_event_id'],$copy['qualification_sha256']);
        if(!is_array($credential)||$copy!=$credential||(int)($s['user_id']??0)!==(int)$grant['user_id']||(int)$row['actor_id']===(int)$grant['user_id']
            ||!in_array($s['scope']??null,[$scope,'catalog'],true)||$this->date($s['verification']['valid_until']??null)<=$this->now()){$this->deny('Reviewer qualification is stale or mismatched.');}
        return ['qualification_event_id'=>(string)$row['id'],'qualification_sha256'=>bin2hex($row['snapshot_hash']),'user_id'=>(string)$grant['user_id']];
    }
    /** Tier comes only from existing configured policy, never the proposed package classification. */
    public function evidenceConfiguration(array $evidence,string $scope):array {
        $this->requireEvidence($evidence,$scope);$source=$this->settings()['evidence_sources'][$evidence['issuer']];
        return ['tier'=>$source['tier'],'purpose'=>'identity','scope'=>$scope,'configuration_sha256'=>hash('sha256',json_encode($source,JSON_THROW_ON_ERROR))];
    }
    public function requireEvidence(array $evidence,string $scope):void {
        $source=$this->settings()['evidence_sources'][$evidence['issuer']??'']??null;
        $tiers=$this->settings()['authoritative_tiers']??[1,2];
        if(!is_array($tiers)||array_diff($tiers,[1,2,3,4])||!is_array($source)||($source['active']??null)!==true
            ||!is_int($source['tier']??null)||!in_array($source['tier'],[1,2,3,4],true)||!in_array($source['tier'],$tiers,true)
            ||!is_array($source['scopes']??null)||!in_array($scope,$source['scopes'],true)||($evidence['kind']??null)!=='identity'){$this->deny('Authoritative identity evidence is required.');}
        if($this->date($source['valid_until']??null)<=$this->now()){$this->deny('Medicine evidence source requires renewed appraisal.');}
    }
    /** Appraised record configuration binds content to actual target/current facts, not a submitted tier. */
    public function requireEvidenceFor(array $evidence,string $scope,string $id,string $snapshotHash):void {
        $this->requireEvidence($evidence,$scope);
        $records=$this->settings()['evidence_sources'][$evidence['issuer']]['records']??[];
        if(!is_array($records)){$this->deny('Medicine evidence appraisal is unavailable.');}
        foreach($records as $record){
            if(!is_array($record)||($record['scope']??null)!==$scope||(string)($record['target_id']??'')!==$id||($record['snapshot_hash']??null)!==$snapshotHash){continue;}
            foreach(['record','version','locator','source_sha256'] as $field){if(($record[$field]??null)!==($evidence[$field]??null)){continue 2;}}
            if(($record['identity_appraised']??null)===true&&$this->date($record['valid_until']??null)>$this->now()){return;}
        }
        $this->deny('Identity evidence does not support these current Medicine facts.');
    }
    public function pilot():array {
        $s=$this->settings();$targets=$s['pilot_substances']??[];$limit=$s['pilot_limit']??null;$code=$s['pilot_code']??null;
        if(($s['pilot_enabled']??null)!==true||!is_int($limit)||$limit<1||$limit>10||!is_string($code)||!preg_match('/\A[a-z0-9_-]{1,64}\z/D',$code)
            ||!is_array($targets)||!array_is_list($targets)||count($targets)<1||count($targets)>$limit){$this->deny('Medicine internal pilot is disabled.');}
        $ids=[];$representatives=[];
        foreach($targets as $target){$id=$target['substance_id']??null;$representative=$target['representative_reference']??null;
            if(!is_array($target)||!is_string($id)||!preg_match('/\A[1-9][0-9]{0,19}\z/D',$id)||($target['unambiguous']??null)!==true
                ||!is_string($representative)||trim($representative)===''||in_array($id,$ids,true)||in_array($representative,$representatives,true)){$this->deny('Medicine pilot representatives require explicit appraisal.');}
            $ids[]=$id;$representatives[]=$representative;
        }
        return ['code'=>$code,'limit'=>$limit,'ids'=>$ids,'actions'=>$s['pilot_actions']??[]];
    }
    public function requireSubmission(?array $event,int $revision):void {
        if(!$event||$event['action']!=='submit'||(int)$event['revision']!==$revision){$this->deny('Submit this revision for independent Medicine review.');}
    }
    public function governedPilotRequired():bool {return true;}
    /** Deployment-controlled attestations; no request may supply activation authority. */
    public function activation():array {
        $s=$this->settings();$pilot=$this->pilot();$gate=$s['pilot_activation']??null;
        if(($s['pilot_internal_enabled']??null)!==true||count($pilot['ids'])<5||!is_array($gate)
            ||($gate['pilot_code']??null)!==$pilot['code']||($gate['public_disabled']??null)!==true){$this->deny('Medicine pilot activation prerequisites are unavailable.');}
        foreach(['administrator_id','deployment_operator_id','reviewer_id'] as $field){if(!is_int($gate[$field]??null)||$gate[$field]<1){$this->deny('Named Medicine pilot roles are required.');}}
        foreach(['backup_reference','deployment_reference'] as $field){if(!is_string($gate[$field]??null)||trim($gate[$field])===''||strlen($gate[$field])>512){$this->deny('Verified pilot backup and deployment are required.');}}
        $entries=$gate['enrollments']??null;
        if(!is_array($entries)||!array_is_list($entries)||count($entries)<5||count($entries)>10){$this->deny('Five to ten explicit approved pilot enrollments are required.');}
        $seen=[];
        foreach($entries as $entry){
            if(!is_array($entry)||!in_array($entry['scope']??null,['chemical','composition'],true)){$this->deny('Invalid pilot activation scope.');}
            foreach(['target_id','revision','approval_event_id','enrollment_event_id'] as $field){if(!is_string($entry[$field]??null)||!preg_match('/\A[1-9][0-9]{0,19}\z/D',$entry[$field])){$this->deny('Explicit current pilot revisions are required.');}}
            $key=$entry['scope'].':'.$entry['target_id'];if(in_array($key,$seen,true)){$this->deny('Duplicate pilot activation target.');}$seen[]=$key;
        }
        return $gate;
    }
}

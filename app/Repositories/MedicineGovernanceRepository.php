<?php
declare(strict_types=1);
namespace App\Repositories;

use RuntimeException;

/** Internal primitives on the same PDO/transaction convention as Medicine persistence. */
class MedicineGovernanceRepository extends MedicineRepository
{
    /** Caller locks the subject user first, including an absent first journal entry. */
    public function qualificationLatest(int $user):?array {return $this->fetch('SELECT * FROM medicine_reviewer_qualifications WHERE user_id=:user ORDER BY revision DESC LIMIT 1 FOR UPDATE',['user'=>$user]);}
    public function appendQualification(array $row):string {
        $this->execute('INSERT INTO medicine_reviewer_qualifications(user_id,revision,state,snapshot_json,snapshot_hash,actor_id,occurred_at) VALUES(:user_id,:revision,:state,:snapshot_json,:snapshot_hash,:actor_id,:occurred_at)',$row);return $this->lastInsertId();
    }
    public function appraisalLatest(string $substance,int $revision):?array {return $this->fetch('SELECT * FROM medicine_evidence_appraisals WHERE substance_id=:substance AND provisional_revision=:revision ORDER BY decision_revision DESC LIMIT 1 FOR UPDATE',['substance'=>$substance,'revision'=>$revision]);}
    /** Unlocked hint only; service locks reviewer before registry, then revalidates the journal. */
    public function appraisalReviewer(string $id):?int {$r=$this->fetch('SELECT reviewer_id FROM medicine_evidence_appraisals WHERE substance_id=:id ORDER BY provisional_revision DESC,decision_revision DESC LIMIT 1',['id'=>$id]);return $r===null?null:(int)$r['reviewer_id'];}
    public function provisionalHistory(string $id):array {return $this->fetchAll('SELECT * FROM medicine_provisional_identity_revisions WHERE substance_id=:id ORDER BY revision FOR UPDATE',['id'=>$id]);}
    public function appendAppraisal(array $row):string {
        $this->execute('INSERT INTO medicine_evidence_appraisals(substance_id,provisional_revision,decision_revision,state,reviewer_id,qualification_event_id,authority_grant_id,snapshot_json,snapshot_hash,occurred_at) VALUES(:substance_id,:provisional_revision,:decision_revision,:state,:reviewer_id,:qualification_event_id,:authority_grant_id,:snapshot_json,:snapshot_hash,:occurred_at)',$row);return $this->lastInsertId();
    }
    private const TARGETS = ['chemical'=>['kobi_medicine_chemical_refs','chemical_ref_id'], 'composition'=>['kobi_medicine_identities','medicine_identity_id'], 'presentation'=>['kobi_medicine_presentations','medicine_presentation_id']];
    /** Called only after locking the fixed configured owner user; history, not active count. */
    public function authorityInitialized():bool {return $this->fetch('SELECT id FROM medicine_authority_grants ORDER BY id LIMIT 1 FOR UPDATE')!==null;}
    /** Read-only readiness/status; final initialization remains locked in the command. */
    public function ownerBootstrapState():array {
        $column=$this->fetch("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='medicine_authority_grants' AND COLUMN_NAME='expires_at'");
        return ['schema_ready'=>($column['IS_NULLABLE']??null)==='YES','initialized'=>$this->fetch('SELECT id FROM medicine_authority_grants ORDER BY id LIMIT 1')!==null];
    }
    public function grants(int $actor,string $capability,string $scope,bool $lock=true):array
    {
        return $this->fetchAll('SELECT * FROM medicine_authority_grants WHERE user_id=:actor AND capability=:capability AND scope IN (:scope,\'catalog\') ORDER BY id'.($lock?' FOR UPDATE':''),['actor'=>$actor,'capability'=>$capability,'scope'=>$scope]);
    }
    public function grant(string $id,bool $lock=true):?array {return $this->fetch('SELECT * FROM medicine_authority_grants WHERE id=:id'.($lock?' FOR UPDATE':''),['id'=>$id]);}
    public function insertGrant(array $r):string
    {
        $this->execute('INSERT INTO medicine_authority_grants(user_id,capability,scope,is_active,qualification_reference,qualification_assessed_by,qualification_scope,granted_by,granted_at,expires_at) VALUES(:user_id,:capability,:scope,:is_active,:qualification_reference,:qualification_assessed_by,:qualification_scope,:granted_by,:granted_at,:expires_at)',$r);return $this->lastInsertId();
    }
    public function revokeGrant(string $id,int $actor,string $now,string $reason):void {$this->execute('UPDATE medicine_authority_grants SET is_active=0,revoked_by=:actor,revoked_at=:now,revocation_reason=:reason WHERE id=:id AND revoked_at IS NULL',['id'=>$id,'actor'=>$actor,'now'=>$now,'reason'=>$reason]);}
    public function target(string $kind,string $id,bool $lock=true):?array
    {
        [$table,$key]=self::TARGETS[$kind]??throw new RuntimeException('Invalid internal Medicine target.');
        return $this->fetch('SELECT * FROM '.$table.' WHERE '.$key.'=:id'.($lock?' FOR UPDATE':''),['id'=>$id]);
    }
    public function changeTarget(string $kind,string $id,array $r):void
    {
        [$table,$key]=self::TARGETS[$kind]??throw new RuntimeException('Invalid internal Medicine target.');
        $allowed=['identity_status','metadata_revision','display_name','updated_at','verified_by','verified_at','verification_reference','retired_by','retired_at','retirement_reason'];
        if(!$r || array_diff(array_keys($r),$allowed) || ($kind==='chemical'&&array_key_exists('display_name',$r))){throw new RuntimeException('Invalid internal Medicine fields.');}
        $sets=[];foreach($r as $column=>$value){$sets[]=$column.'=:'.$column;}$r['target_id']=$id;
        $this->execute('UPDATE '.$table.' SET '.implode(',',$sets).' WHERE '.$key.'=:target_id',$r);
    }
    public function registry(string $tag,string $id):?array
    {
        $table=$tag==='S'?'kobi_substances':($tag==='F'?'kobi_substance_forms':throw new RuntimeException('Invalid internal chemical tag.'));
        $key=$tag==='S'?'substance_id':'form_id';return $this->fetch('SELECT * FROM '.$table.' WHERE '.$key.'=:id FOR UPDATE',['id'=>$id]);
    }
    public function assessment(string $id):?array {return $this->fetch('SELECT * FROM medicine_registry_assessments WHERE chemical_ref_id=:id FOR UPDATE',['id'=>$id]);}
    public function pilotRecords(string $code,string $scope):array {return $this->fetchAll('SELECT * FROM medicine_pilot_records WHERE pilot_code=:code AND scope=:scope ORDER BY id FOR UPDATE',['code'=>$code,'scope'=>$scope]);}
    public function pilotTarget(string $scope,string $id):?array {return $this->fetch('SELECT * FROM medicine_pilot_records WHERE '.$this->eventColumn($scope).'=:id FOR UPDATE',['id'=>$id]);}
    public function insertPilot(string $code,string $scope,string $id,string $event):void {
        $this->execute('INSERT INTO medicine_pilot_records(pilot_code,scope,'.$this->eventColumn($scope).',creation_event_id) VALUES(:code,:scope,:subject,:event)',['code'=>$code,'scope'=>$scope,'subject'=>$id,'event'=>$event]);
    }
    /** Registry row must be locked before this read; external activity writes record events in that transaction. */
    public function registryLifecycle(string $tag,string $id):string
    {
        $column=$tag==='S'?'substance_id':($tag==='F'?'form_id':throw new RuntimeException('Invalid internal chemical tag.'));
        $row=$this->fetch('SELECT id FROM medicine_registry_lifecycle_events WHERE '.$column.'=:id ORDER BY id DESC LIMIT 1 FOR UPDATE',['id'=>$id]);
        return $row===null?'0':(string)$row['id'];
    }
    public function saveAssessment(array $r,bool $exists):void
    {
        if(!$exists){$this->execute('INSERT INTO medicine_registry_assessments(chemical_ref_id,candidate_identity_id,revision,assessment_state,registry_hash,registry_snapshot,decision_event_id,updated_by,updated_at) VALUES(:chemical_ref_id,:candidate_identity_id,:revision,:assessment_state,:registry_hash,:registry_snapshot,:decision_event_id,:updated_by,:updated_at)',$r);return;}
        $this->execute('UPDATE medicine_registry_assessments SET candidate_identity_id=:candidate_identity_id,revision=:revision,assessment_state=:assessment_state,registry_hash=:registry_hash,registry_snapshot=:registry_snapshot,decision_event_id=:decision_event_id,updated_by=:updated_by,updated_at=:updated_at WHERE chemical_ref_id=:chemical_ref_id',$r);
    }
    private function eventColumn(string $kind):string {return ['grant'=>'grant_subject_id','chemical'=>'chemical_ref_id','composition'=>'medicine_identity_id','presentation'=>'medicine_presentation_id'][$kind]??throw new RuntimeException('Invalid internal event target.');}
    public function events(string $kind,string $id):array {return $this->fetchAll('SELECT * FROM medicine_governance_events WHERE '.$this->eventColumn($kind).'=:id ORDER BY id FOR UPDATE',['id'=>$id]);}
    public function latest(string $kind,string $id):?array {return $this->fetch('SELECT * FROM medicine_governance_events WHERE '.$this->eventColumn($kind).'=:id ORDER BY id DESC LIMIT 1 FOR UPDATE',['id'=>$id]);}
    public function event(string $id):?array {return $this->fetch('SELECT * FROM medicine_governance_events WHERE id=:id FOR UPDATE',['id'=>$id]);}
    public function appendEvent(string $kind,string $id,array $r):string
    {
        $column=$this->eventColumn($kind);
        $this->execute('INSERT INTO medicine_governance_events('.$column.',actor_id,authority_grant_id,action,revision,previous_state,new_state,rationale,evidence_json,snapshot_json,snapshot_hash,independent,occurred_at) VALUES(:subject_id,:actor_id,:authority_grant_id,:action,:revision,:previous_state,:new_state,:rationale,:evidence_json,:snapshot_json,:snapshot_hash,:independent,:occurred_at)',['subject_id'=>$id]+$r);return $this->lastInsertId();
    }
    /** Registry candidate must be locked first, including the absent-first-revision case. */
    public function provisionalLatest(string $substance):?array
    {
        return $this->fetch('SELECT * FROM medicine_provisional_identity_revisions WHERE substance_id=:id ORDER BY revision DESC LIMIT 1 FOR UPDATE',['id'=>$substance]);
    }
    public function appendProvisional(array $r):void
    {
        $fields=['substance_id','revision','state','snapshot_json','snapshot_hash','evidence_json','evidence_hash','actor_id','authority_grant_id','rationale','occurred_at'];
        if(array_diff(array_keys($r),$fields)||array_diff($fields,array_keys($r))){throw new RuntimeException('Invalid internal provisional revision.');}
        $this->execute('INSERT INTO medicine_provisional_identity_revisions (`'.implode('`,`',$fields).'`) VALUES (:'.implode(',:',$fields).')',$r);
    }
}

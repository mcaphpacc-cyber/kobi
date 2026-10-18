<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TreatmentGovernanceRepository;
use App\Repositories\TreatmentSourceRepository;
use App\Repositories\TreatmentEditorialPermissionRepository;
use RuntimeException;

class TreatmentGovernanceService
{
    public function __construct(private TreatmentGovernanceRepository $repository, private TreatmentSourceRepository $sources, private TreatmentEditorialPermissionRepository $permissions, private TreatmentEditorialAuthorizationService $authorization, private TreatmentGovernanceRules $rules, private TreatmentGovernanceSnapshot $snapshots, private TreatmentGovernancePolicy $policy) {}

    /** Recomputed on every deadlock retry; source locks precede protocol locks. */
    private function command(int $id, int $revision, string $expectedState, string $action, callable $work, array $extraSources = []): mixed
    {
        $this->rules->id($id); $this->rules->id($revision); $this->rules->value($expectedState, TreatmentGovernanceRules::REVIEW);
        return $this->repository->transaction(function () use ($id,$revision,$expectedState,$action,$work,$extraSources) {
            $initial=$this->repository->find($id);
            if (!$initial) { throw new RuntimeException('Treatment protocol unavailable.'); }
            $actor=$this->authorization->require($action, (int)$initial['treatment_system_id'], true);
            $system=$this->repository->system((int)$initial['treatment_system_id'], true);
            if (!$system) { throw new RuntimeException('Treatment system unavailable.'); }
            $before=$this->repository->aggregate($id);
            $sourceIds=array_merge(array_column($before['sources'],'id'), $extraSources); $sourceIds=array_values(array_unique(array_map('intval',$sourceIds))); sort($sourceIds,SORT_NUMERIC);
            $lockedSources=[];
            foreach ($sourceIds as $sourceId) {
                $source=$this->sources->find($this->rules->id($sourceId),true);
                if (!$source) { throw new RuntimeException('Treatment source unavailable.'); }
                $lockedSources[$sourceId]=$source;
            }
            $protocol=$this->repository->find($id,true);
            if (!$protocol || (int)$protocol['content_revision'] !== $revision || $protocol['review_state'] !== $expectedState || (int)$protocol['treatment_system_id'] !== (int)$initial['treatment_system_id']) { throw new RuntimeException('Treatment content changed. Reload and retry.'); }
            $this->rules->protocol($protocol);
            $aggregate=$this->repository->aggregate($id,true); $aggregate['protocol']=$protocol;
            $currentIds=array_values(array_unique(array_map('intval',array_merge(array_column($aggregate['protocol_sources'],'source_id'),array_column($aggregate['item_sources'],'source_id')))));
            if (array_diff($currentIds,$sourceIds)) { throw new RuntimeException('Treatment references changed. Reload and retry.'); }
            // Use current locked source states rather than a REPEATABLE READ snapshot.
            $aggregate['sources']=array_values(array_intersect_key($lockedSources,array_flip($currentIds)));
            $aggregate['locked_sources']=$lockedSources;
            return $work($aggregate,$actor,$system);
        });
    }

    private function event(array $protocol, string $type, ?int $actor, array $extra = []): int
    {
        $event=array_replace(['protocol_id'=>(int)$protocol['id'],'content_revision'=>(int)$protocol['content_revision'],'event_type'=>$type,'actor_id'=>$actor,'occurred_at'=>$this->rules->now(),'locales'=>'','independence_confirmed'=>0],$extra);
        $this->rules->event($event);
        return $this->repository->appendEvent($event);
    }

    private function saveInvalidation(array $protocol, int $actor, string $type, string $reason, bool $source = false): array
    {
        $next=$this->rules->invalidate($protocol,$source);
        $fields=array_intersect_key($next,array_flip(['content_revision','review_state','approved_review_id','publication_status','published_locales']));
        if (!$source && $protocol['publication_status']==='withdrawn') { $fields['publication_status']='unpublished'; }
        $fields['updated_by']=$actor; $fields['updated_at']=$this->rules->now();
        $this->repository->updateProtocol((int)$protocol['id'],$fields);
        $this->event($next,$type,$actor,['notes'=>$reason]);
        return array_replace($next,$fields);
    }

    private function contentValues(array $changes, bool $item): array
    {
        $allowed=$item ? TreatmentGovernanceRules::ITEM_CONTENT : TreatmentGovernanceRules::PROTOCOL_CONTENT;
        if (array_diff(array_keys($changes),$allowed) || array_key_exists('disease_id',$changes) || array_key_exists('treatment_system_id',$changes)) { throw new RuntimeException('Invalid treatment field.'); }
        foreach ($changes as $key=>&$value) {
            if ($key==='evidence_classification') { $value=$this->rules->value($value,TreatmentGovernanceRules::EVIDENCE); }
            elseif ($key==='content_purpose') { if ($value!==null) { $value=$this->rules->value($value,TreatmentGovernanceRules::PURPOSE); } }
            elseif ($key==='item_type') { if ($value!==null) { $value=$this->rules->value($value,TreatmentGovernanceRules::ITEM_TYPES); } }
            elseif ($key==='is_included') { $value=$this->rules->flag($value); }
            elseif ($key==='sort_order') {
                if ($value===null) { continue; } // Preserve nullable ordering on excluded legacy rows.
                if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int)$value<0 || (string)(int)$value!==(string)$value || (int)$value>2147483647) { throw new RuntimeException('Invalid treatment ordering.'); }
                $value=(int)$value;
            }
            else { $value=$this->rules->text($value,str_starts_with($key,'title_') ? 1020 : 65535); if (str_starts_with($key,'title_') && $value!==null && mb_strlen($value)>255) { throw new RuntimeException('Invalid treatment content.'); } }
        }
        unset($value); return $changes;
    }

    private function initialContent(array $content,bool $item): array
    {
        $allowed=$item ? ['item_type','title_en','title_hi','instructions_en','instructions_hi','sort_order'] : ['title_en','title_hi','overview_en','overview_hi'];
        if (array_diff(array_keys($content),$allowed)) { throw new RuntimeException('Invalid treatment field.'); }
        $content=$this->contentValues($content,$item);
        foreach ($content as $key=>&$value) { if ($key!=='sort_order' && is_string($value) && trim($value)==='') { $value=null; } }
        unset($value);
        if (($content['title_en']??null)===null && ($content['title_hi']??null)===null) { throw new RuntimeException('A treatment title in English or Hindi is required.'); }
        if ($item) {
            $content['item_type']=$this->rules->value($content['item_type']??null,TreatmentGovernanceRules::ITEM_TYPES);
            $content['sort_order']=$content['sort_order']??0;
        }
        return $content;
    }

    /** Initial protocol, optional first item and contributor event share revision 1. */
    public function createDraft(int $diseaseId,int $systemId,array $content,?array $firstItem=null): int
    {
        $this->rules->id($diseaseId); $this->rules->id($systemId);
        $content=$this->initialContent($content,false);
        $firstItem=$firstItem===null ? null : $this->initialContent($firstItem,true);
        return $this->repository->transaction(function () use ($diseaseId,$systemId,$content,$firstItem) {
            $actor=$this->authorization->require('edit',$systemId,true);
            $system=$this->repository->system($systemId,true);
            if (!$system || (int)$system['is_active']!==1) { throw new RuntimeException('An active treatment system is required.'); }
            if (!$this->repository->diseaseExists($diseaseId)) { throw new RuntimeException('Treatment disease unavailable.'); }
            $row=$this->rules->legacyProtocol(array_replace(['disease_id'=>$diseaseId,'treatment_system_id'=>$systemId,'title_en'=>null,'title_hi'=>null,'overview_en'=>null,'overview_hi'=>null],$content));
            $row['updated_by']=$actor; $row['updated_at']=$this->rules->now();
            $this->rules->protocol($row);
            if ($this->repository->matchingProtocol($row)) { throw new RuntimeException('This treatment protocol content already exists. Open the existing protocol instead.'); }
            $id=$this->repository->insertProtocol($row); $row['id']=$this->rules->id($id);
            if ($firstItem!==null) { $this->repository->insertItem($this->newItem($id,$firstItem)); }
            $this->event($row,'content_changed',$actor,['notes'=>'New treatment protocol draft created.']);
            return $id;
        });
    }

    private function newItem(int $id,array $content): array
    {
        return $this->rules->legacyItem(array_replace(['protocol_id'=>$id,'item_type'=>null,'title_en'=>null,'title_hi'=>null,'instructions_en'=>null,'instructions_hi'=>null,'sort_order'=>0],$content));
    }

    public function addItem(int $id,int $revision,string $state,array $content): int
    {
        $content=$this->initialContent($content,true);
        return $this->command($id,$revision,$state,'edit',function ($aggregate,$actor,$system) use ($content) {
            $p=$aggregate['protocol'];
            if ($p['publication_status']==='published') { throw new RuntimeException('Withdraw published treatment content before editing.'); }
            if ((int)$system['is_active']!==1) { throw new RuntimeException('An active treatment system is required.'); }
            $this->saveInvalidation($p,$actor,'content_changed','New treatment item added.');
            return $this->repository->insertItem($this->newItem((int)$p['id'],$content));
        });
    }

    /** All protocol/item edits share one revision and transaction. */
    public function edit(int $id,int $revision,string $state,array $protocolChanges,array $itemChanges=[]): bool
    {
        $protocolChanges=$this->contentValues($protocolChanges,false);
        foreach ($itemChanges as $itemId=>$changes) { $this->rules->id($itemId); $itemChanges[$itemId]=$this->contentValues($changes,true); }
        return $this->command($id,$revision,$state,'edit',function ($aggregate,$actor) use ($protocolChanges,$itemChanges) {
            $protocol=$aggregate['protocol'];
            if ($protocol['publication_status']==='published') { throw new RuntimeException('Withdraw published treatment content before editing.'); }
            $changed=[]; foreach ($protocolChanges as $key=>$value) { if (($protocol[$key]??null)!==$value) { $changed[$key]=$value; } }
            $items=[]; foreach ($aggregate['items'] as $item) { $items[(int)$item['id']]=$item; }
            $updates=[];
            foreach ($itemChanges as $itemId=>$changes) {
                if (!isset($items[$itemId])) { throw new RuntimeException('Treatment item unavailable.'); }
                foreach ($changes as $key=>$value) {
                    $old=$items[$itemId][$key]??null;
                    if (in_array($key,['is_included','sort_order'],true) && $old!==null) { $old=(int)$old; }
                    if ($old!==$value) { $updates[$itemId][$key]=$value; }
                }
            }
            $orders=[];
            foreach ($items as $itemId=>$item) {
                $candidate=array_replace($item,$updates[$itemId]??[]);
                if ($this->rules->flag($candidate['is_included'])===1) {
                    $order=$this->rules->id($candidate['sort_order']);
                    if (isset($orders[$order])) { throw new RuntimeException('Included treatment ordering must be unique.'); }
                    $orders[$order]=true;
                }
            }
            if (!$changed && !$updates) { return false; }
            $next=$this->saveInvalidation($protocol,$actor,'content_changed','Governed protocol/item content changed.');
            if ($changed) { $this->repository->updateProtocol((int)$next['id'],$changed); }
            foreach ($updates as $itemId=>$changes) { $this->repository->updateItem($itemId,$changes); }
            return true;
        });
    }

    public function reference(int $id,int $revision,string $state,int $sourceId,?int $itemId,string $scope,?string $locator=null,bool $remove=false,string $relationshipMode='upsert'): bool
    {
        $this->rules->value($relationshipMode,['upsert','attach','existing']);
        $this->rules->id($sourceId); if ($itemId!==null) { $this->rules->id($itemId); }
        $this->rules->text($scope,65535,!$remove); $this->rules->text($locator,2000); if ($locator!==null && mb_strlen($locator)>500) { throw new RuntimeException('Invalid treatment locator.'); }
        return $this->command($id,$revision,$state,'edit',function ($aggregate,$actor) use ($sourceId,$itemId,$scope,$locator,$remove,$relationshipMode) {
            $p=$aggregate['protocol'];
            if ($p['publication_status']==='published') { throw new RuntimeException('Withdraw published treatment content before editing.'); }
            if ($itemId!==null && !in_array($itemId,array_map('intval',array_column($aggregate['items'],'id')),true)) { throw new RuntimeException('Treatment item unavailable.'); }
            $refs=$itemId===null ? $aggregate['protocol_sources'] : $aggregate['item_sources']; $found=null;
            foreach ($refs as $ref) { if ((int)$ref['source_id']===$sourceId && ($itemId===null || (int)$ref['item_id']===$itemId)) { $found=$ref; break; } }
            if ($relationshipMode==='attach' && $found!==null) { throw new RuntimeException('This source is already attached. Edit its existing claim instead.'); }
            if ($relationshipMode==='existing' && $found===null) { throw new RuntimeException('Treatment source relationship unavailable.'); }
            $source=$aggregate['locked_sources'][$sourceId];
            $this->rules->value($source['status'],TreatmentGovernanceRules::SOURCE_STATES);
            if (!$remove && $source['status']==='withdrawn') { throw new RuntimeException('Withdrawn sources cannot be attached to prepared content.'); }
            if (($remove && $found===null) || (!$remove && $found && $found['claim_scope']===$scope && ($found['locator']??null)===$locator)) { return false; }
            $this->saveInvalidation($p,$actor,'content_changed','Treatment source relationship changed.');
            if ($remove) { $this->repository->unlink((int)$p['id'],$itemId,$sourceId); }
            else { $this->repository->link((int)$p['id'],$itemId,$sourceId,$scope,$locator); }
            return true;
        },[$sourceId]);
    }

    public function prepared(array $aggregate,array $locales): void
    {
        $locales=$this->rules->locales($locales,false); $p=$aggregate['protocol']; $this->rules->protocol($p);
        if (!$this->repository->diseaseExists((int)$p['disease_id']) || $p['content_purpose']===null || $p['evidence_classification']==='not_assessed') { throw new RuntimeException('Treatment content is not ready for review.'); }
        foreach ($locales as $locale) { foreach (['title','overview','applicability','limitations'] as $field) { $this->rules->text($p[$field.'_'.$locale]??null,65535,true); } }
        $sources=[]; foreach ($aggregate['sources'] as $source) { $sources[(int)$source['id']]=$source; }
        $valid=function ($refs) use ($sources) {
            if (!$refs) { throw new RuntimeException('Verified treatment references are required.'); }
            foreach ($refs as $ref) {
                $source=$sources[(int)$ref['source_id']]??null;
                if (!$source || $source['status']!=='verified' || !$source['verified_by'] || !$source['verified_at']) { throw new RuntimeException('Verified treatment references are required.'); }
                $this->rules->text($ref['claim_scope'],65535,true);
            }
        };
        $valid($aggregate['protocol_sources']); $included=0; $orders=[];
        foreach ($aggregate['items'] as $item) {
            if ($this->rules->flag($item['is_included'])!==1) { continue; } ++$included;
            $this->rules->value($item['item_type'],TreatmentGovernanceRules::ITEM_TYPES);
            $evidence=$this->rules->value($item['evidence_classification'],TreatmentGovernanceRules::EVIDENCE);
            if ($evidence==='not_assessed') { throw new RuntimeException('Included treatment items require assessment.'); }
            $order=$this->rules->id($item['sort_order']); if (isset($orders[$order])) { throw new RuntimeException('Included treatment ordering must be unique.'); } $orders[$order]=true;
            $this->rules->value($item['content_purpose']??null,TreatmentGovernanceRules::PURPOSE);
            foreach ($locales as $locale) { foreach (['title','instructions','limitations'] as $field) { $this->rules->text($item[$field.'_'.$locale]??null,65535,true); } }
            $valid(array_values(array_filter($aggregate['item_sources'],static fn($ref)=>(int)$ref['item_id']===(int)$item['id'])));
        }
        if (!$included) { throw new RuntimeException('At least one included treatment item is required.'); }
    }

    public function submit(int $id,int $revision,string $state,array $locales): int
    {
        $locales=$this->rules->locales($locales,false);
        return $this->command($id,$revision,$state,'submit',function ($aggregate,$actor,$system) use ($locales) {
            $p=$aggregate['protocol'];
            if (!in_array($p['review_state'],['draft','changes_required'],true) || $p['publication_status']!=='unpublished' || (int)$system['is_active']!==1) { throw new RuntimeException('Invalid treatment review transition.'); }
            if ($p['review_state']==='changes_required') {
                $decision=$this->repository->latestChangesRequired((int)$p['id']);
                if (!$decision || (int)$decision['content_revision']>=(int)$p['content_revision']) { throw new RuntimeException('Make the required governed-content corrections before submitting a fresh revision.'); }
            }
            $this->prepared($aggregate,$locales);
            $event=$this->event($p,'submitted',$actor,array_merge($this->snapshots->capture($aggregate,$locales),['locales'=>implode(',',$locales)]));
            $this->repository->updateProtocol((int)$p['id'],['review_state'=>'pending_review']); return $event;
        });
    }

    public function recordRiskEligibility(int $id,int $revision,string $state,string $decision,string $reason,bool $independent): bool
    {
        $decision=$this->rules->value($decision,TreatmentGovernanceRules::RISK_ELIGIBILITY);
        $this->rules->text($reason,65535,true);
        return $this->command($id,$revision,$state,'review',function ($a,$actor) use ($decision,$reason,$independent) {
            $p=$a['protocol'];
            if ($p['publication_status']!=='unpublished') { throw new RuntimeException('Edit withdrawn content before recording a fresh risk decision. Published content must first be withdrawn.'); }
            $this->rules->independence($actor,$this->repository->contributors((int)$p['id']),$independent);
            if ($p['risk_eligibility']===$decision) { return false; }
            $next=$this->rules->invalidate($p);
            $next['risk_eligibility']=$decision;
            $fields=array_intersect_key($next,array_flip(['content_revision','review_state','approved_review_id','publication_status','published_locales','risk_eligibility']));
            $this->repository->updateProtocol((int)$p['id'],$fields+['updated_by'=>$actor,'updated_at'=>$this->rules->now()]);
            $a['protocol']=$next;
            $this->event($next,'risk_assessed',$actor,$this->snapshots->capture($a,[])+['notes'=>$reason,'independence_confirmed'=>1]);
            // A reviewer decision is distinct from material authorship. The new
            // revision must be submitted before any approval can be recorded.
            return true;
        });
    }

    public function approve(int $id,int $revision,int $submissionId,array $locales,string $reason,string $due,bool $independent): int
    {
        $locales=$this->rules->locales($locales,false); $this->rules->text($reason,65535,true); $this->rules->timestamp($due);
        return $this->command($id,$revision,'pending_review','approve',function ($aggregate,$actor,$system) use ($submissionId,$locales,$reason,$due,$independent) {
            $p=$aggregate['protocol']; $submitted=$this->repository->latestSubmission((int)$p['id'],(int)$p['content_revision']);
            if (!$submitted || (int)$submitted['id']!==$submissionId || (int)$system['is_active']!==1 || array_diff($locales,$this->rules->storedLocales($submitted['locales']))) { throw new RuntimeException('Treatment review changed. Reload and retry.'); }
            $this->rules->independence($actor,$this->repository->contributors((int)$p['id']),$independent);
            $this->approvalReadiness($aggregate,$system,$locales);
            $current=$this->snapshots->capture($aggregate,$this->rules->storedLocales($submitted['locales']));
            $this->matchSubmission($submitted,$current,(int)$p['id'],(int)$p['content_revision']);
            $event=$this->event($p,'approved',$actor,array_merge($this->snapshots->capture($aggregate,$locales),['locales'=>implode(',',$locales),'notes'=>$reason,'review_due_at'=>$due,'independence_confirmed'=>1]));
            $this->repository->updateProtocol((int)$p['id'],['approved_review_id'=>$event,'review_state'=>'approved']); return $event;
        });
    }

    /** Shared read-only predicate; approval always repeats it under the command locks. */
    public function approvalReadiness(array $aggregate,array $system,array $locales): void
    {
        $p=$aggregate['protocol'];
        if ($p['review_state']!=='pending_review' || $p['publication_status']!=='unpublished' || (int)$system['id']!==(int)$p['treatment_system_id'] || (int)$system['is_active']!==1) { throw new RuntimeException('Treatment review is not ready for an approval decision.'); }
        $this->prepared($aggregate,$locales);
        if (!$this->policy->riskAllowed($p['risk_eligibility'] ?? null)) { throw new RuntimeException('Record an eligible risk decision and submit the fresh revision before approval.'); }
        if (!$this->policy->evidenceAllowed($p['evidence_classification'])) { throw new RuntimeException('Approval is blocked by unresolved evidence policy. Request changes; do not infer a supported classification.'); }
        foreach ($aggregate['items'] as $item) {
            if ((int)$item['is_included']===1 && !$this->policy->evidenceAllowed($item['evidence_classification'])) { throw new RuntimeException('Included item approval is blocked by unresolved evidence policy. Request changes.'); }
        }
    }

    public function matchSubmission(array $event,array $current,int $protocolId,int $revision): void
    {
        if (($event['event_type']??null)!=='submitted' || (int)($event['protocol_id']??0)!==$protocolId || (int)($event['content_revision']??0)!==$revision
            || !is_string($event['content_snapshot']??null) || !is_string($event['content_hash']??null)
            || !hash_equals(hash('sha256',$event['content_snapshot']),$event['content_hash'])
            || !hash_equals($event['content_hash'],$current['content_hash']) || $event['content_snapshot']!==$current['content_snapshot']) {
            throw new RuntimeException('Treatment submission no longer matches current content. Reload and retry after resubmission.');
        }
    }

    public function reviewAssessment(array $aggregate,array $system,?array $submission,int $actor): array
    {
        $issues=[]; $matches=false; $submittedContent=null; $locales=[];
        if ($submission) {
            try {
                $locales=$this->rules->locales($this->rules->storedLocales($submission['locales']),false);
                $current=$this->snapshots->capture($aggregate,$locales);
                $this->matchSubmission($submission,$current,(int)$aggregate['protocol']['id'],(int)$aggregate['protocol']['content_revision']);
                $matches=true;
            } catch (RuntimeException $error) { $issues[]=$error->getMessage(); }
            // Never render raw JSON. Views use only the explicit governed-field projection.
            if (is_string($submission['content_snapshot']??null) && is_string($submission['content_hash']??null) && hash_equals(hash('sha256',$submission['content_snapshot']),$submission['content_hash'])) {
                $decoded=json_decode($submission['content_snapshot'],true);
                if (is_array($decoded) && ($decoded['format_version']??null)===1 && is_array($decoded['protocol']??null) && (int)($decoded['protocol']['id']??0)===(int)$aggregate['protocol']['id'] && (int)($decoded['protocol']['content_revision']??0)===(int)$aggregate['protocol']['content_revision']) {
                    // Project only known scalar governed fields; malformed stored rows
                    // must not become executable markup or leak unrelated JSON keys.
                    $project=static function (array $row,array $fields): array {
                        $result=[]; foreach ($fields as $field) { $value=$row[$field]??null; $result[$field]=is_scalar($value) || $value===null ? $value : null; } return $result;
                    };
                    $savedRisk=$decoded['protocol']['risk_eligibility']??null;
                    if (!in_array($savedRisk,TreatmentGovernanceRules::RISK_ELIGIBILITY,true)) {
                        $issues[]='Submitted risk eligibility is unavailable or invalid. Resubmit a valid governed revision.';
                    } else {
                        $submittedContent=['protocol'=>$project($decoded['protocol'],array_merge(['id','content_revision','risk_eligibility'],TreatmentGovernanceRules::PROTOCOL_CONTENT))];
                        foreach (['items'=>array_merge(['id','protocol_id'],TreatmentGovernanceRules::ITEM_CONTENT),'sources'=>array_merge(['id'],TreatmentGovernanceRules::SOURCE_CONTENT),'protocol_sources'=>['id','protocol_id','source_id','claim_scope','locator'],'item_sources'=>['id','item_id','source_id','claim_scope','locator']] as $group=>$fields) {
                            $submittedContent[$group]=[];
                            foreach (is_array($decoded[$group]??null) ? $decoded[$group] : [] as $row) { if (is_array($row)) { $submittedContent[$group][]=$project($row,$fields); } }
                        }
                    }
                }
            }
        } else { $issues[]='No current submission exists. An editor must submit this revision before approval.'; }
        try { $this->approvalReadiness($aggregate,$system,$locales); }
        catch (RuntimeException $error) { if ($error instanceof \PDOException || $error->getPrevious()!==null) { throw $error; } $issues[]=$error->getMessage(); }
        $selfAuthored=in_array($actor,$this->repository->contributors((int)$aggregate['protocol']['id']),true);
        if ($selfAuthored) { $issues[]='You materially authored this protocol and cannot approve it. An independent qualified reviewer is required.'; }
        return ['matches'=>$matches,'issues'=>array_values(array_unique($issues)),'locales'=>$locales,'submittedContent'=>$submittedContent,'canApprove'=>$matches && !$issues,'selfAuthored'=>$selfAuthored];
    }

    public function requestChanges(int $id,int $revision,string $reason,?int $submissionId=null): void
    {
        $this->rules->text($reason,65535,true);
        $this->command($id,$revision,'pending_review','request_changes',function ($a,$actor) use ($reason,$submissionId) {
            $p=$a['protocol']; $submission=$this->repository->latestSubmission((int)$p['id'],(int)$p['content_revision']);
            if ($p['publication_status']==='published' || !$submission || ($submissionId!==null && (int)$submission['id']!==$submissionId)) { throw new RuntimeException('Treatment review changed. Reload and retry.'); }
            $this->repository->updateProtocol((int)$p['id'],['review_state'=>'changes_required','approved_review_id'=>null]);
            $this->event($p,'changes_required',$actor,['notes'=>$reason]);
        });
    }

    public function resetReview(int $id,int $revision,string $state,string $reason): void
    {
        $this->rules->text($reason,65535,true);
        $this->command($id,$revision,$state,'review',function ($a,$actor) use ($reason) {
            $p=$a['protocol']; if (!in_array($p['review_state'],['approved','pending_review'],true)) { throw new RuntimeException('Invalid treatment review transition.'); }
            if ($p['publication_status']==='published') { throw new RuntimeException('Withdraw published treatment content before review.'); }
            $target=$p['review_state']==='approved' ? 'pending_review' : 'draft';
            $this->repository->updateProtocol((int)$p['id'],['review_state'=>$target,'approved_review_id'=>null]);
            $this->event($p,'review_reset',$actor,['notes'=>$reason]);
            if ($target==='pending_review') {
                $old=$this->repository->event((int)$p['approved_review_id']);
                if (!$old) { throw new RuntimeException('Treatment approval unavailable.'); }
                $locales=$this->rules->storedLocales($old['locales']); $this->prepared($a,$locales);
                $this->event($p,'submitted',$actor,array_merge($this->snapshots->capture($a,$locales),['locales'=>implode(',',$locales)]));
            }
        });
    }

    public function publish(int $id,int $revision,array $locales): void
    {
        $locales=$this->rules->locales($locales,false);
        $this->command($id,$revision,'approved','publish',function ($a,$actor,$system) use ($locales) {
            $p=$a['protocol']; $review=$this->repository->event((int)$p['approved_review_id']);
            if ($p['publication_status']==='withdrawn') { throw new RuntimeException('Withdrawn content requires a substantive edit and fresh review.'); }
            if (!$review || $review['event_type']!=='approved' || (int)$review['protocol_id']!==(int)$p['id'] || (int)$review['content_revision']!==(int)$p['content_revision'] || $review['review_due_at']<=$this->rules->now() || (int)$system['is_active']!==1 || array_diff($locales,$this->rules->storedLocales($review['locales']))) { throw new RuntimeException('Treatment publication requirements are not satisfied.'); }
            $this->prepared($a,$locales);
            if (!$this->policy->riskAllowed($p['risk_eligibility'] ?? null)) { throw new RuntimeException('Treatment publication requirements are not satisfied.'); }
            $hash=$this->snapshots->capture($a,$this->rules->storedLocales($review['locales']));
            if (!hash_equals($review['content_hash'],$hash['content_hash']) || !$this->policy->evidenceAllowed($p['evidence_classification'])) { throw new RuntimeException('Treatment publication requirements are not satisfied.'); }
            foreach ($a['items'] as $item) { if ((int)$item['is_included']===1 && !$this->policy->evidenceAllowed($item['evidence_classification'])) { throw new RuntimeException('Treatment publication requirements are not satisfied.'); } }
            $set=implode(',',$locales); if ($p['publication_status']==='published' && $p['published_locales']===$set) { return; }
            $this->repository->updateProtocol((int)$p['id'],['publication_status'=>'published','published_locales'=>$set]);
            $this->event($p,$p['publication_status']==='published' ? 'publication_locales_changed' : 'published',$actor,['locales'=>$set]);
        });
    }

    public function withdraw(int $id,int $revision,string $state,string $reason): void
    {
        $this->rules->text($reason,65535,true);
        $this->command($id,$revision,$state,'withdraw',function ($a,$actor) use ($reason) {
            $p=$a['protocol']; if ($p['publication_status']!=='published') { throw new RuntimeException('Invalid treatment publication transition.'); }
            $this->repository->updateProtocol((int)$p['id'],['publication_status'=>'withdrawn','review_state'=>'draft','approved_review_id'=>null,'published_locales'=>'']);
            $this->event($p,'withdrawn',$actor,['notes'=>$reason]);
        });
    }

    public function initializeLegacy(int $id): bool
    {
        return $this->repository->transaction(function () use ($id) {
            $this->authorization->administrator(true);
            $p=$this->repository->find($this->rules->id($id),true); if (!$p) { throw new RuntimeException('Treatment protocol unavailable.'); }
            if ($this->repository->hasEvents($id)) { return false; }
            $expected=$this->rules->legacyProtocol($p);
            foreach (['review_state','publication_status','content_revision','approved_review_id','published_locales','risk_eligibility','evidence_classification','content_purpose','evidence_summary_en','evidence_summary_hi','applicability_en','applicability_hi','limitations_en','limitations_hi','updated_at','updated_by'] as $key) {
                if (in_array($key,['content_revision'],true)) { if ((int)$p[$key]!==1) { throw new RuntimeException('Legacy initialization requires untouched governance defaults.'); } }
                elseif (array_key_exists($key,$p) && $p[$key]!==$expected[$key]) { throw new RuntimeException('Legacy initialization requires untouched governance defaults.'); }
            }
            $aggregate=$this->repository->aggregate($id,true);
            if ($aggregate['sources'] || $aggregate['protocol_sources'] || $aggregate['item_sources']) { throw new RuntimeException('Legacy initialization requires no fabricated references.'); }
            foreach ($aggregate['items'] as $item) { if ((int)$item['is_included']!==0 || $item['evidence_classification']!=='not_assessed' || ($item['content_purpose']??null)!==null || ($item['limitations_en']??null)!==null || ($item['limitations_hi']??null)!==null) { throw new RuntimeException('Legacy items must remain excluded and unassessed.'); } }
            $this->event($p,'legacy_initialized',null,$this->snapshots->capture($aggregate,[])); return true;
        });
    }

    public function createSource(int $systemId, array $content): int
    {
        $this->rules->id($systemId);
        if (array_diff(array_keys($content),TreatmentGovernanceRules::SOURCE_CONTENT)) { throw new RuntimeException('Invalid source field.'); }
        $row=[];
        foreach (TreatmentGovernanceRules::SOURCE_CONTENT as $field) {
            $value=$content[$field]??null;
            if ($field==='source_type') { $value=$this->rules->value($value,TreatmentGovernanceRules::SOURCE_TYPES); }
            elseif ($field==='replaces_source_id') { if ($value!==null) { $value=$this->rules->id($value); } }
            elseif (in_array($field,['publication_date','accessed_on'],true)) {
                if ($value!==null) {
                    $value=$this->rules->text($value,10,true);
                    $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);
                    if (!$date || $date->format('Y-m-d')!==$value || $value<'1000-01-01') { throw new RuntimeException('Invalid source date.'); }
                }
            } else {
                $limits=['title'=>500,'author_or_organization'=>255,'publication'=>255,'url'=>2048,'identifier'=>255,'edition_or_version'=>100];
                $value=$this->rules->text($value,isset($limits[$field]) ? $limits[$field]*4 : 65535,in_array($field,['title','citation_text'],true));
                if ($value!==null && isset($limits[$field]) && mb_strlen($value)>$limits[$field]) { throw new RuntimeException('Invalid source content.'); }
            }
            if ($field==='url' && $value!==null && (!filter_var($value,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($value,PHP_URL_SCHEME)),['http','https'],true))) { throw new RuntimeException('Source URLs must use http or https.'); }
            $row[$field]=$value;
        }
        return $this->repository->transaction(function () use ($systemId,$row) {
            $actor=$this->authorization->require('create_source',$systemId,true);
            if (!$this->repository->system($systemId,true)) { throw new RuntimeException('Treatment system unavailable.'); }
            // Bibliographic predecessor edges are immutable. Validate the whole stored chain.
            $resolve=fn(int $id)=>$this->sources->find($id);
            $this->rules->replacement(null,$row['replaces_source_id'],$resolve);
            if ($row['replaces_source_id']!==null && !$this->sources->find($row['replaces_source_id'],true)) { throw new RuntimeException('Treatment source unavailable.'); }
            $id=$this->sources->insert(array_merge($row,['created_by'=>$actor,'created_at'=>$this->rules->now()]));
            $this->rules->replacement($id,$row['replaces_source_id'],$resolve);
            return $id;
        });
    }

    public function verifySource(int $systemId,int $sourceId): void
    {
        $this->rules->id($systemId); $this->rules->id($sourceId);
        $this->repository->transaction(function () use ($systemId,$sourceId) {
            $actor=$this->authorization->require('verify_source',$systemId,true);
            if (!$this->repository->system($systemId,true)) { throw new RuntimeException('Treatment system unavailable.'); }
            $source=$this->sources->find($sourceId,true);
            if (!$source) { throw new RuntimeException('Treatment source unavailable.'); }
            $this->rules->sourceTransition($source['status'],'verified');
            $this->rules->replacement($sourceId,$source['replaces_source_id']===null ? null : (int)$source['replaces_source_id'],fn($id)=>$this->sources->find($id));
            $this->sources->verify($sourceId,$actor,$this->rules->now());
        });
    }

    public function withdrawSource(int $systemId,int $sourceId,string $reason): void
    {
        $this->rules->id($systemId); $this->rules->id($sourceId); $this->rules->text($reason,65535,true);
        $this->repository->transaction(function () use ($systemId,$sourceId,$reason) {
            $actor=$this->authorization->require('manage_sources',$systemId,true);
            $before=$this->sources->affectedProtocols($sourceId);
            $systems=array_values(array_unique(array_merge([$systemId],array_map('intval',array_column($before,'treatment_system_id'))))); sort($systems,SORT_NUMERIC);
            // Publication withdrawal authority is required for every affected system.
            foreach ($systems as $id) { $this->authorization->require('withdraw',$id,true); }
            foreach ($systems as $id) { if (!$this->repository->system($id,true)) { throw new RuntimeException('Treatment system unavailable.'); } }
            $source=$this->sources->find($sourceId,true);
            if (!$source) { throw new RuntimeException('Treatment source unavailable.'); }
            $this->rules->sourceTransition($source['status'],'withdrawn');
            // Current locking read after the source lock: concurrent reference writers
            // also lock the source first. Refuse a newly discovered system and retry.
            $affected=$this->sources->affectedProtocols($sourceId,true);
            if (array_diff(array_map('intval',array_column($affected,'treatment_system_id')),$systems)) { throw new RuntimeException('Treatment references changed. Reload and retry.'); }
            $protocols=[];
            foreach ($affected as $row) { $p=$this->repository->find((int)$row['id'],true); if (!$p) { throw new RuntimeException('Treatment protocol unavailable.'); } $protocols[]=$p; }
            $this->sources->withdraw($sourceId,$actor,$this->rules->now(),$reason);
            foreach ($protocols as $p) { $this->saveInvalidation($p,$actor,'source_invalidated',$reason,true); }
        });
    }

    /** Deployment administrators change grants; revoked rows remain immutable history. */
    public function grantPermission(int $userId,int $systemId,array $capabilities,?string $qualification=null,?string $replacementReason=null): int
    {
        $this->rules->id($userId); $this->rules->id($systemId);
        if (array_diff(array_keys($capabilities),['can_edit','can_review','can_publish'])) { throw new RuntimeException('Invalid editorial capability.'); }
        $row=['user_id'=>$userId,'treatment_system_id'=>$systemId,'qualification_scope'=>$qualification];
        foreach (['can_edit','can_review','can_publish'] as $key) { $row[$key]=$this->rules->flag($capabilities[$key]??0); }
        $this->rules->grant($row);
        return $this->repository->transaction(function () use ($row,$replacementReason) {
            $actor=$this->authorization->administrator();
            $users=$this->permissions->lockUsers([$actor,$row['user_id']]);
            $this->authorization->administrator(true);
            if (!$users[$row['user_id']] || $users[$row['user_id']]['status']!=='active') { throw new RuntimeException('Editorial user unavailable.'); }
            $old=$this->permissions->active($row['user_id'],$row['treatment_system_id'],true);
            if (!$this->repository->system($row['treatment_system_id'],true)) { throw new RuntimeException('Treatment system unavailable.'); }
            $now=$this->rules->now();
            if ($old) { $this->rules->text($replacementReason,65535,true); $this->permissions->revoke((int)$old['id'],$actor,$now,$replacementReason); }
            return $this->permissions->insert(array_merge($row,['authorized_by'=>$actor,'granted_at'=>$now]));
        });
    }

    public function revokePermission(int $userId,int $systemId,string $reason): void
    {
        $this->rules->id($userId); $this->rules->id($systemId); $this->rules->text($reason,65535,true);
        $this->repository->transaction(function () use ($userId,$systemId,$reason) {
            $actor=$this->authorization->administrator();
            $this->permissions->lockUsers([$actor,$userId]); $this->authorization->administrator(true);
            $old=$this->permissions->active($userId,$systemId,true);
            if (!$this->repository->system($systemId,true) || !$old) { throw new RuntimeException('Active editorial permission unavailable.'); }
            $this->permissions->revoke((int)$old['id'],$actor,$this->rules->now(),$reason);
        });
    }
}

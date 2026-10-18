<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TreatmentGovernanceRepository;
use App\Repositories\TreatmentSourceRepository;
use App\Repositories\TreatmentEditorialPermissionRepository;
use RuntimeException;

/** Internal presentation/input boundary. All writes use the governance commands. */
class TreatmentEditorialService
{
    public function __construct(
        private TreatmentGovernanceRepository $protocols,
        private TreatmentSourceRepository $sources,
        private TreatmentEditorialPermissionRepository $permissions,
        private TreatmentEditorialAuthorizationService $authorization,
        private TreatmentGovernanceService $governance,
        private TreatmentGovernanceRules $rules
    ) {}

    private function keys(array $input, array $allowed): void
    {
        if (array_diff(array_keys($input),$allowed)) { throw new RuntimeException('Unsupported editorial field.'); }
    }

    public function systems(string $action='edit'): array
    {
        $actor=$this->authorization->actor(); $result=[];
        try { $rows=$this->permissions->systems($actor); }
        catch (RuntimeException $error) { throw new RuntimeException('Treatment authorization data is unavailable.',0,$error); }
        foreach ($rows as $row) {
            $id=$this->rules->id($row['treatment_system_id']);
            if ($this->authorization->can($action,$id)) { $result[]=['id'=>$id,'name_en'=>$row['name_en'],'is_active'=>$row['is_active']]; }
        }
        if (!$result) { throw new RuntimeException('Treatment editorial access denied.'); }
        return $result;
    }

    private function page(mixed $page): int
    {
        $page=$this->rules->id($page??1);
        if ($page>100000) { throw new RuntimeException('Invalid editorial page.'); }
        return $page;
    }

    public function draftForm(): array
    {
        $systems=array_values(array_filter($this->systems(),fn($s)=>(int)$s['is_active']===1));
        if (!$systems) { throw new RuntimeException('An active treatment system is required.'); }
        return ['systems'=>$systems];
    }

    private function creationContent(mixed $input,bool $item): array
    {
        if (!is_array($input)) { throw new RuntimeException('Invalid treatment content.'); }
        $allowed=$item ? ['item_type','title_en','title_hi','instructions_en','instructions_hi','sort_order'] : ['title_en','title_hi','overview_en','overview_hi'];
        $this->keys($input,$allowed);
        foreach ($input as $key=>&$value) {
            if (!is_string($value) && !is_int($value)) { throw new RuntimeException('Invalid treatment content.'); }
            if ($value==='') { $value=$key==='sort_order' ? 0 : null; }
        }
        unset($value); return $input;
    }

    public function createDraft(array $input): int
    {
        $this->keys($input,['_csrf_token','disease_id','treatment_system_id','protocol','include_first_item','first_item']);
        $include=$this->rules->flag($input['include_first_item']??0);
        $first=$this->creationContent($input['first_item']??[],true);
        if (!$include && array_filter($first,fn($v)=>$v!==null && $v!==0)) { throw new RuntimeException('Select the optional first item before entering its content.'); }
        return $this->governance->createDraft($this->rules->id($input['disease_id']??null),$this->rules->id($input['treatment_system_id']??null),$this->creationContent($input['protocol']??null,false),$include ? $first : null);
    }

    public function itemForm(int $id): array
    {
        $data=$this->detail($id); $p=$data['protocol'];
        $system=$this->protocols->system((int)$p['treatment_system_id']);
        if ($p['publication_status']==='published') { throw new RuntimeException('Withdraw published treatment content before editing.'); }
        if (!$system || (int)$system['is_active']!==1) { throw new RuntimeException('An active treatment system is required.'); }
        return $data;
    }

    public function addItem(int $id,array $input): int
    {
        $this->keys($input,['_csrf_token','expected_revision','expected_state','item']);
        [$revision,$state]=$this->expected($input);
        return $this->governance->addItem($id,$revision,$state,$this->creationContent($input['item']??null,true));
    }

    public function listing(array $input): array
    {
        $systems=$this->systems();
        $this->keys($input,['treatment_system_id','disease_id','review_state','publication_status','evidence_classification','completeness','page']);
        $filters=[];
        foreach ($input as $key=>$value) {
            if ($key==='page' || $value==='') { continue; }
            if (in_array($key,['treatment_system_id','disease_id'],true)) { $filters[$key]=$this->rules->id($value); }
            else { $filters[$key]=$this->rules->value($value,match($key) {
                'review_state'=>TreatmentGovernanceRules::REVIEW,
                'publication_status'=>TreatmentGovernanceRules::PUBLICATION,
                'evidence_classification'=>TreatmentGovernanceRules::EVIDENCE,
                'completeness'=>['unassessed','missing_sources'],
            }); }
        }
        if (isset($filters['treatment_system_id'])) { $this->authorization->require('edit',$filters['treatment_system_id']); }
        $page=$this->page($input['page']??1);
        $rows=$this->protocols->editorialList($this->authorization->actor(),$filters,($page-1)*50);
        foreach ($rows as $row) { $this->authorization->require('edit',(int)$row['treatment_system_id']); }
        $more=count($rows)>50;
        return ['systems'=>$systems,'protocols'=>array_slice($rows,0,50),'filters'=>$filters,'page'=>$page,'more'=>$more];
    }

    public function operationsListing(array $input): array
    {
        $systems=$this->systems('view');
        $this->keys($input,['treatment_system_id','disease_id','review_state','publication_status','page']);
        $filters=[];
        foreach ($input as $key=>$value) {
            if ($key==='page' || $value==='') { continue; }
            $filters[$key]=in_array($key,['treatment_system_id','disease_id'],true) ? $this->rules->id($value) : $this->rules->value($value,$key==='review_state' ? TreatmentGovernanceRules::REVIEW : TreatmentGovernanceRules::PUBLICATION);
        }
        if (isset($filters['treatment_system_id'])) { $this->authorization->require('view',$filters['treatment_system_id']); }
        $page=$this->page($input['page']??1);
        $rows=$this->protocols->operationsList($this->authorization->actor(),$filters,($page-1)*50);
        foreach ($rows as $row) { $this->authorization->require('view',(int)$row['treatment_system_id']); }
        return ['systems'=>$systems,'protocols'=>array_slice($rows,0,50),'filters'=>$filters,'page'=>$page,'more'=>count($rows)>50];
    }

    private function operationAccess(int $id,string $action='view'): array
    {
        $p=$this->protocols->find($this->rules->id($id));
        if (!$p) { throw new RuntimeException('Treatment editorial access denied.'); }
        $this->authorization->require($action,(int)$p['treatment_system_id']);
        return $p;
    }

    public function operationsDetail(int $id,array $input=[]): array
    {
        $p=$this->operationAccess($id); $this->keys($input,['before_event','withdrawn','reset']);
        $this->rules->protocol($p);
        $before=isset($input['before_event']) ? $this->rules->id($input['before_event']) : 0;
        $history=$this->protocols->history($id,$before); $more=count($history)>50;
        // Explicit metadata projection, even when a repository adapter returns extra fields.
        $fields=array_flip(['id','content_revision','event_type','actor_id','occurred_at','locales','notes','review_due_at','independence_confirmed']);
        $history=array_map(fn($event)=>array_intersect_key($event,$fields),array_slice($history,0,50));
        $current=$this->protocols->find($id);
        if (!$current || (int)$current['treatment_system_id']!==(int)$p['treatment_system_id']) { throw new RuntimeException('Treatment content changed. Reload and retry.'); }
        $system=(int)$p['treatment_system_id'];
        return ['protocol'=>$p,'identity'=>$this->protocols->editorialIdentity($id),'history'=>$history,'moreHistory'=>$more,
            'canWithdraw'=>$this->authorization->can('withdraw',$system) && $p['publication_status']==='published',
            'canReset'=>$this->authorization->can('review',$system) && $p['publication_status']==='unpublished' && in_array($p['review_state'],['approved','pending_review'],true),
            'canEdit'=>$this->authorization->can('edit',$system),'canReview'=>$this->authorization->can('review',$system)];
    }

    public function withdrawProtocol(int $id,array $input): void
    {
        $this->operationAccess($id,'withdraw');
        $this->keys($input,['_csrf_token','expected_revision','expected_state','reason']);
        [$revision,$state]=$this->expected($input);
        $this->governance->withdraw($id,$revision,$state,$this->rules->text($input['reason']??null,65535,true));
    }

    public function resetProtocolReview(int $id,array $input): void
    {
        $p=$this->operationAccess($id,'review');
        $this->keys($input,['_csrf_token','expected_revision','expected_state','reason']);
        [$revision,$state]=$this->expected($input);
        if ((int)$p['content_revision']!==$revision || $p['review_state']!==$state) { throw new RuntimeException('Treatment content changed. Reload and retry.'); }
        if ($p['publication_status']!=='unpublished') { throw new RuntimeException('Withdrawn content requires a governed edit and fresh review. Withdraw published treatment content before review.'); }
        $this->governance->resetReview($id,$revision,$state,$this->rules->text($input['reason']??null,65535,true));
    }

    public function reviewListing(array $input): array
    {
        $systems=$this->systems('review');
        $this->keys($input,['treatment_system_id','disease_id','review_state','evidence_classification','content_purpose','content_revision','due_status','page']);
        $filters=['review_state'=>'pending_review'];
        foreach ($input as $key=>$value) {
            if ($key==='page' || $value==='') { continue; }
            if (in_array($key,['treatment_system_id','disease_id','content_revision'],true)) { $filters[$key]=$this->rules->id($value); }
            else { $filters[$key]=$this->rules->value($value,match($key) {
                'review_state'=>['pending_review','changes_required','approved'],
                'evidence_classification'=>TreatmentGovernanceRules::EVIDENCE,
                'content_purpose'=>TreatmentGovernanceRules::PURPOSE,
                'due_status'=>['expired','current'],
            }); }
        }
        if (isset($filters['treatment_system_id'])) { $this->authorization->require('review',$filters['treatment_system_id']); }
        $page=$this->page($input['page']??1);
        $rows=$this->protocols->reviewList($this->authorization->actor(),$filters,($page-1)*50,$this->rules->now());
        foreach ($rows as $row) { $this->authorization->require('review',(int)$row['treatment_system_id']); }
        return ['systems'=>$systems,'protocols'=>array_slice($rows,0,50),'filters'=>$filters,'page'=>$page,'more'=>count($rows)>50];
    }

    private function reviewAccess(int $id): array
    {
        $p=$this->protocols->find($this->rules->id($id));
        if (!$p) { throw new RuntimeException('Treatment editorial access denied.'); }
        $this->authorization->require('review',(int)$p['treatment_system_id']);
        return $p;
    }

    public function reviewDetail(int $id,array $input=[]): array
    {
        $p=$this->reviewAccess($id); $this->keys($input,['before_event']);
        $before=isset($input['before_event']) ? $this->rules->id($input['before_event']) : 0;
        $a=$this->protocols->aggregate($id);
        if (!$a['protocol'] || (int)$a['protocol']['treatment_system_id']!==(int)$p['treatment_system_id']) { throw new RuntimeException('Treatment content changed. Reload and retry.'); }
        $actor=$this->authorization->actor(); $system=$this->protocols->system((int)$p['treatment_system_id']);
        if (!$system) { throw new RuntimeException('Treatment editorial access denied.'); }
        $submission=$this->protocols->latestSubmission($id,(int)$a['protocol']['content_revision']);
        $history=$this->protocols->history($id,$before); $more=count($history)>50; $history=array_slice($history,0,50);
        $grant=$this->permissions->active($actor,(int)$p['treatment_system_id']);
        return $a+['identity'=>$this->protocols->editorialIdentity($id),'submission'=>$submission,'assessment'=>$this->governance->reviewAssessment($a,$system,$submission,$actor),'history'=>$history,'moreHistory'=>$more,'qualification'=>$grant['qualification_scope'],'actor'=>$actor];
    }

    public function approveReview(int $id,array $input): void
    {
        $p=$this->reviewAccess($id);
        if ($p['publication_status']!=='unpublished') { throw new RuntimeException('This review stage handles unpublished protocols only.'); }
        $this->keys($input,['_csrf_token','expected_revision','expected_state','submission_id','locales','rationale','review_due_at','independence_confirmed','risk_eligibility']);
        $decision=$this->rules->value($input['risk_eligibility']??null,TreatmentGovernanceRules::RISK_ELIGIBILITY);
        if ($decision!==$p['risk_eligibility'] || $decision!=='eligible') { throw new RuntimeException('Confirm the saved eligible risk decision. Changes require a fresh revision and submission.'); }
        [$revision,$state]=$this->expected($input);
        if ($state!=='pending_review') { throw new RuntimeException('Treatment review changed. Reload and retry.'); }
        $reason=$this->rules->text($input['rationale']??null,65535,true);
        $due=$this->rules->text($input['review_due_at']??null,26,true);
        // HTML datetime-local is explicitly interpreted as UTC, never browser/server local time.
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D',$due)) { $due=str_replace('T',' ',$due).':00.000000'; }
        $this->rules->timestamp($due);
        $attested=($input['independence_confirmed']??null)==='1';
        if (!$attested) { throw new RuntimeException('Explicit reviewer independence confirmation is required.'); }
        $this->governance->approve($id,$revision,$this->rules->id($input['submission_id']??null),$this->rules->locales($input['locales']??[],false),$reason,$due,$attested);
    }

    public function requestReviewChanges(int $id,array $input): void
    {
        $p=$this->reviewAccess($id);
        if ($p['publication_status']!=='unpublished') { throw new RuntimeException('This review stage handles unpublished protocols only.'); }
        $this->keys($input,['_csrf_token','expected_revision','expected_state','submission_id','rationale']);
        [$revision,$state]=$this->expected($input);
        if ($state!=='pending_review') { throw new RuntimeException('Treatment review changed. Reload and retry.'); }
        $this->governance->requestChanges($id,$revision,$this->rules->text($input['rationale']??null,65535,true),$this->rules->id($input['submission_id']??null));
    }

    public function recordRiskEligibility(int $id,array $input): void
    {
        $this->reviewAccess($id);
        $this->keys($input,['_csrf_token','expected_revision','expected_state','risk_eligibility','rationale','independence_confirmed']);
        [$revision,$state]=$this->expected($input);
        $this->governance->recordRiskEligibility($id,$revision,$state,
            $this->rules->value($input['risk_eligibility']??null,TreatmentGovernanceRules::RISK_ELIGIBILITY),
            $this->rules->text($input['rationale']??null,65535,true),($input['independence_confirmed']??null)==='1');
    }

    public function detail(int $id): array
    {
        $p=$this->protocols->find($this->rules->id($id));
        if (!$p) { throw new RuntimeException('Treatment editorial access denied.'); }
        $this->authorization->require('edit',(int)$p['treatment_system_id']);
        $aggregate=$this->protocols->aggregate($id);
        if (!$aggregate['protocol'] || (int)$aggregate['protocol']['treatment_system_id']!==(int)$p['treatment_system_id']) { throw new RuntimeException('Treatment content changed. Reload and retry.'); }
        $aggregate['identity']=$this->protocols->editorialIdentity($id);
        return $aggregate;
    }

    private function expected(array $input): array
    {
        return [$this->rules->id($input['expected_revision']??null),$this->rules->value($input['expected_state']??null,TreatmentGovernanceRules::REVIEW)];
    }

    private function content(array $input,array $old,bool $item): array
    {
        $this->keys($input,$item ? TreatmentGovernanceRules::ITEM_CONTENT : array_diff(TreatmentGovernanceRules::PROTOCOL_CONTENT,['disease_id','treatment_system_id']));
        foreach ($input as $key=>&$value) {
            if (!is_string($value) && !is_int($value)) { throw new RuntimeException('Invalid treatment content.'); }
            if ($value==='' && (!isset($old[$key]) || in_array($key,['content_purpose','item_type'],true))) { $value=null; }
        }
        unset($value); return $input;
    }

    public function save(int $id,array $input): bool
    {
        $a=$this->detail($id);
        $this->keys($input,['_csrf_token','expected_revision','expected_state','protocol','items','form_complete']);
        if (($input['form_complete']??null)!=='1') { throw new RuntimeException('The form was incomplete. Reload it and check the server form-size settings before saving.'); }
        [$revision,$state]=$this->expected($input);
        if (!is_array($input['protocol']??null) || !is_array($input['items']??[])) { throw new RuntimeException('Invalid treatment content.'); }
        $items=array_column($a['items'],null,'id'); $changes=[];
        foreach ($input['items']??[] as $itemId=>$fields) {
            $itemId=$this->rules->id($itemId);
            if (!isset($items[$itemId]) || !is_array($fields)) { throw new RuntimeException('Treatment item unavailable.'); }
            $changes[$itemId]=$this->content($fields,$items[$itemId],true);
        }
        return $this->governance->edit($id,$revision,$state,$this->content($input['protocol'],$a['protocol'],false),$changes);
    }

    public function submit(int $id,array $input): void
    {
        $this->detail($id);
        $this->keys($input,['_csrf_token','expected_revision','expected_state','locales']);
        [$revision,$state]=$this->expected($input);
        $this->governance->submit($id,$revision,$state,$this->rules->locales($input['locales']??[],false));
    }

    public function reference(int $id,array $input): bool
    {
        $this->detail($id);
        $this->keys($input,['_csrf_token','expected_revision','expected_state','source_id','item_id','claim_scope','locator','operation']);
        [$revision,$state]=$this->expected($input);
        $operation=$this->rules->value($input['operation']??null,['attach','update','remove']);
        $source=$this->rules->id($input['source_id']??null);
        $item=($input['item_id']??'')==='' ? null : $this->rules->id($input['item_id']);
        $scope=$this->rules->text($input['claim_scope']??'',65535,$operation!=='remove');
        $locator=$this->rules->text($input['locator']??null,2000);
        return $this->governance->reference($id,$revision,$state,$source,$item,$scope,$locator,$operation==='remove',$operation==='attach' ? 'attach' : 'existing');
    }

    private function sourceScope(int $systemId,string $action='view'): void
    {
        $this->authorization->require($action,$this->rules->id($systemId));
        if (!$this->protocols->system($systemId)) { throw new RuntimeException('Treatment editorial access denied.'); }
    }

    public function sourceListing(int $systemId,array $input): array
    {
        $this->sourceScope($systemId);
        $this->keys($input,['status','page']);
        $status=($input['status']??'')==='' ? null : $this->rules->value($input['status'],TreatmentGovernanceRules::SOURCE_STATES);
        $page=$this->page($input['page']??1); $rows=$this->sources->catalog($status,($page-1)*50);
        return ['systemId'=>$systemId,'sources'=>array_slice($rows,0,50),'status'=>$status,'page'=>$page,'more'=>count($rows)>50,'canCreate'=>$this->authorization->can('create_source',$systemId)];
    }

    public function sourceDetail(int $systemId,int $sourceId): array
    {
        $this->sourceScope($systemId); $source=$this->sources->find($this->rules->id($sourceId));
        if (!$source) { throw new RuntimeException('Treatment source unavailable.'); }
        return ['systemId'=>$systemId,'source'=>$source,'canCreate'=>$this->authorization->can('create_source',$systemId),'canVerify'=>$this->authorization->can('verify_source',$systemId),'canWithdraw'=>$this->authorization->can('manage_sources',$systemId) && $this->authorization->can('withdraw',$systemId)];
    }

    public function sourceForm(int $systemId,?int $predecessor=null): array
    {
        $this->sourceScope($systemId,'create_source');
        $content=[];
        if ($predecessor!==null) {
            $source=$this->sourceDetail($systemId,$predecessor)['source'];
            $content=array_intersect_key($source,array_flip(TreatmentGovernanceRules::SOURCE_CONTENT));
            $content['replaces_source_id']=$predecessor;
        }
        return ['systemId'=>$systemId,'source'=>$content];
    }

    public function createSource(int $systemId,array $input): int
    {
        $this->sourceScope($systemId,'create_source');
        $this->keys($input,array_merge(['_csrf_token'],TreatmentGovernanceRules::SOURCE_CONTENT));
        unset($input['_csrf_token']);
        foreach ($input as $key=>&$value) {
            if (!is_string($value) && !is_int($value)) { throw new RuntimeException('Invalid source content.'); }
            if ($value==='' && !in_array($key,['source_type','title','citation_text'],true)) { $value=null; }
        }
        unset($value);
        return $this->governance->createSource($systemId,$input);
    }

    public function verifySource(int $systemId,int $sourceId,array $input): void
    {
        $this->sourceScope($systemId,'verify_source'); $this->keys($input,['_csrf_token']);
        $this->governance->verifySource($systemId,$sourceId);
    }

    public function withdrawSource(int $systemId,int $sourceId,array $input): void
    {
        $this->sourceScope($systemId,'manage_sources'); $this->keys($input,['_csrf_token','reason']);
        $reason=$this->rules->text($input['reason']??null,65535,true);
        $this->governance->withdrawSource($systemId,$sourceId,$reason);
    }
}

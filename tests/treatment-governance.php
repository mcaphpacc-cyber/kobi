<?php

declare(strict_types=1);

// Database-free foundation tests: php tests/treatment-governance.php
// No bootstrap, session, credentials, database connection, or application logs.
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file=dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php';
        if (is_file($file)) { require_once $file; }
    }
});
$testConfiguration=[];
function config(?string $key=null): mixed { global $testConfiguration; return $key===null ? $testConfiguration : ($testConfiguration[$key]??null); }

use App\Repositories\TreatmentGovernanceRepository;
use App\Repositories\TreatmentSourceRepository;
use App\Repositories\TreatmentEditorialPermissionRepository;
use App\Services\AuthService;
use App\Services\TreatmentGovernanceRules;
use App\Services\TreatmentGovernancePolicy;
use App\Services\TreatmentGovernanceSnapshot;
use App\Services\TreatmentEditorialAuthorizationService;
use App\Services\TreatmentGovernanceService;

final class GovernanceMemory
{
    public array $protocols=[], $items=[], $sources=[], $protocolRefs=[], $itemRefs=[], $events=[], $grants=[];
    public array $users=[1=>['id'=>1,'status'=>'active'],2=>['id'=>2,'status'=>'active'],3=>['id'=>3,'status'=>'active'],4=>['id'=>4,'status'=>'active']];
    public array $systems=[1=>['id'=>1,'is_active'=>1],2=>['id'=>2,'is_active'=>1]];
    public int $actor=1;
    public array $locks=[];
}
final class GovernanceMemoryAuth extends AuthService
{
    public function __construct(private GovernanceMemory $memory) {}
    public function id(): ?int { return $this->memory->actor ?: null; }
}
final class GovernanceMemoryPermissions extends TreatmentEditorialPermissionRepository
{
    public function __construct(private GovernanceMemory $memory) {}
    public function systems(int $userId): array { $rows=[]; foreach ($this->memory->systems as $id=>$system) { $grant=$this->active($userId,$id); if ($grant) { $rows[]=array_merge($grant,['name_en'=>'Fixture system '.$id,'is_active'=>$system['is_active']]); } } return $rows; }
    public function lockUsers(array $ids): array { sort($ids,SORT_NUMERIC); $rows=[]; foreach (array_unique($ids) as $id) { $this->memory->locks[]='user:'.$id; $rows[$id]=$this->user($id); } return $rows; }
    public function user(int $id): ?array { return $this->memory->users[$id]??null; }
    public function active(int $userId,int $systemId,bool $lock=false): ?array {
        if ($lock) { $this->memory->locks[]='permission:'.$systemId; }
        foreach (array_reverse($this->memory->grants,true) as $row) { if ($row['user_id']===$userId && $row['treatment_system_id']===$systemId && ($row['revoked_at']??null)===null) { return $row; } } return null;
    }
    public function insert(array $row): int { $id=count($this->memory->grants)+1; $this->memory->grants[$id]=$row+['id'=>$id,'revoked_at'=>null]; return $id; }
    public function revoke(int $id,int $actor,string $now,string $reason): void { $this->memory->grants[$id]=array_replace($this->memory->grants[$id],['revoked_by'=>$actor,'revoked_at'=>$now,'revocation_reason'=>$reason]); }
}
final class GovernanceMemorySources extends TreatmentSourceRepository
{
    public function __construct(private GovernanceMemory $memory) {}
    public function catalog(?string $status,int $offset=0): array { return array_slice(array_values(array_reverse(array_filter($this->memory->sources,fn($s)=>$status===null || $s['status']===$status),true)),$offset,51); }
    public function find(int $id,bool $lock=false): ?array { if ($lock) { $this->memory->locks[]='source:'.$id; } return $this->memory->sources[$id]??null; }
    public function insert(array $row): int { $id=$this->memory->sources ? max(array_keys($this->memory->sources))+1 : 1; $this->memory->sources[$id]=$row+['id'=>$id,'status'=>'unverified','verified_by'=>null,'verified_at'=>null]; return $id; }
    public function verify(int $id,int $actor,string $now): void { $this->memory->sources[$id]=array_replace($this->memory->sources[$id],['status'=>'verified','verified_by'=>$actor,'verified_at'=>$now]); }
    public function withdraw(int $id,int $actor,string $now,string $reason): void { $this->memory->sources[$id]=array_replace($this->memory->sources[$id],['status'=>'withdrawn','withdrawn_by'=>$actor,'withdrawn_at'=>$now,'withdrawal_reason'=>$reason]); }
    public function affectedProtocols(int $sourceId,bool $lock=false): array {
        $ids=[]; foreach ($this->memory->protocolRefs as $ref) { if ($ref['source_id']===$sourceId) { $ids[$ref['protocol_id']]=true; } }
        foreach ($this->memory->itemRefs as $ref) { if ($ref['source_id']===$sourceId) { $ids[$this->memory->items[$ref['item_id']]['protocol_id']]=true; } }
        ksort($ids,SORT_NUMERIC); $rows=[]; foreach ($ids as $id=>$_) { $rows[]=array_intersect_key($this->memory->protocols[$id],array_flip(['id','treatment_system_id'])); if ($lock) { $this->memory->locks[]='protocol:'.$id; } } return $rows;
    }
}
final class GovernanceMemoryRepository extends TreatmentGovernanceRepository
{
    public function __construct(private GovernanceMemory $memory) {}
    public function reviewList(int $actor,array $filters,int $offset,string $now): array {
        $rows=[];
        foreach ($this->memory->protocols as $p) {
            $grant=null; foreach ($this->memory->grants as $g) { if ($g['user_id']===$actor && $g['treatment_system_id']===$p['treatment_system_id'] && ($g['revoked_at']??null)===null) { $grant=$g; } }
            if (!$grant || !$grant['can_review'] || $this->memory->users[$actor]['status']!=='active') { continue; }
            foreach ($filters as $key=>$value) { if ($key!=='due_status' && $p[$key]!==$value) { continue 2; } }
            $approval=$this->memory->events[$p['approved_review_id']??0]??null;
            if (isset($filters['due_status']) && (!$approval || ($filters['due_status']==='expired' ? $approval['review_due_at']>$now : $approval['review_due_at']<=$now))) { continue; }
            $submission=$this->latestSubmission($p['id'],$p['content_revision']);
            $rows[]=$p+['disease_en'=>'Fixture disease','system_name'=>'Fixture system','submission_id'=>$submission['id']??null,'review_due_at'=>$approval['review_due_at']??null];
        }
        usort($rows,fn($a,$b)=>$a['id']<=>$b['id']); return array_slice($rows,$offset,51);
    }
    public function history(int $id,int $beforeId=0): array { return array_slice(array_values(array_reverse(array_filter($this->memory->events,fn($e)=>$e['protocol_id']===$id && (!$beforeId || $e['id']<$beforeId)),true)),0,51); }
    public function latestChangesRequired(int $id): ?array { foreach (array_reverse($this->memory->events,true) as $e) { if ($e['protocol_id']===$id && $e['event_type']==='changes_required') { return $e; } } return null; }
    public function editorialIdentity(int $id): ?array { return isset($this->memory->protocols[$id]) ? ['disease_en'=>'Fixture disease','disease_hi'=>'परीक्षण','system_name'=>'Fixture system'] : null; }
    public function editorialList(int $actor,array $filters,int $offset): array {
        $rows=[];
        foreach ($this->memory->protocols as $p) {
            $grant=null; foreach ($this->memory->grants as $g) { if ($g['user_id']===$actor && $g['treatment_system_id']===$p['treatment_system_id'] && ($g['revoked_at']??null)===null) { $grant=$g; } }
            if (!$grant || !$grant['can_edit'] || $this->memory->users[$actor]['status']!=='active') { continue; }
            foreach ($filters as $key=>$value) { if ($key!=='completeness' && $p[$key]!==$value) { continue 2; } }
            if (($filters['completeness']??null)==='unassessed' && $p['evidence_classification']!=='not_assessed' && $p['content_purpose']!==null) { continue; }
            $count=count(array_filter($this->memory->protocolRefs,fn($r)=>$r['protocol_id']===$p['id']));
            if (($filters['completeness']??null)==='missing_sources' && $count) { continue; }
            $rows[]=$p+['disease_en'=>'Fixture disease','disease_hi'=>'परीक्षण','system_name'=>'Fixture system','source_count'=>$count];
        }
        usort($rows,fn($a,$b)=>$a['id']<=>$b['id']); return array_slice($rows,$offset,51);
    }
    public function transaction(callable $action): mixed {
        $before=clone $this->memory;
        try { return $action(); }
        catch (Throwable $error) { foreach (get_object_vars($before) as $key=>$value) { $this->memory->$key=$value; } throw $error; }
    }
    public function find(int $id,bool $lock=false): ?array { if ($lock) { $this->memory->locks[]='protocol:'.$id; } return $this->memory->protocols[$id]??null; }
    public function system(int $id,bool $lock=false): ?array { if ($lock) { $this->memory->locks[]='system:'.$id; } return $this->memory->systems[$id]??null; }
    public function diseaseExists(int $id): bool { return $id===1; }
    public function aggregate(int $id,bool $lockChildren=false): array {
        $items=array_values(array_filter($this->memory->items,fn($i)=>$i['protocol_id']===$id)); $itemIds=array_column($items,'id');
        $p=array_values(array_filter($this->memory->protocolRefs,fn($r)=>$r['protocol_id']===$id));
        $i=array_values(array_filter($this->memory->itemRefs,fn($r)=>in_array($r['item_id'],$itemIds,true)));
        $sourceIds=array_merge(array_column($p,'source_id'),array_column($i,'source_id'));
        $sources=array_values(array_filter($this->memory->sources,fn($s)=>in_array($s['id'],$sourceIds,true)));
        if ($lockChildren) { $this->memory->locks[]='children:'.$id; }
        return ['protocol'=>$this->find($id),'items'=>$items,'protocol_sources'=>$p,'item_sources'=>$i,'sources'=>$sources];
    }
    public function updateProtocol(int $id,array $changes): void { $this->memory->protocols[$id]=array_replace($this->memory->protocols[$id],$changes); }
    public function updateItem(int $id,array $changes): void { $this->memory->items[$id]=array_replace($this->memory->items[$id],$changes); }
    public function link(int $protocolId,?int $itemId,int $sourceId,string $scope,?string $locator): void {
        $key=$itemId===null ? 'protocolRefs' : 'itemRefs'; $parent=$itemId===null ? 'protocol_id' : 'item_id';
        foreach ($this->memory->$key as &$row) { if ($row[$parent]===($itemId??$protocolId) && $row['source_id']===$sourceId) { $row['claim_scope']=$scope; $row['locator']=$locator; return; } } unset($row);
        $this->memory->{$key}[]=['id'=>count($this->memory->$key)+1,$parent=>$itemId??$protocolId,'source_id'=>$sourceId,'claim_scope'=>$scope,'locator'=>$locator];
    }
    public function unlink(int $protocolId,?int $itemId,int $sourceId): void { $key=$itemId===null ? 'protocolRefs' : 'itemRefs'; $parent=$itemId===null ? 'protocol_id' : 'item_id'; $this->memory->$key=array_values(array_filter($this->memory->$key,fn($r)=>!($r[$parent]===($itemId??$protocolId) && $r['source_id']===$sourceId))); }
    public function appendEvent(array $event): int { $id=count($this->memory->events)+1; $this->memory->events[$id]=$event+['id'=>$id]; $this->memory->locks[]='event:'.$id; return $id; }
    public function event(int $id): ?array { return $this->memory->events[$id]??null; }
    public function latestSubmission(int $id,int $revision): ?array { foreach (array_reverse($this->memory->events,true) as $event) { if ($event['protocol_id']===$id && $event['content_revision']===$revision && $event['event_type']==='submitted') { return $event; } } return null; }
    public function contributors(int $id): array { return array_column(array_filter($this->memory->events,fn($e)=>$e['protocol_id']===$id && $e['event_type']==='content_changed'),'actor_id'); }
    public function hasEvents(int $id): bool { return (bool)array_filter($this->memory->events,fn($e)=>$e['protocol_id']===$id); }
}

// Exercise the real repository transaction/retry code without a PDO connection.
final class GovernanceTransactionPDO extends PDO
{
    public bool $open=false;
    public int $begins=0,$commits=0,$rollbacks=0;
    public function __construct() {}
    public function beginTransaction(): bool { ++$this->begins; return $this->open=true; }
    public function inTransaction(): bool { return $this->open; }
    public function commit(): bool { ++$this->commits; $this->open=false; return true; }
    public function rollBack(): bool { ++$this->rollbacks; $this->open=false; return true; }
}
final class GovernanceTransactionRepository extends TreatmentGovernanceRepository
{
    public function __construct(PDO $pdo) { $this->db=$pdo; }
}
final class GovernanceContainerDatabase extends App\Core\Database
{
    public function __construct(private PDO $testConnection) {}
    public function connection(): PDO { return $this->testConnection; }
}
final class GovernanceDiseaseRepository extends App\Repositories\DiseaseRepository
{
    public function __construct() {}
    public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array { return [['id'=>99,'treatment_system_id'=>1,'title_en'=>'Legacy educational fixture']]; }
    public function findKnowledgeBySlug(string $slug,string $language='en'): ?array { return $slug==='missing' ? null : ['disease'=>['id'=>1],'symptoms'=>['preserved'],'treatments'=>[['legacy'=>'must be held']]]; }
}

function fixture(bool $legacy=false): array {
    global $testConfiguration; $testConfiguration=['treatment_governance'=>['publication_mode'=>'hold','permission_admin_user_ids'=>[4]]];
    $m=new GovernanceMemory(); $r=new TreatmentGovernanceRules(); $policy=new TreatmentGovernancePolicy(); $snapshot=new TreatmentGovernanceSnapshot($r);
    $p=['id'=>1,'disease_id'=>1,'treatment_system_id'=>1,'title_en'=>'Test protocol','title_hi'=>'परीक्षण','overview_en'=>'Overview','overview_hi'=>'विवरण'];
    $m->protocols[1]=$r->legacyProtocol($p);
    $m->items[1]=$r->legacyItem(['id'=>1,'protocol_id'=>1,'item_type'=>'lifestyle','title_en'=>'Test item','title_hi'=>'मद','instructions_en'=>'Instructions','instructions_hi'=>'निर्देश','sort_order'=>1]);
    if (!$legacy) {
        // Synthetic previously assessed decision, never a production default.
        $m->protocols[1]['risk_eligibility']='eligible';
        $m->protocols[1]=array_replace($m->protocols[1],['evidence_classification'=>'evidence_supported','content_purpose'=>'supportive_care','applicability_en'=>'Scope','applicability_hi'=>'दायरा','limitations_en'=>'Limits','limitations_hi'=>'सीमा']);
        $m->items[1]=array_replace($m->items[1],['is_included'=>1,'evidence_classification'=>'evidence_supported','content_purpose'=>'supportive_care','limitations_en'=>'Limits','limitations_hi'=>'सीमा']);
        $m->sources[1]=['id'=>1,'source_type'=>'guideline','title'=>'Fixture citation','citation_text'=>'Synthetic test citation','replaces_source_id'=>null,'status'=>'verified','created_by'=>1,'created_at'=>$r->now(),'verified_by'=>2,'verified_at'=>$r->now()];
        $m->protocolRefs=[['id'=>1,'protocol_id'=>1,'source_id'=>1,'claim_scope'=>'Overview claim','locator'=>null]];
        $m->itemRefs=[['id'=>1,'item_id'=>1,'source_id'=>1,'claim_scope'=>'Item claim','locator'=>null]];
    }
    $permissions=new GovernanceMemoryPermissions($m);
    foreach ([1=>[1,0,0],2=>[0,1,0],3=>[1,0,1]] as $actor=>$flags) { $permissions->insert(['user_id'=>$actor,'treatment_system_id'=>1,'can_edit'=>$flags[0],'can_review'=>$flags[1],'can_publish'=>$flags[2],'qualification_scope'=>$flags[1] ? 'Fixture reviewer scope' : null,'authorized_by'=>4,'granted_at'=>$r->now()]); }
    $repo=new GovernanceMemoryRepository($m); $sources=new GovernanceMemorySources($m);
    $auth=new TreatmentEditorialAuthorizationService(new GovernanceMemoryAuth($m),$permissions,$r,$policy);
    $service=new TreatmentGovernanceService($repo,$sources,$permissions,$auth,$r,$snapshot,$policy);
    return [$m,$r,$snapshot,$auth,$service,$repo,$sources,$permissions,$policy];
}

$passed=0;
function check(bool $condition,string $message): void { if (!$condition) { throw new LogicException($message); } }
function rejects(callable $action): void { try { $action(); } catch (RuntimeException $expected) { return; } throw new LogicException('Expected command rejection'); }
function test(string $name,callable $action): void {
    global $passed;
    try { $action(); ++$passed; echo "PASS $name\n"; }
    catch (Throwable $error) { fwrite(STDERR,"FAIL $name: ".get_class($error).': '.$error->getMessage()."\n"); exit(1); }
}
function reviewed(array $f): void { [$m,,,,$s]=$f; $m->actor=1; $event=$s->submit(1,$m->protocols[1]['content_revision'],$m->protocols[1]['review_state'],['en','hi']); $m->actor=2; $s->approve(1,$m->protocols[1]['content_revision'],$event,['en','hi'],'Independent fixture review','2099-01-01 00:00:00.000000',true); }
function published(array $f): void { reviewed($f); [$m,,,,$s]=$f; $m->actor=3; $s->publish(1,$m->protocols[1]['content_revision'],['en','hi']); }

foreach (['EVIDENCE','PURPOSE','REVIEW','PUBLICATION','SOURCE_TYPES','SOURCE_STATES','EVENTS','ITEM_TYPES'] as $group) {
    test("controlled $group values",function () use ($group) { $r=new TreatmentGovernanceRules(); $values=constant(TreatmentGovernanceRules::class.'::'.$group); foreach ($values as $v) { check($r->value($v,$values)===$v,'Value changed'); } foreach (['',strtoupper($values[0]),'unknown',0,null] as $bad) { rejects(fn()=>$r->value($bad,$values)); } });
}
test('locale SET validation and canonical order',function () { $r=new TreatmentGovernanceRules(); check($r->locales(['hi','en'])===['en','hi'],'Locale ordering'); foreach ([['en','en'],['xx'],['EN'],['en,hi'],'en',1] as $v) { rejects(fn()=>$r->locales($v)); } rejects(fn()=>$r->locales([],false)); });
test('source rejects direct self-replacement',function () { $r=new TreatmentGovernanceRules(); rejects(fn()=>$r->replacement(1,1,fn()=>null)); });
test('source rejects two-node cycle',function () { $r=new TreatmentGovernanceRules(); $rows=[1=>['id'=>1,'replaces_source_id'=>2],2=>['id'=>2,'replaces_source_id'=>1]]; rejects(fn()=>$r->replacement(3,1,fn($id)=>$rows[$id]??null)); });
test('source rejects longer cycle returning to candidate',function () { $r=new TreatmentGovernanceRules(); $rows=[1=>['id'=>1,'replaces_source_id'=>2],2=>['id'=>2,'replaces_source_id'=>3],3=>['id'=>3,'replaces_source_id'=>4]]; rejects(fn()=>$r->replacement(4,1,fn($id)=>$rows[$id]??null)); });
test('source accepts valid finite replacement chain',function () { $r=new TreatmentGovernanceRules(); $rows=[1=>['id'=>1,'replaces_source_id'=>null],2=>['id'=>2,'replaces_source_id'=>1],3=>['id'=>3,'replaces_source_id'=>2]]; $r->replacement(4,3,fn($id)=>$rows[$id]??null); $r->replacement(1,null,fn()=>null); });
test('source rejects missing predecessor',function () { $r=new TreatmentGovernanceRules(); rejects(fn()=>$r->replacement(2,1,fn()=>null)); });
foreach ([['unverified','verified'],['unverified','withdrawn'],['verified','withdrawn']] as [$from,$to]) { test("source lifecycle $from to $to",fn()=>(new TreatmentGovernanceRules())->sourceTransition($from,$to)); }
foreach ([['withdrawn','verified'],['withdrawn','unverified'],['verified','unverified'],['verified','verified'],['unverified','unverified']] as [$from,$to]) { test("source lifecycle rejects $from to $to",fn()=>rejects(fn()=>(new TreatmentGovernanceRules())->sourceTransition($from,$to))); }

test('draft submission then independent approval succeeds',function () { $f=fixture(); reviewed($f); check($f[0]->protocols[1]['review_state']==='approved','Not approved'); check($f[0]->protocols[1]['content_revision']===1,'Workflow revised content'); });
test('direct draft approval fails',function () { [$m,,,,$s]=fixture(); $m->actor=2; rejects(fn()=>$s->approve(1,1,1,['en'],'Reason','2099-01-01 00:00:00.000000',true)); });
test('approved edit invalidates approval globally',function () { $f=fixture(); reviewed($f); [$m,,,,$s]=$f; $m->actor=1; $s->edit(1,1,'approved',['overview_hi'=>'नया विवरण']); check($m->protocols[1]['content_revision']===2 && $m->protocols[1]['approved_review_id']===null && $m->protocols[1]['review_state']==='draft','Global invalidation failed'); });
test('changes required resubmission after revision',function () { [$m,,,,$s]=fixture(); $s->submit(1,1,'draft',['en']); $m->actor=2; $s->requestChanges(1,1,'Needs a correction'); $m->actor=1; $s->edit(1,1,'changes_required',['overview_en'=>'Corrected']); $s->submit(1,2,'changes_required',['en']); check($m->protocols[1]['review_state']==='pending_review','Resubmission failed'); });
test('pending edit returns to draft',function () { [$m,,,,$s]=fixture(); $s->submit(1,1,'draft',['en']); $s->edit(1,1,'pending_review',['overview_en'=>'Revised']); check($m->protocols[1]['review_state']==='draft','Pending review not invalidated'); });
test('published direct edit rejected with rollback',function () { $f=fixture(); published($f); [$m,,,,$s]=$f; $before=serialize($m); rejects(fn()=>$s->edit(1,1,'approved',['overview_en'=>'Forbidden'])); check(serialize($m)===$before,'Rejected transaction mutated state'); });
test('publication withdrawal requires publisher and reason',function () { $f=fixture(); published($f); [$m,,,,$s]=$f; $m->actor=1; rejects(fn()=>$s->withdraw(1,1,'approved','Reason')); $m->actor=3; rejects(fn()=>$s->withdraw(1,1,'approved',' ')); $s->withdraw(1,1,'approved','Fixture withdrawal'); check($m->protocols[1]['publication_status']==='withdrawn' && $m->protocols[1]['content_revision']===1,'Withdrawal state'); });
test('withdrawn cannot republish without fresh edit and review',function () { $f=fixture(); published($f); [$m,,,,$s]=$f; $s->withdraw(1,1,'approved','Reason'); rejects(fn()=>$s->publish(1,1,['en'])); $m->actor=1; rejects(fn()=>$s->submit(1,1,'draft',['en'])); $s->edit(1,1,'draft',['overview_en'=>'Fresh withdrawal correction']); reviewed($f); $m->actor=3; $s->publish(1,2,['en']); check($m->protocols[1]['publication_status']==='published','Fresh approval did not permit publication'); });
test('approved fresh review reset has a new submission',function () { $f=fixture(); reviewed($f); [$m,,,,$s]=$f; $s->resetReview(1,1,'approved','Fresh independent review'); check($m->protocols[1]['review_state']==='pending_review' && $m->protocols[1]['approved_review_id']===null,'Reset failed'); $submission=$f[5]->latestSubmission(1,1); $s->approve(1,1,$submission['id'],['en'],'Reason','2099-01-01 00:00:00.000000',true); });

test('unauthenticated actor denied',function () { [$m,,,$a,$s]=fixture(); $m->actor=0; check(!$a->can('edit',1),'Anonymous access'); rejects(fn()=>$s->edit(1,1,'draft',['overview_en'=>'No'])); });
test('inactive authenticated actor denied',function () { [$m,,,$a]=fixture(); $m->users[1]['status']='inactive'; check(!$a->can('edit',1),'Inactive access'); });
test('editor cannot approve',function () { [$m,,,,$s]=fixture(); $id=$s->submit(1,1,'draft',['en']); rejects(fn()=>$s->approve(1,1,$id,['en'],'Reason','2099-01-01 00:00:00.000000',true)); });
test('reviewer cannot publish',function () { $f=fixture(); reviewed($f); rejects(fn()=>$f[4]->publish(1,1,['en'])); });
test('system A does not grant system B or wildcard',function () { [,,, $a]=fixture(); check(!$a->can('edit',2),'Cross-system permission'); rejects(fn()=>$a->require('edit',0)); check(!$a->can('unknown',1),'Unknown action'); });
test('review grant requires qualification and capabilities',function () { $r=new TreatmentGovernanceRules(); rejects(fn()=>$r->grant(['user_id'=>1,'treatment_system_id'=>1,'can_edit'=>0,'can_review'=>1,'can_publish'=>0])); rejects(fn()=>$r->grant(['user_id'=>1,'treatment_system_id'=>1,'can_edit'=>0,'can_review'=>0,'can_publish'=>0])); });
test('allowlist only manages permissions, no editorial bypass',function () { [$m,,,$a,$s]=fixture(); $m->actor=1; rejects(fn()=>$s->grantPermission(2,2,['can_review'=>1],'Scope')); $m->actor=4; check(!$a->can('approve',1),'Administrator bypass'); $s->grantPermission(2,2,['can_review'=>1],'Scope'); $m->actor=2; check($a->can('approve',2),'Explicit grant failed'); });
test('permission replacement retains revoked grant history',function () { [$m,,,$a,$s]=fixture(); $m->actor=4; rejects(fn()=>$s->grantPermission(1,1,['can_review'=>1],'Scope')); $s->grantPermission(1,1,['can_review'=>1],'Scope','Replace editor qualification'); check(count($m->grants)===4 && $m->grants[1]['revoked_at']!==null,'Grant history lost'); $m->actor=1; check(!$a->can('edit',1) && $a->can('approve',1),'Replaced grant authorization'); });
test('revoked permission blocks future commands',function () { [$m,,,$a,$s]=fixture(); $m->actor=4; $s->revokePermission(1,1,'Access ended'); $m->actor=1; check(!$a->can('edit',1),'Revocation bypass'); rejects(fn()=>$s->edit(1,1,'draft',['overview_en'=>'No'])); });
test('reviewer cannot approve materially authored content',function () { [$m,,,,$s,,,$permissions]=fixture(); $s->edit(1,1,'draft',['overview_en'=>'Authored']); $submission=$s->submit(1,2,'draft',['en']); $permissions->insert(['user_id'=>1,'treatment_system_id'=>1,'can_edit'=>1,'can_review'=>1,'can_publish'=>0,'qualification_scope'=>'Scope']); rejects(fn()=>$s->approve(1,2,$submission,['en'],'Reason','2099-01-01 00:00:00.000000',true)); });
test('unknown authorship requires explicit independence attestation',function () { [$m,,,,$s]=fixture(); $id=$s->submit(1,1,'draft',['en']); $m->actor=2; rejects(fn()=>$s->approve(1,1,$id,['en'],'Reason','2099-01-01 00:00:00.000000',false)); $s->approve(1,1,$id,['en'],'Attested independence','2099-01-01 00:00:00.000000',true); });
test('expired approval cannot publish',function () { $f=fixture(); reviewed($f); [$m,,,,$s]=$f; $m->events[$m->protocols[1]['approved_review_id']]['review_due_at']='2000-01-01 00:00:00.000000'; $m->actor=3; rejects(fn()=>$s->publish(1,1,['en'])); });
test('approval requires future due date and rationale',function () { [$m,,,,$s]=fixture(); $id=$s->submit(1,1,'draft',['en']); $m->actor=2; rejects(fn()=>$s->approve(1,1,$id,['en'],'Reason','2000-01-01 00:00:00.000000',true)); rejects(fn()=>$s->approve(1,1,$id,['en'],' ','2099-01-01 00:00:00.000000',true)); });

test('one revision for combined protocol and item edit',function () { [$m,,,,$s]=fixture(); $s->edit(1,1,'draft',['overview_en'=>'Changed','overview_hi'=>'बदला'],[1=>['instructions_en'=>'Changed','sort_order'=>2]]); check($m->protocols[1]['content_revision']===2 && count($m->events)===1,'Multiple increments'); });
test('no-op edit and no-op reference preserve revision and history',function () { [$m,,,,$s]=fixture(); check(!$s->edit(1,1,'draft',['title_en'=>'Test protocol'],[1=>['is_included'=>'1']]),'No-op changed'); check(!$s->reference(1,1,'draft',1,null,'Overview claim'),'No-op reference changed'); check($m->protocols[1]['content_revision']===1 && !$m->events,'No-op revised'); });
test('stale revision and stale state reject',function () { [$m,,,,$s]=fixture(); rejects(fn()=>$s->edit(1,2,'draft',['overview_en'=>'Changed'])); rejects(fn()=>$s->edit(1,1,'pending_review',['overview_en'=>'Changed'])); });
test('source relationship edit increments once',function () { [$m,,,,$s]=fixture(); $s->reference(1,1,'draft',1,null,'Changed claim','Page 2'); check($m->protocols[1]['content_revision']===2 && $m->protocolRefs[0]['locator']==='Page 2','Reference change failed'); });
test('source replacement relationship invalidates approval',function () { $f=fixture(); reviewed($f); [$m,,,,$s]=$f; $m->actor=1; $id=$s->createSource(1,['source_type'=>'guideline','title'=>'Correction','citation_text'=>'Corrected fixture','replaces_source_id'=>1]); $s->reference(1,1,'approved',$id,null,'New claim'); check($m->protocols[1]['content_revision']===2 && $m->protocols[1]['approved_review_id']===null,'Replacement invalidation'); });
test('edit cannot accept permissive ENUM coercion',function () { [$m,,,,$s]=fixture(); foreach (['','SUPPORTED',0] as $v) { rejects(fn()=>$s->edit(1,1,'draft',['evidence_classification'=>$v])); } rejects(fn()=>$s->edit(1,1,'draft',[],[1=>['item_type'=>'bogus']])); });
test('protocol coherency rejects impossible approval/publication',function () { [$m,$r]=fixture(); foreach ([['review_state'=>'approved'],['publication_status'=>'published'],['published_locales'=>'en'],['content_revision'=>0]] as $change) { rejects(fn()=>$r->protocol(array_replace($m->protocols[1],$change))); } });
test('review requires explicit protocol and item verified sources',function () { [$m,,,,$s]=fixture(); $m->itemRefs=[]; rejects(fn()=>$s->submit(1,1,'draft',['en'])); $m->itemRefs=[['id'=>1,'item_id'=>1,'source_id'=>1,'claim_scope'=>'Claim','locator'=>null]]; $m->protocolRefs=[]; rejects(fn()=>$s->submit(1,1,'draft',['en'])); });
test('unverified source and excluded items block submission',function () { [$m,,,,$s]=fixture(); $m->sources[1]['status']='unverified'; rejects(fn()=>$s->submit(1,1,'draft',['en'])); $m->sources[1]['status']='verified'; $m->items[1]['is_included']=0; rejects(fn()=>$s->submit(1,1,'draft',['en'])); });
test('publication language subset changes no revision',function () { $f=fixture(); published($f); [$m,,,,$s]=$f; $s->publish(1,1,['en']); check($m->protocols[1]['content_revision']===1 && $m->protocols[1]['published_locales']==='en','Language subset revised'); });
test('unassessed clinical exceptions fail closed for publication',function () {
    $f=fixture(); $m=$f[0]; $m->protocols[1]['evidence_classification']='insufficient_evidence';
    $submission=$f[4]->submit(1,1,'draft',['en','hi']); $m->actor=2;
    // Phase 2C-3 additionally blocks new approvals for unresolved classifications.
    rejects(fn()=>$f[4]->approve(1,1,$submission,['en','hi'],'Reason','2099-01-01 00:00:00.000000',true));
    check(count($m->events)===1 && $m->protocols[1]['approved_review_id']===null,'Policy rejection created approval');
    // Simulate a historically persisted pre-2C-3 approval solely in memory:
    // retain the independent publication-policy backstop regression.
    $event=$m->events[$submission]; $event=array_replace($event,['id'=>2,'event_type'=>'approved','actor_id'=>2,'notes'=>'Historical fixture approval','review_due_at'=>'2099-01-01 00:00:00.000000','independence_confirmed'=>1]);
    $m->events[2]=$event; $m->protocols[1]['review_state']='approved'; $m->protocols[1]['approved_review_id']=2;
    $m->actor=3; rejects(fn()=>$f[4]->publish(1,1,['en']));
});

test('canonical object ordering deterministic',function () { [,,$s]=fixture(); check($s->canonical(['b'=>1,'a'=>['y'=>2,'x'=>1]])===$s->canonical(['a'=>['x'=>1,'y'=>2],'b'=>1]),'Object ordering changed'); });
test('canonical hash stable across row ordering and numeric representation',function () { [$m,,$s,,, $repo]=fixture(); $a=$repo->aggregate(1); $a['items'][]=array_replace($a['items'][0],['id'=>2,'sort_order'=>2]); $a['protocol_sources'][]=['id'=>2,'protocol_id'=>1,'source_id'=>1,'claim_scope'=>'Claim','locator'=>null]; $b=$a; $b['items']=array_reverse($b['items']); $b['protocol_sources']=array_reverse($b['protocol_sources']); $b['protocol']['id']='1'; $b['items'][0]['sort_order']='2'; $b['items'][1]['is_included']='1'; check($s->capture($a,['hi','en'])===$s->capture($b,['en','hi']),'Canonical representation unstable'); });
test('null and omitted optional content have identical hash',function () { [,, $s,,, $repo]=fixture(); $a=$repo->aggregate(1); $b=$a; unset($b['protocol']['evidence_summary_en']); $a['protocol']['evidence_summary_en']=null; check($s->capture($a,['en'])===$s->capture($b,['en']),'Null handling changed'); });
test('governed text whitespace and UTF8 changes alter hash',function () { [,, $s,,, $repo]=fixture(); $a=$repo->aggregate(1); $b=$a; $b['protocol']['overview_hi'].=' '; check($s->capture($a,['en'])!==$s->capture($b,['en']),'Whitespace lost'); check(str_contains($s->capture($a,['hi'])['content_snapshot'],'परीक्षण'),'UTF8 escaped'); });
test('source bibliographic version and claim scope alter hash',function () { [,, $s,,, $repo]=fixture(); $a=$repo->aggregate(1); foreach (['source','scope'] as $field) { $b=$a; if ($field==='source') { $b['sources'][0]['edition_or_version']='2'; } else { $b['item_sources'][0]['claim_scope']='Changed'; } check($s->capture($a,['en'])!==$s->capture($b,['en']),'Source change missed'); } });
test('snapshot excludes arbitrary credentials and user information',function () { [,, $s,,, $repo]=fixture(); $a=$repo->aggregate(1); $a['protocol']['password']='test-secret'; $a['sources'][0]['email']='test-private'; $json=$s->capture($a,['en'])['content_snapshot']; check(!str_contains($json,'test-secret') && !str_contains($json,'test-private'),'Unexpected snapshot fields'); });
test('canonical numeric flags and no pretty-print whitespace',function () { [,, $s,,, $repo]=fixture(); $j=$s->capture($repo->aggregate(1),['en'])['content_snapshot']; check(str_contains($j,'"is_included":true') && str_contains($j,'"content_revision":1') && !str_contains($j,"\n"),'Canonical representation'); rejects(fn()=>$s->canonical(['number'=>1.1])); });
test('event snapshot hash must match and null actor is legacy-only',function () { [, $r]=fixture(); $base=['protocol_id'=>1,'content_revision'=>1,'event_type'=>'content_changed','actor_id'=>null]; rejects(fn()=>$r->event($base)); $base['actor_id']=1; $base['content_snapshot']='{}'; $base['content_hash']=str_repeat('0',64); rejects(fn()=>$r->event($base)); $r->event(['protocol_id'=>1,'content_revision'=>1,'event_type'=>'legacy_initialized','actor_id'=>null]); });

test('source creation validation, immutability and explicit verification',function () { [$m,,,,$s]=fixture(); $id=$s->createSource(1,['source_type'=>'research_study','title'=>'Synthetic study','citation_text'=>'Citation']); check($m->sources[$id]['status']==='unverified','Auto verified'); $m->actor=2; $s->verifySource(1,$id); check($m->sources[$id]['status']==='verified','Not verified'); check(!method_exists($s,'restoreSource') && !method_exists($s,'updateSource'),'Mutable source API'); rejects(fn()=>$s->verifySource(1,$id)); });
test('source creation rejects malformed enum, date and oversized fields',function () { [,,,,$s]=fixture(); foreach ([['source_type'=>'unknown'],['publication_date'=>'2025-02-30'],['title'=>str_repeat('x',501)],['status'=>'verified']] as $change) { rejects(fn()=>$s->createSource(1,array_replace(['source_type'=>'guideline','title'=>'Fixture','citation_text'=>'Citation'],$change))); } });
test('source creation rejects preexisting cyclic chain transactionally',function () { [$m,,,,$s]=fixture(); $m->sources[1]['replaces_source_id']=2; $m->sources[2]=array_replace($m->sources[1],['id'=>2,'replaces_source_id'=>1]); $before=serialize($m); rejects(fn()=>$s->createSource(1,['source_type'=>'guideline','title'=>'Fixture','citation_text'=>'Citation','replaces_source_id'=>1])); check(serialize($m)===$before,'Cyclic chain insertion committed'); });
test('source creation accepts successive immutable replacement versions',function () { [$m,,,,$s]=fixture(); $a=$s->createSource(1,['source_type'=>'guideline','title'=>'Version 2','citation_text'=>'Citation','replaces_source_id'=>1]); $b=$s->createSource(1,['source_type'=>'guideline','title'=>'Version 3','citation_text'=>'Citation','replaces_source_id'=>$a]); check($m->sources[$b]['replaces_source_id']===$a && $m->sources[1]['title']==='Fixture citation','Replacement mutated original'); });
test('source withdrawal invalidates each affected protocol once and retains references',function () { $f=fixture(); published($f); [$m,,,,$s]=$f; $m->protocols[2]=array_replace($m->protocols[1],['id'=>2,'review_state'=>'draft','publication_status'=>'unpublished','approved_review_id'=>null,'published_locales'=>'']); $m->protocolRefs[]=['id'=>2,'protocol_id'=>2,'source_id'=>1,'claim_scope'=>'Shared source','locator'=>null]; $beforeRefs=serialize([$m->protocolRefs,$m->itemRefs]); $s->withdrawSource(1,1,'Provenance withdrawn'); foreach ($m->protocols as $p) { check($p['content_revision']===2 && $p['review_state']==='draft' && $p['approved_review_id']===null,'Source invalidation failed'); } check($m->protocols[1]['publication_status']==='withdrawn','Publication continued'); check(serialize([$m->protocolRefs,$m->itemRefs])===$beforeRefs,'References deleted'); $events=array_filter($m->events,fn($e)=>$e['event_type']==='source_invalidated'); check(count($events)===2,'Duplicate/missing invalidations'); $m->actor=2; rejects(fn()=>$s->verifySource(1,1)); });
test('unverified source withdrawal succeeds and is terminal',function () { [$m,,,,$s]=fixture(); $id=$s->createSource(1,['source_type'=>'other','title'=>'Fixture','citation_text'=>'Citation']); $m->actor=3; $s->withdrawSource(1,$id,'Withdraw unverified'); check($m->sources[$id]['status']==='withdrawn','Not withdrawn'); rejects(fn()=>$s->withdrawSource(1,$id,'Again')); });
test('cross-system source withdrawal fails closed atomically',function () { [$m,,,,$s]=fixture(); $m->protocols[2]=array_replace($m->protocols[1],['id'=>2,'treatment_system_id'=>2]); $m->protocolRefs[]=['id'=>2,'protocol_id'=>2,'source_id'=>1,'claim_scope'=>'Claim','locator'=>null]; $m->actor=3; $before=serialize($m); rejects(fn()=>$s->withdrawSource(1,1,'Reason')); check(serialize($m)===$before,'Unauthorized source withdrawal committed'); });
test('source locks precede protocol and child locks',function () { [$m,,,,$s]=fixture(); $s->reference(1,1,'draft',1,null,'Changed'); $source=array_search('source:1',$m->locks,true); $protocol=array_search('protocol:1',$m->locks,true); $children=array_search('children:1',$m->locks,true); check($source!==false && $source<$protocol && $protocol<$children,'Lock order violated'); });
test('real transaction retries deadlock after rollback and reruns validation',function () { $pdo=new GovernanceTransactionPDO(); $repo=new GovernanceTransactionRepository($pdo); $runs=0; $result=$repo->transaction(function () use (&$runs) { if (++$runs===1) { $error=new PDOException('Synthetic deadlock'); $error->errorInfo=['40001',1213,'Synthetic']; throw $error; } return 'Revalidated'; }); check($result==='Revalidated' && $runs===2 && $pdo->begins===2 && $pdo->rollbacks===1 && $pdo->commits===1,'Retry did not revalidate'); });
test('real transaction bounds retry and safely reports database error',function () { $pdo=new GovernanceTransactionPDO(); $repo=new GovernanceTransactionRepository($pdo); $runs=0; try { $repo->transaction(function () use (&$runs) { ++$runs; $error=new PDOException('Synthetic private SQL detail'); $error->errorInfo=['40001',1213,'Synthetic']; throw $error; }); throw new LogicException('Expected failure'); } catch (RuntimeException $error) { check($error->getPrevious() instanceof PDOException && !str_contains($error->getMessage(),'private SQL'),'Unsafe error'); } check($runs===3 && $pdo->rollbacks===3 && $pdo->commits===0,'Retry bound'); });
test('real transaction rolls back and rethrows assertion failures',function () { $pdo=new GovernanceTransactionPDO(); $repo=new GovernanceTransactionRepository($pdo); try { $repo->transaction(fn()=>throw new AssertionError('Sentinel assertion')); throw new LogicException('Assertion swallowed'); } catch (AssertionError $error) { check($error->getMessage()==='Sentinel assertion','Assertion replaced'); } check($pdo->rollbacks===1 && !$pdo->open,'Assertion did not roll back'); });
test('real transaction does not retry non-deadlock failures or allow nesting',function () { $pdo=new GovernanceTransactionPDO(); $repo=new GovernanceTransactionRepository($pdo); rejects(fn()=>$repo->transaction(fn()=>throw new PDOException('Synthetic constraint failure'))); check($pdo->begins===1 && $pdo->rollbacks===1,'Unexpected retry'); $pdo->open=true; rejects(fn()=>$repo->transaction(fn()=>true)); check($pdo->begins===1,'Nested transaction began'); });

test('legacy defaults preserve all original domain content',function () { [$m,$r]=fixture(true); $p=$m->protocols[1]; check($p['review_state']==='draft' && $p['publication_status']==='unpublished' && $p['content_revision']===1 && $p['approved_review_id']===null && $p['published_locales']==='' && $p['evidence_classification']==='not_assessed','Legacy protocol defaults'); check($m->items[1]['is_included']===0 && $m->items[1]['evidence_classification']==='not_assessed','Legacy item defaults'); check($p['title_hi']==='परीक्षण' && $m->items[1]['sort_order']===1 && !$m->sources && !$m->events,'Legacy content altered/fabricated'); });
test('legacy initialization is controlled append-only and idempotent',function () { [$m,,,,$s]=fixture(true); $m->actor=4; $before=serialize([$m->protocols,$m->items]); check($s->initializeLegacy(1),'Initialization failed'); check(!$s->initializeLegacy(1),'Duplicate initialization'); check(serialize([$m->protocols,$m->items])===$before && count($m->events)===1 && $m->events[1]['actor_id']===null && $m->events[1]['event_type']==='legacy_initialized','Initialization mutated content'); });
test('legacy initializer rejects assessed or fabricated governance',function () { [$m,,,,$s]=fixture(true); $m->actor=4; $m->protocols[1]['evidence_classification']='evidence_supported'; rejects(fn()=>$s->initializeLegacy(1)); });
foreach ([[],['publication_mode'=>'bad'],['publication_mode'=>1],['publication_mode'=>'HOLD'],['publication_mode'=>'hold']] as $setting) { test('hold mode fails closed '.json_encode($setting),function () use ($setting) { global $testConfiguration; $testConfiguration=['treatment_governance'=>$setting]; $p=new TreatmentGovernancePolicy(); check($p->mode()==='hold' && $p->publicTreatments(fn()=>['unsafe'])===[],'Hold leaked'); }); }
test('governed recognized but never falls back to legacy reader',function () { global $testConfiguration; $testConfiguration=['treatment_governance'=>['publication_mode'=>'governed']]; $p=new TreatmentGovernancePolicy(); check($p->mode()==='governed' && $p->publicTreatments()===[],'Premature public cutover'); });
function emptyPublicationReader(): App\Services\TreatmentPublicationService {
    $rules = new TreatmentGovernanceRules();
    $repository = new class extends App\Repositories\TreatmentPublicationRepository {
        public function __construct() {}
        public function candidates(int $diseaseId, ?int $protocolId = null): array { return []; }
    };
    return new App\Services\TreatmentPublicationService($repository, $rules, new TreatmentGovernanceSnapshot($rules), new TreatmentGovernancePolicy());
}

test('actual disease detail restores legacy display independently of governed publication',function () {
    global $testConfiguration;
    $reflection=new ReflectionClass(App\Services\DiseaseService::class); $service=$reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('diseaseReferenceService')->setValue($service,new App\Services\DiseaseReferenceService());
    $reflection->getProperty('repository')->setValue($service,new GovernanceDiseaseRepository());
    $reflection->getProperty('treatmentPublicationService')->setValue($service,emptyPublicationReader());
    foreach (['hold','governed','invalid'] as $mode) {
        $testConfiguration=['treatment_governance'=>['publication_mode'=>$mode]];
        $knowledge=$service->getKnowledgeBySlug('fixture');
        check($knowledge['treatments'][0]['id']===99 && $knowledge['symptoms']===['preserved'] && $knowledge['disease']['id']===1,'Legacy detail altered other content or mixed aggregate rows');
        check(emptyPublicationReader()->getPublicTreatmentsForDisease(1)===[],'Governed reader gained legacy fallback');
    }
    check($service->getKnowledgeBySlug('missing')===null,'Missing disease handling changed');
});
test('existing container resolves the governance and disease services with shared PDO',function () {
    $pdo=new GovernanceTransactionPDO(); $container=new App\Core\Container();
    (new ReflectionProperty($container,'instances'))->setValue($container,[App\Core\Database::class=>new GovernanceContainerDatabase($pdo)]);
    $service=$container->get(TreatmentGovernanceService::class);
    check($service instanceof TreatmentGovernanceService && $container->get(App\Services\DiseaseService::class) instanceof App\Services\DiseaseService,'Container resolution failed');
    $db=new ReflectionProperty(App\Repositories\BaseRepository::class,'db');
    foreach (['repository','sources','permissions'] as $property) { $repo=(new ReflectionProperty($service,$property))->getValue($service); check($db->getValue($repo)===$pdo,'Repositories do not share transaction connection'); }
    check($pdo->begins===0,'Container issued transaction');
});
test('missing and malformed administrator allowlist fail closed',function () { global $testConfiguration; $p=new TreatmentGovernancePolicy(); foreach ([null,['permission_admin_user_ids'=>['1']],['permission_admin_user_ids'=>[0]],['permission_admin_user_ids'=>'*']] as $setting) { $testConfiguration=['treatment_governance'=>$setting]; check($p->administrators()===[],'Invalid administrator allowlist'); } });

test('schema declares exact trigger correction and remaining integrity backstops',function () {
    $sql=file_get_contents(dirname(__DIR__).'/database/structure.sql');
    foreach (['AFTER INSERT ON `treatment_sources` FOR EACH ROW','AFTER UPDATE ON `treatment_sources` FOR EACH ROW','IF NEW.replaces_source_id = NEW.id THEN',"SIGNAL SQLSTATE '45000'",'chk_treatment_source_lifecycle','chk_treatment_approval_pointer','chk_treatment_publication','fk_treatment_approved_revision','uq_treatment_active_grant','chk_treatment_grant_qualification','chk_treatment_event_approval'] as $text) { check(str_contains($sql,$text),'Missing schema definition '.$text); }
    check(substr_count($sql,'IF NEW.replaces_source_id = NEW.id THEN')===2,'Wrong trigger count');
    check(!preg_match('/CHECK\s*\([^;]*replaces_source_id\s*[^;]*`id`/i',$sql),'Impossible AUTO_INCREMENT CHECK retained');
});

test('review reset rejects the superseded submission without creating approval',function () {
    $f=fixture(); reviewed($f); [$m,,,,$service]=$f;
    $old=$f[5]->latestSubmission(1,1); $service->resetReview(1,1,'approved','Require a fresh decision');
    $before=serialize($m); rejects(fn()=>$service->approve(1,1,$old['id'],['en'],'Stale decision','2099-01-01 00:00:00.000000',true));
    check(serialize($m)===$before && $m->protocols[1]['review_state']==='pending_review','Superseded review created approval');
});

// Model an InnoDB snapshot predating a committed reset while exercising the
// actual repository query. A locking read sees the replacement submission.
final class GovernanceSubmissionPDO extends PDO
{
    public bool $open=false; public array $queries=[];
    public function __construct() {}
    public function inTransaction(): bool { return $this->open; }
    public function prepare(string $query,array $options=[]): PDOStatement|false {
        return new GovernanceSubmissionStatement($this,$query);
    }
}
final class GovernanceSubmissionStatement extends PDOStatement
{
    public function __construct(private GovernanceSubmissionPDO $pdo,private string $sql) {}
    public function execute(?array $params=null): bool { $this->pdo->queries[]=['sql'=>$this->sql,'params'=>$params]; return true; }
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0): mixed {
        return ['id'=>str_ends_with($this->sql,' FOR UPDATE') ? 108 : 105,'protocol_id'=>1,'content_revision'=>5,'event_type'=>'submitted'];
    }
}
test('transaction submission lookup sees reset committed after the initial snapshot',function () {
    $pdo=new GovernanceSubmissionPDO(); $repo=new GovernanceTransactionRepository($pdo); $pdo->open=true;
    check($repo->latestSubmission(1,5)['id']===108,'Command read the superseded snapshot submission');
    check($pdo->queries[0]['params']===['id'=>1,'revision'=>5],'Submission scope was not bound');
});
test('presentation submission lookup does not acquire governance event locks',function () {
    $pdo=new GovernanceSubmissionPDO(); $repo=new GovernanceTransactionRepository($pdo);
    check($repo->latestSubmission(1,5)['id']===105 && !$pdo->open,'Presentation started a transaction or acquired a lock');
});

echo "Treatment governance: $passed tests passed; no database connection or writes.\n";

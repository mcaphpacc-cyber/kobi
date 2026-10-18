<?php
declare(strict_types=1);

// Memory-only Phase 4B-3 command, HTTP, history and canonical public-boundary checks.
ob_start();
require __DIR__.'/treatment-creation.php';
$operationsBaseline=$passed;

final class OperationsRepository extends App\Repositories\TreatmentGovernanceRepository
{
    public ?Throwable $failure=null;
    public string $stage='event';
    public int $failEvent=1;
    private int $eventCalls=0;
    public function __construct(private GovernanceMemory $memory,private GovernanceMemoryRepository $inner) {}
    private function fail(string $stage): void { if ($this->failure && $this->stage===$stage) { throw $this->failure; } }
    public function transaction(callable $action): mixed { return $this->inner->transaction($action); }
    public function find(int $id,bool $lock=false): ?array { $this->fail('find'); return $this->inner->find($id,$lock); }
    public function system(int $id,bool $lock=false): ?array { return $this->inner->system($id,$lock); }
    public function diseaseExists(int $id): bool { return $this->inner->diseaseExists($id); }
    public function aggregate(int $id,bool $lockChildren=false): array { return $this->inner->aggregate($id,$lockChildren); }
    public function editorialIdentity(int $id): ?array { return $this->inner->editorialIdentity($id); }
    public function history(int $id,int $beforeId=0): array { $this->fail('history'); return $this->inner->history($id,$beforeId); }
    public function event(int $id): ?array { return $this->inner->event($id); }
    public function latestSubmission(int $id,int $revision): ?array { return $this->inner->latestSubmission($id,$revision); }
    public function contributors(int $id): array { return $this->inner->contributors($id); }
    public function updateProtocol(int $id,array $changes): void { $this->inner->updateProtocol($id,$changes); $this->fail('update'); }
    public function updateItem(int $id,array $changes): void { $this->inner->updateItem($id,$changes); }
    public function appendEvent(array $event): int { $id=$this->inner->appendEvent($event); if (++$this->eventCalls===$this->failEvent) { $this->fail('event'); } return $id; }
    public function operationsList(int $actor,array $filters,int $offset): array {
        $this->fail('list'); $rows=[];
        foreach ($this->memory->protocols as $p) {
            $grant=null; foreach ($this->memory->grants as $g) { if ($g['user_id']===$actor && $g['treatment_system_id']===$p['treatment_system_id'] && $g['revoked_at']===null) { $grant=$g; } }
            if (!$grant || ($this->memory->users[$actor]['status']??null)!=='active' || !($grant['can_edit'] || $grant['can_review'] || $grant['can_publish'])) { continue; }
            foreach ($filters as $field=>$value) { if ($p[$field]!==$value) { continue 2; } }
            $rows[]=$p+$this->inner->editorialIdentity($p['id']);
        }
        usort($rows,fn($a,$b)=>$a['id']<=>$b['id']); return array_slice($rows,$offset,51);
    }
}
function operationsFixture(string $state='published'): array {
    [$f]=editorialFixture();
    if ($state==='published' || $state==='withdrawn') { published($f); }
    elseif ($state==='approved') { reviewed($f); }
    elseif ($state==='pending_review') { $f[4]->submit(1,1,'draft',['en','hi']); }
    elseif ($state==='changes_required') { $f[4]->submit(1,1,'draft',['en','hi']); $f[0]->actor=2; $f[4]->requestChanges(1,1,'Synthetic corrections',1); }
    if ($state==='withdrawn') { $f[4]->withdraw(1,1,'approved','Prior withdrawal'); }
    $repo=new OperationsRepository($f[0],$f[5]); $permissions=new HardeningPermissions($f[7]);
    $auth=new App\Services\TreatmentEditorialAuthorizationService(new GovernanceMemoryAuth($f[0]),$permissions,$f[1],$f[8]);
    $g=new App\Services\TreatmentGovernanceService($repo,$f[6],$permissions,$auth,$f[1],$f[2],$f[8]);
    $e=new App\Services\TreatmentEditorialService($repo,$f[6],$permissions,$auth,$g,$f[1]);
    $c=new EditorialController($e,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]);
    $f[0]->actor=$state==='published' ? 3 : 2;
    return [$f,$repo,$permissions,$g,$e,$c];
}
function operationsInput(array $f,array $replace=[]): array { return array_replace(['_csrf_token'=>'fixture-csrf','expected_revision'=>(string)$f[0]->protocols[1]['content_revision'],'expected_state'=>$f[0]->protocols[1]['review_state'],'reason'=>'Synthetic governance reason'],$replace); }
function preservedHistory(array $old,GovernanceMemory $m): void { foreach ($old as $id=>$event) { check(($m->events[$id]??null)===$event,'Historical event/snapshot changed'); } }

test('4B3 publisher-only scoped grant can list and withdraw without editor/reviewer rights',function () {
    [$f,,,,$e,$c]=operationsFixture(); $f[0]->grants[3]['can_edit']=0;
    check(count($e->operationsListing([])['protocols'])===1,'Publisher route unavailable');
    $data=$e->operationsDetail(1); check($data['canWithdraw'] && !$data['canReset'] && !$data['canEdit'] && !$data['canReview'],'Publisher capability confusion');
    $html=renderEditorial('operation',$data); check(str_contains($html,'Confirm withdrawal') && !str_contains($html,'Confirm review reset') && !str_contains($html,'/publish'),'Incorrect operations form');
    check(controllerPost($c,'withdraw',operationsInput($f))==='/admin/treatments/1/operations?withdrawn=1','Withdrawal redirect');
    check($f[0]->protocols[1]['publication_status']==='withdrawn','Withdrawal failed');
});
test('4B3 withdrawal preserves content and history, clears approval/locales at same revision',function () {
    [$f,,,,$e]=operationsFixture(); $m=$f[0]; $old=$m->events; $items=$m->items; $content=array_intersect_key($m->protocols[1],array_flip(App\Services\TreatmentGovernanceRules::PROTOCOL_CONTENT));
    $e->withdrawProtocol(1,operationsInput($f)); $p=$m->protocols[1]; $event=end($m->events);
    check($p['publication_status']==='withdrawn' && $p['review_state']==='draft' && $p['content_revision']===1 && $p['approved_review_id']===null && $p['published_locales']==='','Withdrawal state');
    check($items===$m->items && $content===array_intersect_key($p,array_flip(App\Services\TreatmentGovernanceRules::PROTOCOL_CONTENT)),'Content deleted or mutated');
    preservedHistory($old,$m); check(count($m->events)===count($old)+1 && $event['event_type']==='withdrawn' && $event['protocol_id']===1 && $event['content_revision']===1 && $event['actor_id']===3 && $event['notes']==='Synthetic governance reason','Withdrawal attribution');
    $f[1]->timestamp($event['occurred_at']); check(($event['content_snapshot']??null)===null && ($event['content_hash']??null)===null,'False withdrawal snapshot');
});
foreach (['withdraw'=>'published','resetReview'=>'approved'] as $method=>$state) {
    foreach (['editor','wrong_capability','no_grant','revoked','inactive_user','wrong_scope'] as $case) {
        test('4B3 '.$method.' rejects '.$case,function () use ($method,$state,$case) {
            [$f,,,,$e,$c]=operationsFixture($state); $m=$f[0]; $actor=$method==='withdraw' ? 3 : 2;
            if ($case==='editor') { $m->actor=1; } elseif ($case==='wrong_capability') { $m->actor=$method==='withdraw' ? 2 : 3; } elseif ($case==='no_grant') { $m->actor=4; } elseif ($case==='revoked') { $m->grants[$actor]['revoked_at']=$f[1]->now(); } elseif ($case==='inactive_user') { $m->users[$actor]['status']='inactive'; } else { $m->protocols[1]['treatment_system_id']=2; }
            $before=unchangedRows($m); check(controllerPost($c,$method,operationsInput($f))===null && http_response_code()===403,'Denied command not 403'); check(unchangedRows($m)===$before,'Unauthorized mutation');
            if ($case==='editor' || $case==='wrong_capability') { $d=$e->operationsDetail(1); check(!$d[$method==='withdraw' ? 'canWithdraw' : 'canReset'],'Unauthorized control visible'); }
        });
    }
    foreach ([null,'bad',[]] as $token) {
        test('4B3 '.$method.' CSRF rejection '.json_encode($token),function () use ($method,$state,$token) { [$f,,,,,$c]=operationsFixture($state); $input=operationsInput($f,['_csrf_token'=>$token]); if ($token===null) { unset($input['_csrf_token']); } $before=unchangedRows($f[0]); controllerPost($c,$method,$input); check(http_response_code()===403 && unchangedRows($f[0])===$before,'CSRF bypass'); });
    }
    foreach (['GET','PUT','DELETE'] as $verb) { test('4B3 '.$method.' rejects HTTP '.$verb,function () use ($method,$state,$verb) { [$f,,,,,$c]=operationsFixture($state); $_POST=operationsInput($f); $_SERVER['REQUEST_METHOD']=$verb; $before=unchangedRows($f[0]); $c->$method('1'); check(http_response_code()===422 && unchangedRows($f[0])===$before,'Verb bypass'); }); }
    foreach (['user_id','actor_id','treatment_system_id','can_publish','can_review','publication_status','approved_review_id','published_locales','content_revision'] as $field) { test('4B3 '.$method.' rejects forged '.$field,function () use ($method,$state,$field) { [$f,,,,,$c]=operationsFixture($state); $before=unchangedRows($f[0]); controllerPost($c,$method,operationsInput($f,[$field=>'1'])); check(http_response_code()===422 && unchangedRows($f[0])===$before,'Mass assignment'); }); }
    foreach ([null,' ',[],"\xFF",str_repeat('x',65536)] as $reason) { test('4B3 '.$method.' validates reason '.gettype($reason).' '.strlen(serialize($reason)),function () use ($method,$state,$reason) { [$f,,,,,$c]=operationsFixture($state); $before=unchangedRows($f[0]); controllerPost($c,$method,operationsInput($f,['reason'=>$reason])); check(http_response_code()===422 && unchangedRows($f[0])===$before,'Invalid reason wrote'); }); }
    foreach (['expected_revision'=>'2','expected_state'=>'draft'] as $field=>$value) { test('4B3 '.$method.' safe stale '.$field,function () use ($method,$state,$field,$value) { [$f,,,,,$c]=operationsFixture($state); $before=unchangedRows($f[0]); controllerPost($c,$method,operationsInput($f,[$field=>$value])); check(http_response_code()===409 && unchangedRows($f[0])===$before && str_contains($c->rendered['data']['error'],'Reload and retry'),'Stale command accepted'); }); }
    foreach (['user','active','lockUsers'] as $stage) { test('4B3 '.$method.' permission infrastructure '.$stage.' is safe 503',function () use ($method,$state,$stage) { [$f,,$permissions,,,$c]=operationsFixture($state); $permissions->stage=$stage; $permissions->failure=new RuntimeException('PRIVATE_SQL_PASSWORD_PATH'); $before=unchangedRows($f[0]); controllerPost($c,$method,operationsInput($f)); check(http_response_code()===503 && unchangedRows($f[0])===$before && !str_contains(serialize($c->rendered),'PRIVATE_') && !str_contains(serialize(App\Core\Logger::$messages),'PRIVATE_'),'Unsafe permission failure'); }); }
    foreach (['update','event'] as $stage) { test('4B3 '.$method.' transactional '.$stage.' failure rolls back',function () use ($method,$state,$stage) { [$f,$repo,,,,$c]=operationsFixture($state); $repo->stage=$stage; $repo->failure=new PDOException('PRIVATE_SQL_PASSWORD_PATH'); $before=unchangedRows($f[0]); controllerPost($c,$method,operationsInput($f)); check(http_response_code()===503 && unchangedRows($f[0])===$before && !str_contains(serialize($c->rendered),'PRIVATE_'),'Partial operation persisted'); }); }
    test('4B3 '.$method.' unexpected infrastructure failure is safe 500',function () use ($method,$state) { [$f,$repo,,,,$c]=operationsFixture($state); $repo->failure=new LogicException('PRIVATE_STACK_PATH'); $repo->stage='update'; $before=unchangedRows($f[0]); controllerPost($c,$method,operationsInput($f)); check(http_response_code()===500 && unchangedRows($f[0])===$before && !str_contains(serialize($c->rendered),'PRIVATE_'),'Unsafe unexpected failure'); });
    test('4B3 '.$method.' repeated submission is stale, fresh invalid state is 422',function () use ($method,$state) { [$f,,,,,$c]=operationsFixture($state); $input=operationsInput($f); controllerPost($c,$method,$input); $before=unchangedRows($f[0]); controllerPost($c,$method,$input); check(http_response_code()===409 && unchangedRows($f[0])===$before,'Repeated command wrote'); if ($method==='resetReview') { controllerPost($c,$method,operationsInput($f)); } controllerPost($c,$method,operationsInput($f)); check(http_response_code()===422,'Fresh invalid operation accepted'); });
}
foreach (['operations'=>[],'operation'=>['1'],'withdraw'=>['1'],'resetReview'=>['1']] as $method=>$ids) { test('4B3 '.$method.' redirects anonymous users without data',function () use ($method,$ids) { [$f,,,,,$c]=operationsFixture(); $f[0]->actor=0; try { $c->$method(...$ids); throw new LogicException('Anonymous access'); } catch (EditorialRedirect $r) { check($r->target==='/login' && !$c->rendered,'Anonymous data'); } }); }
foreach (['withdraw'=>'published','resetReview'=>'approved','operation'=>'approved'] as $method=>$state) {
    foreach (['0','01','1e0','999','2'] as $id) {
        test('4B3 '.$method.' forged protocol ID '.$id,function () use ($method,$state,$id) {
            [$f,,,,,$c]=operationsFixture($state); $f[0]->protocols[2]=array_replace($f[0]->protocols[1],['id'=>2,'treatment_system_id'=>2]); $before=unchangedRows($f[0]);
            if ($method==='operation') { $c->operation($id); } else { controllerPost($c,$method,operationsInput($f),[$id]); }
            check(http_response_code()===(in_array($id,['999','2'],true) ? 403 : 422) && unchangedRows($f[0])===$before && !isset($c->rendered['data']['protocol']),'Forged protocol access/write');
        });
    }
}
test('4B3 stale approval HTTP after reset is safe 409 and fresh independent approval succeeds',function () {
    [$f,,,,$e]=operationsFixture('approved'); $e->resetProtocolReview(1,operationsInput($f));
    $c=new ReviewControllerFixture($e,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]); $before=unchangedRows($f[0]);
    reviewPost($c,'approve',approvalInput()); check(http_response_code()===409 && unchangedRows($f[0])===$before,'Reset reused previous submission');
    $e->approveReview(1,approvalInput(['submission_id'=>'4'])); check($f[0]->protocols[1]['approved_review_id']===5 && $f[0]->protocols[1]['publication_status']==='unpublished','Fresh approval missing or automatic publication');
});
foreach (['operations','operation'] as $method) { test('4B3 '.$method.' permission read failure remains safe 503',function () use ($method) { [$f,,$permissions,,,$c]=operationsFixture(); $permissions->failure=new PDOException('PRIVATE_PERMISSION_SQL'); $method==='operations' ? $c->operations() : $c->operation('1'); check(http_response_code()===503 && !str_contains(serialize($c->rendered),'PRIVATE_'),'Unsafe read authorization'); }); }
foreach (['draft','pending_review','approved','changes_required','withdrawn'] as $state) { test('4B3 withdrawal rejects '.$state,function () use ($state) { [$f,,,,$e,$c]=operationsFixture($state); $f[0]->actor=3; $before=unchangedRows($f[0]); check(!$e->operationsDetail(1)['canWithdraw'],'Invalid withdrawal form'); controllerPost($c,'withdraw',operationsInput($f)); check(http_response_code()===422 && unchangedRows($f[0])===$before,'Invalid withdrawal'); }); }
foreach (['draft','changes_required','withdrawn','published'] as $state) { test('4B3 reset rejects '.$state,function () use ($state) { [$f,,,,$e,$c]=operationsFixture($state); $f[0]->actor=2; $before=unchangedRows($f[0]); check(!$e->operationsDetail(1)['canReset'],'Invalid reset form'); controllerPost($c,'resetReview',operationsInput($f)); check(http_response_code()===422 && unchangedRows($f[0])===$before,'Invalid reset'); }); }
test('4B3 reset pending review to draft same revision with immutable submission',function () { [$f,,,,$e,$c]=operationsFixture('pending_review'); $old=$f[0]->events; check($e->operationsDetail(1)['canReset'],'Reviewer cannot reset'); check(controllerPost($c,'resetReview',operationsInput($f))==='/admin/treatments/1/operations?reset=1','Reset redirect'); $p=$f[0]->protocols[1]; $ev=end($f[0]->events); check($p['review_state']==='draft' && $p['publication_status']==='unpublished' && $p['content_revision']===1 && $p['approved_review_id']===null && $p['published_locales']==='' && $ev['event_type']==='review_reset' && $ev['actor_id']===2 && $ev['notes']==='Synthetic governance reason' && count($f[0]->events)===2,'Pending reset state/history'); preservedHistory($old,$f[0]); });
test('4B3 reset approved creates fresh pending submission at same revision and clears approval',function () {
    [$f,,,,$e]=operationsFixture('approved'); $old=$f[0]->events; $e->resetProtocolReview(1,operationsInput($f)); $p=$f[0]->protocols[1];
    check($p['review_state']==='pending_review' && $p['publication_status']==='unpublished' && $p['content_revision']===1 && $p['approved_review_id']===null && $p['published_locales']==='' && count($f[0]->events)===4,'Approved reset state'); preservedHistory($old,$f[0]);
    check($f[0]->events[3]['event_type']==='review_reset' && $f[0]->events[3]['actor_id']===2 && $f[0]->events[3]['notes']==='Synthetic governance reason','Reset event');
    $submission=$f[0]->events[4]; check($submission['event_type']==='submitted' && $submission['content_revision']===1 && $submission['locales']==='en,hi' && $submission['actor_id']===2 && ($submission['review_due_at']??null)===null && $submission['independence_confirmed']===0,'False fresh approval');
    $captured=$f[2]->capture($f[5]->aggregate(1),['en','hi']); check(hash('sha256',$submission['content_snapshot'])===$submission['content_hash'] && $captured['content_hash']===$submission['content_hash'] && $captured['content_snapshot']===$submission['content_snapshot'],'Reset snapshot/hash mismatch');
});
test('4B3 reset second submitted-event failure rolls back approval and both appended events',function () { [$f,$repo,,,,$c]=operationsFixture('approved'); $repo->failure=new PDOException('PRIVATE_EVENT'); $repo->failEvent=2; $before=unchangedRows($f[0]); controllerPost($c,'resetReview',operationsInput($f)); check(http_response_code()===503 && unchangedRows($f[0])===$before,'Partial reset persisted'); });
test('4B3 reset requires recorded reviewer qualification',function () { [$f,,,,$e]=operationsFixture('approved'); $f[0]->grants[2]['qualification_scope']=' '; $before=unchangedRows($f[0]); rejects(fn()=>$e->resetProtocolReview(1,operationsInput($f))); rejects(fn()=>$e->operationsDetail(1)); check(unchangedRows($f[0])===$before,'Unqualified reset'); });
test('4B3 reset cannot bypass independent approval and supersedes stale submission',function () {
    [$f,,,$g,$e]=operationsFixture('approved'); $e->resetProtocolReview(1,operationsInput($f)); $before=unchangedRows($f[0]); rejects(fn()=>$e->approveReview(1,approvalInput())); check(unchangedRows($f[0])===$before,'Old submission approved');
    $f[0]->grants[1]['can_review']=1; $f[0]->grants[1]['qualification_scope']='Synthetic contributor scope'; $f[0]->actor=1; $g->edit(1,1,'pending_review',['overview_en'=>'Material contributor edit']); $e->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'2','expected_state'=>'draft','locales'=>['en','hi']]);
    $f[0]->actor=2; $g->approve(1,2,6,['en','hi'],'Independent fresh assessment','2099-01-01 00:00:00.000000',true);
    $f[0]->actor=1; $e->resetProtocolReview(1,operationsInput($f)); $before=unchangedRows($f[0]); rejects(fn()=>$g->approve(1,2,9,['en','hi'],'Self review forbidden','2099-01-01 00:00:00.000000',true)); check(unchangedRows($f[0])===$before,'Reset bypassed contributor independence');
});
test('4B3 withdrawn edit, new revision, fresh submission and independent approval remain required',function () {
    [$f,,,$g,$e]=operationsFixture(); $e->withdrawProtocol(1,operationsInput($f)); $old=$f[0]->events; rejects(fn()=>$g->publish(1,1,['en']));
    $f[0]->actor=1; check(!$e->save(1,draftInput()),'No-op edit changed state'); rejects(fn()=>$e->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'draft','locales'=>['en']]));
    $e->save(1,draftInput(['overview_en'=>'New governed post-withdrawal content'])); $p=$f[0]->protocols[1]; check($p['content_revision']===2 && $p['review_state']==='draft' && $p['publication_status']==='unpublished' && $p['approved_review_id']===null,'Withdrawal edit lifecycle');
    $e->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'2','expected_state'=>'draft','locales'=>['en','hi']]); $f[0]->actor=2; $e->approveReview(1,approvalInput(['expected_revision'=>'2','submission_id'=>'6']));
    check($f[0]->protocols[1]['review_state']==='approved' && $f[0]->protocols[1]['publication_status']==='unpublished' && $f[0]->protocols[1]['approved_review_id']===7,'Fresh review unexpectedly published'); preservedHistory($old,$f[0]);
});
foreach ([1,2,3] as $actor) { test('4B3 scoped history access actor '.$actor,function () use ($actor) { [$f,,,,$e,$c]=operationsFixture(); $f[0]->actor=$actor; $before=unchangedRows($f[0]); $c->operation('1'); check($c->rendered['view']==='admin/treatments/operation' && count($c->rendered['data']['history'])===3 && unchangedRows($f[0])===$before,'History access or write'); $d=$e->operationsDetail(1); foreach ($d['history'] as $ev) { check(!isset($ev['content_snapshot']) && !isset($ev['content_hash']) && !isset($ev['protocol_id']),'Raw history projection'); } }); }
foreach (['no_grant','revoked','inactive_user','wrong_scope','missing_protocol'] as $case) { test('4B3 history rejects '.$case,function () use ($case) { [$f,,,,$e,$c]=operationsFixture(); if ($case==='no_grant') { $f[0]->actor=4; } elseif ($case==='revoked') { $f[0]->grants[3]['revoked_at']=$f[1]->now(); } elseif ($case==='inactive_user') { $f[0]->users[3]['status']='inactive'; } elseif ($case==='wrong_scope') { $f[0]->protocols[1]['treatment_system_id']=2; } else { unset($f[0]->protocols[1]); } $c->operation('1'); check(http_response_code()===403 && !isset($c->rendered['data']['history']),'History IDOR'); }); }
test('4B3 history escapes rationale and labels UTC, actor, due date, independence',function () { [$f,,,,$e]=operationsFixture(); $f[0]->events[2]['notes']='<script>private</script>'; $html=renderEditorial('operation',$e->operationsDetail(1)); check(!str_contains($html,'<script>') && str_contains($html,'&lt;script&gt;') && str_contains($html,'UTC') && str_contains($html,'Actor ID') && str_contains($html,'2099-01-01') && str_contains($html,'Confirmed'),'Unsafe history rendering'); });
test('4B3 history keyset pagination, descending order and protocol isolation',function () { [$f,,,,$e]=operationsFixture('draft'); $f[0]->actor=1; for ($i=1;$i<=56;++$i) { $f[0]->events[$i]=['id'=>$i,'protocol_id'=>$i===56 ? 2 : 1,'event_type'=>'content_changed','content_revision'=>1,'actor_id'=>1,'occurred_at'=>$f[1]->now(),'locales'=>'','notes'=>'Synthetic history','review_due_at'=>null,'independence_confirmed'=>0,'content_snapshot'=>'PRIVATE_SNAPSHOT','content_hash'=>'PRIVATE_HASH']; } $before=unchangedRows($f[0]); $d=$e->operationsDetail(1); check(count($d['history'])===50 && $d['moreHistory'] && array_column($d['history'],'id')===range(55,6),'First history page'); $next=$e->operationsDetail(1,['before_event'=>'6']); check(count($next['history'])===5 && !$next['moreHistory'] && array_column($next['history'],'id')===range(5,1),'Second history page'); check(str_contains(renderEditorial('operation',$d),'before_event=6') && !str_contains(renderEditorial('operation',$d),'PRIVATE_') && unchangedRows($f[0])===$before,'History pagination leaked or mutated'); rejects(fn()=>$e->operationsDetail(1,['before_event'=>'0'])); rejects(fn()=>$e->operationsDetail(1,['event_type'=>'approved'])); });
test('4B3 operations list scope, filters, publisher pagination and empty results',function () { [$f,,,,$e]=operationsFixture('draft'); $f[0]->actor=3; $f[0]->grants[3]['can_edit']=0; for ($i=2;$i<=53;++$i) { $f[0]->protocols[$i]=array_replace($f[0]->protocols[1],['id'=>$i,'treatment_system_id'=>$i===53 ? 2 : 1]); } $d=$e->operationsListing(['publication_status'=>'unpublished','review_state'=>'draft']); check(count($d['protocols'])===50 && $d['more'],'Unbounded listing'); $next=$e->operationsListing(['page'=>'2']); check(array_column($next['protocols'],'id')===[51,52] && !$next['more'],'Scope or pagination'); check($e->operationsListing(['disease_id'=>'999'])['protocols']===[],'Wrong disease'); $html=renderEditorial('operations',$d); check(str_contains($html,'Next page') && !str_contains($html,'/publish'),'List rendering'); foreach ([['treatment_system_id'=>'2'],['review_state'=>'INVALID'],['publication_status'=>'INVALID'],['page'=>'0'],['page'=>'100001'],['actor_id'=>'3']] as $input) { rejects(fn()=>$e->operationsListing($input)); } });
foreach (['operations'=>'list','operation'=>'history'] as $method=>$stage) { test('4B3 '.$method.' read storage failure is safe 503',function () use ($method,$stage) { [$f,$repo,,,,$c]=operationsFixture(); $repo->stage=$stage; $repo->failure=new PDOException('PRIVATE_HISTORY_SQL'); $before=unchangedRows($f[0]); $method==='operations' ? $c->operations() : $c->operation('1'); check(http_response_code()===503 && !str_contains(serialize($c->rendered),'PRIVATE_') && unchangedRows($f[0])===$before,'Read error leaked/wrote'); }); }

function operationsPublicReader(array $f,string $mode): App\Services\TreatmentPublicationService {
    $repo=new class($f) extends App\Repositories\TreatmentPublicationRepository {
        public function __construct(private array $f) {}
        public function candidates(int $diseaseId,?int $protocolId=null): array { $p=$this->f[0]->protocols[1]; return [$this->f[5]->aggregate(1)+['disease'=>['id'=>1],'system'=>['id'=>1,'is_active'=>1,'name_en'=>'System','name_hi'=>'प्रणाली','slug'=>'synthetic'],'approval'=>$p['approved_review_id'] ? $this->f[0]->events[$p['approved_review_id']] : null,'unresolved_invalidation'=>0]]; }
    };
    $policy=new class($mode) extends App\Services\TreatmentGovernancePolicy { public function __construct(private string $testMode) {} public function mode(mixed $settings=null): string { return $this->testMode; } };
    return new App\Services\TreatmentPublicationService($repo,$f[1],$f[2],$policy);
}
test('4B3 withdrawal canonical DENY and empty governed comparison; no legacy detail fixture',function () {
    [$f,,,,$e]=operationsFixture(); $reader=operationsPublicReader($f,'governed'); check($reader->assessProtocolForPublicRead(1,1)['status']==='CANDIDATE' && count($reader->getPublicTreatmentsForDisease(1))===1,'Positive public control invalid');
    $e->withdrawProtocol(1,operationsInput($f));
    foreach (['governed','hold'] as $mode) { $reader=operationsPublicReader($f,$mode); check($reader->assessProtocolForPublicRead(1,1)['status']==='DENY' && $reader->getPublicTreatmentProjection(1,1)===null && $reader->getPublicTreatmentsForDisease(1)===[],'Withdrawn exposure'); $reflection=new ReflectionClass(App\Services\DiseaseService::class); $disease=$reflection->newInstanceWithoutConstructor(); $reflection->getProperty('diseaseReferenceService')->setValue($disease,new App\Services\DiseaseReferenceService()); $reflection->getProperty('repository')->setValue($disease,new class extends App\Repositories\DiseaseRepository { public function __construct() {} public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array { return []; } public function findKnowledgeBySlug(string $slug,string $language='en'): ?array { return ['disease'=>['id'=>1],'symptoms'=>[['id'=>1,'symptom_en'=>'Preserved']],'causes'=>[],'treatments'=>[['legacy'=>'must disappear']]]; } }); $reflection->getProperty('treatmentPublicationService')->setValue($disease,$reader); $reflection->getProperty('comparisonBuilder')->setValue($disease,new App\Algorithms\DiseaseComparisonBuilder()); check($disease->getKnowledgeBySlug('fixture')['treatments']===[] && $disease->getKnowledgeBySlug('fixture')['symptoms']===[['id'=>1,'symptom_en'=>'Preserved']],'Disease exposure'); $comparison=$disease->getComparison('left','right'); check($comparison['left']['treatments']===[] && $comparison['right']['treatments']===[],'Comparison exposure'); }
});
test('4B3 reset canonical DENY and HOLD blocks fully eligible published synthetic control',function () { [$f,,,,$e]=operationsFixture('approved'); $e->resetProtocolReview(1,operationsInput($f)); $reader=operationsPublicReader($f,'governed'); check($reader->assessProtocolForPublicRead(1,1)['status']==='DENY' && $reader->getPublicTreatmentProjection(1,1)===null,'Reset public exposure'); [$f]=operationsFixture(); $reader=operationsPublicReader($f,'hold'); check($reader->assessProtocolForPublicRead(1,1)['status']==='CANDIDATE' && $reader->getPublicTreatmentsForDisease(1)===[],'HOLD bypass'); });
test('4B3 operations repository SQL binds scope/filter values and bounds history/list',function () {
    $repo=new class extends App\Repositories\TreatmentGovernanceRepository { public array $queries=[]; public function __construct() {} protected function fetchAll(string $sql,array $params=[]): array { $this->queries[]=[$sql,$params]; return []; } };
    $repo->operationsList(3,['publication_status'=>'withdrawn','treatment_system_id'=>1,'review_state'=>'draft','disease_id'=>1],50); [$sql,$params]=$repo->queries[0]; foreach (["g.user_id=:actor","g.revoked_at IS NULL","g.can_edit=1 OR g.can_review=1 OR g.can_publish=1","u.status='active'",'LIMIT 51 OFFSET 50','p.`publication_status`=:publication_status'] as $part) { check(str_contains($sql,$part),'Missing scoped SQL '.$part); } check($params===['actor'=>3,'treatment_system_id'=>1,'disease_id'=>1,'review_state'=>'draft','publication_status'=>'withdrawn'],'Unbound filter');
    $repo->history(1,9); [$sql,$params]=$repo->queries[1]; check(str_contains($sql,'protocol_id=:id AND id<:before_id') && str_contains($sql,'ORDER BY id DESC LIMIT 51') && !str_contains($sql,'content_snapshot') && !str_contains($sql,'content_hash') && $params===['id'=>1,'before_id'=>9],'Unsafe history SQL');
});
test('4B3 exact internal operation routes, POST-only mutations, no publish/history-write routes',function () {
    global $testConfiguration; $testConfiguration['base_path']='';
    $loader=new class { public App\Core\Router $router; public function __construct() { $this->router=new App\Core\Router(); } public function router(): App\Core\Router { return $this->router; } public function load(): void { require dirname(__DIR__).'/routes/web.php'; } }; $loader->load(); $routes=(new ReflectionProperty(App\Core\Router::class,'routes'))->getValue($loader->router); $expected=['operations'=>['GET','/admin/treatments/operations'],'operation'=>['GET','/admin/treatments/{protocolId}/operations'],'withdraw'=>['POST','/admin/treatments/{protocolId}/withdraw'],'resetReview'=>['POST','/admin/treatments/{protocolId}/reset-review']]; $seen=[];
    foreach ($routes as $verb=>$entries) { foreach ($entries as $r) { if ($r['controller']!==App\Controllers\TreatmentAdminController::class) { continue; } check(!in_array($r['method'],['publish','updateEvent','deleteEvent'],true),'Unsafe operation route'); if (isset($expected[$r['method']])) { check([$verb,$r['uri']]===$expected[$r['method']],'Wrong route or verb'); $seen[]=$r['method']; } } } check(count($seen)===4 && count(array_unique($seen))===4,'Missing/duplicate operations route');
});
test('4B3 operations views use CSRF, escaping and server-rendered confirmation without inline JS',function () { foreach (['operations','operation','_history'] as $view) { $text=file_get_contents(dirname(__DIR__).'/app/Views/admin/treatments/'.$view.'.php'); check(!preg_match('/<script|\bon\w+\s*=|javascript:/i',$text),'Inline JS'); if ($view==='operation') { check(substr_count($text,'method="post"')===2 && substr_count($text,'csrfField()')===2 && substr_count($text,'name="expected_revision"')===2 && substr_count($text,'name="expected_state"')===2 && !str_contains($text,'/publish'),'Unprotected operations form'); } } });

$operationsPassed=$passed-$operationsBaseline;
echo "Treatment operations: $operationsPassed focused tests passed; $operationsBaseline earlier checks retained; no database/session/log writes.\n";
ob_end_flush();

<?php

declare(strict_types=1);

// Database-free Phase 2C-3 checks, including the unchanged earlier suites.
ob_start();
require __DIR__.'/treatment-editorial.php';
$earlierPassed=$passed;

final class ReviewControllerFixture extends App\Controllers\TreatmentReviewController
{
    public array $rendered=[];
    protected function view(string $view,array $data=[]): void { $this->rendered=['view'=>$view,'data'=>$data]; }
}
function reviewFixture(bool $submit=true): array {
    [$f,$editor]=editorialFixture();
    if ($submit) { $editor->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'draft','locales'=>['en','hi']]); }
    $f[0]->actor=2;
    $c=new ReviewControllerFixture($editor,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]);
    return [$f,$editor,$c];
}
function approvalInput(array $replace=[]): array { return array_replace(['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'pending_review','submission_id'=>'1','locales'=>['en','hi'],'rationale'=>'Independent assessment of governed content and explicit references','review_due_at'=>'2099-01-01T00:00','independence_confirmed'=>'1','risk_eligibility'=>'eligible'],$replace); }
function changesInput(array $replace=[]): array { return array_replace(['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'pending_review','submission_id'=>'1','rationale'=>'Please correct the documented limitation'],$replace); }
function reviewPost(ReviewControllerFixture $c,string $method,array $input,string $id='1'): ?string {
    $_POST=$input; $_SERVER['REQUEST_METHOD']='POST';
    try { $c->$method($id); return null; } catch (EditorialRedirect $redirect) { return $redirect->target; }
}
function renderReview(string $name,array $data): string { return (function () use ($name,$data) { extract($data,EXTR_SKIP); ob_start(); require dirname(__DIR__).'/app/Views/admin/treatment-review/'.$name.'.php'; return ob_get_clean(); })(); }
function approvalCount(GovernanceMemory $m): int { return count(array_filter($m->events,fn($e)=>$e['event_type']==='approved')); }

test('2C3 all review routes require authentication',function () {
    foreach (['index'=>[],'show'=>['1'],'approve'=>['1'],'changesRequired'=>['1']] as $method=>$ids) { [$f,,$c]=reviewFixture(); $f[0]->actor=0; try { $c->$method(...$ids); throw new LogicException('Anonymous reviewer accepted'); } catch (EditorialRedirect $r) { check($r->target==='/login' && !$c->rendered,'Anonymous review data leaked'); } }
});
test('2C3 editor without review capability cannot list inspect or decide',function () { [$f,$s]=reviewFixture(); $f[0]->actor=1; rejects(fn()=>$s->reviewListing([])); rejects(fn()=>$s->reviewDetail(1)); rejects(fn()=>$s->approveReview(1,approvalInput())); rejects(fn()=>$s->requestReviewChanges(1,changesInput())); });
test('2C3 reviewer scope cannot access another system',function () { [$f,$s,$c]=reviewFixture(); $f[0]->protocols[2]=array_replace($f[0]->protocols[1],['id'=>2,'treatment_system_id'=>2,'title_en'=>'Restricted review title']); $c->show('2'); check(http_response_code()===403 && !str_contains(serialize($c->rendered),'Restricted review title'),'Review IDOR leaked'); rejects(fn()=>$s->approveReview(2,approvalInput())); rejects(fn()=>$s->reviewListing(['treatment_system_id'=>'2'])); });
test('2C3 revoked reviewer loses access and decisions',function () { [$f,$s]=reviewFixture(); $f[0]->grants[2]['revoked_at']='2026-01-01 00:00:00.000000'; rejects(fn()=>$s->reviewDetail(1)); rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 review permission without qualification fails closed',function () { [$f,$s]=reviewFixture(); $f[0]->grants[2]['qualification_scope']=' '; rejects(fn()=>$s->reviewDetail(1)); rejects(fn()=>$s->approveReview(1,approvalInput())); });
foreach (['actor_id','reviewer_id','user_id','treatment_system_id','can_review','qualification_scope'] as $field) {
    test('2C3 posted authorization field rejected '.$field,function () use ($field) { [$f,$s]=reviewFixture(); $before=serialize($f[0]); rejects(fn()=>$s->approveReview(1,approvalInput([$field=>'1']))); rejects(fn()=>$s->requestReviewChanges(1,changesInput([$field=>'1']))); check(serialize($f[0])===$before,'Identity injection wrote'); });
}
test('2C3 materially authored reviewer cannot approve despite attestation',function () {
    [$f,$s]=reviewFixture(false); $f[0]->actor=1; $s->save(1,draftInput(['overview_en'=>'Materially authored'])); $s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'2','expected_state'=>'draft','locales'=>['en','hi']]);
    $f[7]->insert(['user_id'=>1,'treatment_system_id'=>1,'can_edit'=>1,'can_review'=>1,'can_publish'=>0,'qualification_scope'=>'Qualified fixture']);
    $detail=$s->reviewDetail(1); check($detail['assessment']['selfAuthored'] && !$detail['assessment']['canApprove'],'Self-authorship not surfaced');
    rejects(fn()=>$s->approveReview(1,approvalInput(['expected_revision'=>'2','submission_id'=>'2']))); check(approvalCount($f[0])===0,'Self-review event written');
});
foreach ([null,'0','on','true',1,[]] as $value) { test('2C3 missing or unchecked independence rejected '.json_encode($value),function () use ($value) { [$f,$s]=reviewFixture(); $input=approvalInput(['independence_confirmed'=>$value]); if ($value===null) { unset($input['independence_confirmed']); } rejects(fn()=>$s->approveReview(1,$input)); }); }
test('2C3 legacy unknown authorship requires explicit attestation and records it',function () { [$f,$s]=reviewFixture(); check($f[5]->contributors(1)===[],'Fixture has material author'); $s->approveReview(1,approvalInput()); $event=$f[0]->events[$f[0]->protocols[1]['approved_review_id']]; check($event['independence_confirmed']===1 && $event['actor_id']===2,'Approval attestation/identity missing'); });
test('2C3 draft cannot approve directly',function () { [$f,$s]=reviewFixture(false); rejects(fn()=>$s->approveReview(1,approvalInput(['expected_state'=>'draft']))); rejects(fn()=>$f[4]->approve(1,1,1,['en'],'Reason','2099-01-01 00:00:00.000000',true)); });
test('2C3 matching revision and exact snapshot approves once',function () { [$f,$s]=reviewFixture(); $before=serialize([$f[0]->items,$f[0]->sources,$f[0]->protocolRefs,$f[0]->itemRefs]); $s->approveReview(1,approvalInput()); $p=$f[0]->protocols[1]; check($p['review_state']==='approved' && $p['publication_status']==='unpublished' && $p['published_locales']==='' && $p['content_revision']===1 && $p['approved_review_id']===2,'Approval state/pointer/revision incorrect'); check(approvalCount($f[0])===1 && serialize([$f[0]->items,$f[0]->sources,$f[0]->protocolRefs,$f[0]->itemRefs])===$before,'Approval rewrote governed content'); $e=$f[0]->events[2]; check($e['content_snapshot']!==null && $e['content_hash']===hash('sha256',$e['content_snapshot']) && $e['notes']===approvalInput()['rationale'],'Approval snapshot/rationale missing'); });
test('2C3 approved cannot be approved again or overwritten by second reviewer',function () { [$f,$s]=reviewFixture(); $s->approveReview(1,approvalInput()); $before=serialize($f[0]); rejects(fn()=>$s->approveReview(1,approvalInput())); check(serialize($f[0])===$before && approvalCount($f[0])===1,'Duplicate approval committed'); });
test('2C3 changes required creates one event and preserves content revision/publication',function () { [$f,$s]=reviewFixture(); $before=serialize([$f[0]->items,$f[0]->sources,$f[0]->protocolRefs,$f[0]->itemRefs]); $s->requestReviewChanges(1,changesInput()); $p=$f[0]->protocols[1]; check($p['review_state']==='changes_required' && $p['content_revision']===1 && $p['publication_status']==='unpublished' && $p['approved_review_id']===null,'Changes state invalid'); check(count($f[0]->events)===2 && $f[0]->events[2]['event_type']==='changes_required' && $f[0]->events[2]['notes']===changesInput()['rationale'] && serialize([$f[0]->items,$f[0]->sources,$f[0]->protocolRefs,$f[0]->itemRefs])===$before,'Decision rewrote content/history'); });
test('2C3 changes required needs a corrected revision and fresh submission',function () {
    [$f,$s]=reviewFixture(); $s->requestReviewChanges(1,changesInput()); rejects(fn()=>$s->approveReview(1,approvalInput(['expected_state'=>'changes_required'])));
    $f[0]->actor=1; rejects(fn()=>$s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'changes_required','locales'=>['en','hi']]));
    $s->save(1,draftInput(['limitations_en'=>'Corrected limitation'],[],1,'changes_required'));
    $s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'2','expected_state'=>'changes_required','locales'=>['en','hi']]); $f[0]->actor=2;
    rejects(fn()=>$s->approveReview(1,approvalInput())); $s->approveReview(1,approvalInput(['expected_revision'=>'2','submission_id'=>'4'])); check($f[0]->protocols[1]['content_revision']===2 && $f[0]->protocols[1]['review_state']==='approved','Fresh corrected review failed');
});
test('2C3 stale expected state rejected',function () { [$f,$s]=reviewFixture(); rejects(fn()=>$s->approveReview(1,approvalInput(['expected_state'=>'approved']))); rejects(fn()=>$s->requestReviewChanges(1,changesInput(['expected_state'=>'draft']))); });

foreach (['title_en','overview_en','applicability_en','limitations_en','content_purpose'] as $field) { test('2C3 missing protocol prerequisite '.$field,function () use ($field) { [$f,$s]=reviewFixture(); $f[0]->protocols[1][$field]=null; rejects(fn()=>$s->approveReview(1,approvalInput())); check(approvalCount($f[0])===0,'Missing prerequisite approved'); }); }
foreach (['not_assessed','insufficient_evidence','evidence_does_not_support','unknown',''] as $value) { test('2C3 protocol evidence policy rejects '.$value,function () use ($value) { [$f,$s]=reviewFixture(); $f[0]->protocols[1]['evidence_classification']=$value; rejects(fn()=>$s->approveReview(1,approvalInput())); }); }
test('2C3 no included item rejects approval',function () { [$f,$s]=reviewFixture(); $f[0]->items[1]['is_included']=0; rejects(fn()=>$s->approveReview(1,approvalInput())); });
foreach (['title_en','instructions_en','limitations_en','content_purpose','item_type'] as $field) { test('2C3 included item prerequisite '.$field,function () use ($field) { [$f,$s]=reviewFixture(); $f[0]->items[1][$field]=null; rejects(fn()=>$s->approveReview(1,approvalInput())); }); }
foreach (['not_assessed','insufficient_evidence','evidence_does_not_support'] as $value) { test('2C3 included item evidence policy rejects '.$value,function () use ($value) { [$f,$s]=reviewFixture(); $f[0]->items[1]['evidence_classification']=$value; rejects(fn()=>$s->approveReview(1,approvalInput())); }); }
test('2C3 missing explicit item reference rejects',function () { [$f,$s]=reviewFixture(); $f[0]->itemRefs=[]; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 missing explicit protocol reference rejects',function () { [$f,$s]=reviewFixture(); $f[0]->protocolRefs=[]; rejects(fn()=>$s->approveReview(1,approvalInput())); });
foreach (['unverified','withdrawn'] as $status) { test('2C3 current required source status rejects '.$status,function () use ($status) { [$f,$s]=reviewFixture(); $f[0]->sources[1]['status']=$status; $before=serialize($f[0]); rejects(fn()=>$s->approveReview(1,approvalInput())); check(serialize($f[0])===$before,'Approval silently verified source'); }); }
test('2C3 invalid source relationship rejects approval',function () { [$f,$s]=reviewFixture(); $f[0]->itemRefs[0]['source_id']=99; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 empty claim scope rejects approval',function () { [$f,$s]=reviewFixture(); $f[0]->protocolRefs[0]['claim_scope']=' '; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 inactive system cannot approve',function () { [$f,$s]=reviewFixture(); $f[0]->systems[1]['is_active']=0; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 nonexistent disease cannot approve',function () { [$f,$s]=reviewFixture(); $f[0]->protocols[1]['disease_id']=999; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 incoherent approval pointer cannot approve',function () { [$f,$s]=reviewFixture(); $f[0]->protocols[1]['approved_review_id']=99; rejects(fn()=>$s->approveReview(1,approvalInput())); });
foreach ([null,'','invalid','2025-02-30T12:00','2000-01-01T00:00','2099-01-01T00:00+05:30'] as $due) { test('2C3 invalid missing past or zoned due rejected '.json_encode($due),function () use ($due) { [$f,$s]=reviewFixture(); $input=approvalInput(['review_due_at'=>$due]); if ($due===null) { unset($input['review_due_at']); } rejects(fn()=>$s->approveReview(1,$input)); }); }
test('2C3 future review timestamp stored in UTC without invented maximum',function () { [$f,$s]=reviewFixture(); $s->approveReview(1,approvalInput(['review_due_at'=>'2099-12-01T14:30'])); check($f[0]->events[2]['review_due_at']==='2099-12-01 14:30:00.000000','UTC date conversion incorrect'); });
foreach (['',' '] as $reason) { test('2C3 rationale required for both decisions '.json_encode($reason),function () use ($reason) { [$f,$s]=reviewFixture(); rejects(fn()=>$s->approveReview(1,approvalInput(['rationale'=>$reason]))); rejects(fn()=>$s->requestReviewChanges(1,changesInput(['rationale'=>$reason]))); }); }

foreach (['title_en','overview_hi','evidence_summary_en','limitations_hi'] as $field) { test('2C3 same revision changed protocol hash rejects '.$field,function () use ($field) { [$f,$s]=reviewFixture(); $f[0]->protocols[1][$field]='Materially different'; rejects(fn()=>$s->approveReview(1,approvalInput())); }); }
test('2C3 same revision changed item rejects snapshot comparison',function () { [$f,$s]=reviewFixture(); $f[0]->items[1]['instructions_en']='Changed item'; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 same revision changed source locator rejects',function () { [$f,$s]=reviewFixture(); $f[0]->protocolRefs[0]['locator']='Changed location'; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 changed immutable bibliographic version rejects',function () { [$f,$s]=reviewFixture(); $f[0]->sources[1]['edition_or_version']='Unreviewed version'; rejects(fn()=>$s->approveReview(1,approvalInput())); });
test('2C3 changed submission locales reject exact snapshot',function () { [$f,$s]=reviewFixture(); $f[0]->events[1]['locales']='en'; rejects(fn()=>$s->approveReview(1,approvalInput(['locales'=>['en']]))); });
test('2C3 requested locale outside submission fails',function () { [$f,$s]=reviewFixture(false); $f[0]->actor=1; $s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'draft','locales'=>['en']]); $f[0]->actor=2; rejects(fn()=>$s->approveReview(1,approvalInput(['locales'=>['hi']]))); });
test('2C3 assessed locale subset stored but full submission hash still checked',function () { [$f,$s]=reviewFixture(); $s->approveReview(1,approvalInput(['locales'=>['en']])); check($f[0]->events[2]['locales']==='en','Locale subset lost'); [$f,$s]=reviewFixture(); $f[0]->protocols[1]['overview_hi']='Changed unselected submitted language'; rejects(fn()=>$s->approveReview(1,approvalInput(['locales'=>['en']]))); });
foreach (['hash','snapshot','event_type','protocol_id','content_revision'] as $field) { test('2C3 corrupted submission rejects '.$field,function () use ($field) { [$f,$s]=reviewFixture(); match($field) { 'hash'=>$f[0]->events[1]['content_hash']=str_repeat('0',64),'snapshot'=>$f[0]->events[1]['content_snapshot']='{}','event_type'=>$f[0]->events[1]['event_type']='content_changed','protocol_id'=>$f[0]->events[1]['protocol_id']=2,'content_revision'=>$f[0]->events[1]['content_revision']=2 }; rejects(fn()=>$s->approveReview(1,approvalInput())); }); }
test('2C3 wrong current submission ID cannot approve or request changes',function () { [$f,$s]=reviewFixture(); rejects(fn()=>$s->approveReview(1,approvalInput(['submission_id'=>'99']))); rejects(fn()=>$s->requestReviewChanges(1,changesInput(['submission_id'=>'99']))); });
test('2C3 duplicate changes-required decision rejected after state changes',function () { [$f,$s]=reviewFixture(); $s->requestReviewChanges(1,changesInput()); $before=serialize($f[0]); rejects(fn()=>$s->requestReviewChanges(1,changesInput())); check(serialize($f[0])===$before,'Duplicate decision committed'); });
test('2C3 editor edit after submission invalidates stale review',function () { [$f,$s]=reviewFixture(); $f[0]->actor=1; $s->save(1,draftInput(['overview_en'=>'New revision'],[],1,'pending_review')); $f[0]->actor=2; rejects(fn()=>$s->approveReview(1,approvalInput())); check($f[0]->protocols[1]['content_revision']===2 && $f[0]->protocols[1]['review_state']==='draft','Pending edit state incorrect'); });
test('2C3 required source withdrawal during review invalidates state and old approval',function () { [$f,$s]=reviewFixture(); $f[0]->actor=3; $s->withdrawSource(1,1,['_csrf_token'=>'fixture-csrf','reason'=>'Withdraw during review']); $f[0]->actor=2; rejects(fn()=>$s->approveReview(1,approvalInput())); check($f[0]->protocols[1]['review_state']==='draft' && $f[0]->protocols[1]['content_revision']===2 && approvalCount($f[0])===0,'Source withdrawal review invalidation failed'); });
test('2C3 source verification is a separate explicit operation',function () { [$f,$s]=reviewFixture(); $f[0]->actor=1; $id=$s->createSource(1,sourceInput()); check($f[0]->sources[$id]['status']==='unverified','Creation silently verified source'); $f[0]->actor=2; $s->verifySource(1,$id,['_csrf_token'=>'fixture-csrf']); check($f[0]->sources[$id]['status']==='verified' && approvalCount($f[0])===0 && $f[0]->protocols[1]['review_state']==='pending_review','Verification performed hidden approval'); });

foreach (['approve','changesRequired'] as $method) {
    foreach ([null,'bad',['fixture-csrf']] as $token) { test('2C3 CSRF rejection '.$method.' '.json_encode($token),function () use ($method,$token) { [$f,,$c]=reviewFixture(); $input=$method==='approve' ? approvalInput() : changesInput(); if ($token===null) { unset($input['_csrf_token']); } else { $input['_csrf_token']=$token; } $before=serialize($f[0]); reviewPost($c,$method,$input); check(http_response_code()===403 && serialize($f[0])===$before,'Review CSRF failure wrote'); }); }
    test('2C3 valid CSRF decision '.$method,function () use ($method) { [$f,,$c]=reviewFixture(); check(reviewPost($c,$method,$method==='approve' ? approvalInput() : changesInput())==='/admin/treatments/1/review','Review decision failed'); });
    test('2C3 GET cannot invoke decision '.$method,function () use ($method) { [$f,,$c]=reviewFixture(); $_POST=$method==='approve' ? approvalInput() : changesInput(); $before=serialize($f[0]); $c->$method('1'); check(http_response_code()===422 && serialize($f[0])===$before,'GET decision wrote'); });
}
foreach (['review_state','publication_status','approved_review_id','content_revision','protocol','items'] as $field) { test('2C3 review mass assignment rejected '.$field,function () use ($field) { [$f,$s]=reviewFixture(); rejects(fn()=>$s->approveReview(1,approvalInput([$field=>'modified']))); rejects(fn()=>$s->requestReviewChanges(1,changesInput([$field=>'modified']))); }); }
test('2C3 invalid protocol identifiers expose no review data',function () { foreach (['0','-1','abc','999'] as $id) { [$f,,$c]=reviewFixture(); $c->show($id); check($c->rendered['view']==='admin/treatment-review/error' && !isset($c->rendered['data']['protocol']),'Invalid ID leaked content'); } });
test('2C3 approval preserves public hold and exposes no reviewer data publicly',function () { [$f,$s]=reviewFixture(); $s->approveReview(1,approvalInput()); check($f[8]->publicTreatments()===[] && $f[0]->protocols[1]['publication_status']==='unpublished','Approval leaked to public'); });
test('2C3 queue defaults pending and filters remain qualified-system scoped',function () { [$f,$s]=reviewFixture(); $f[0]->protocols[2]=array_replace($f[0]->protocols[1],['id'=>2,'treatment_system_id'=>2]); $list=$s->reviewListing([]); check(count($list['protocols'])===1 && $list['protocols'][0]['submission_id']===1,'Review list scope/default invalid'); $s->approveReview(1,approvalInput()); check(!$s->reviewListing([])['protocols'],'Approved remained in default pending queue'); check(count($s->reviewListing(['review_state'=>'approved','due_status'=>'current','content_purpose'=>'supportive_care'])['protocols'])===1,'Review filters failed'); rejects(fn()=>$s->reviewListing(['review_state'=>'draft'])); });
test('2C3 detail shows current/submitted match and current provenance without writes',function () { [$f,$s]=reviewFixture(); $before=serialize($f[0]); $d=$s->reviewDetail(1); check($d['assessment']['matches'] && $d['assessment']['canApprove'] && $d['assessment']['submittedContent']['protocol']['id']===1 && serialize($f[0])===$before,'Read assessment mutated or lost snapshot'); $f[0]->protocols[1]['overview_en']='Changed'; $d=$s->reviewDetail(1); check(!$d['assessment']['matches'] && !$d['assessment']['canApprove'] && $d['assessment']['submittedContent']['protocol']['overview_en']==='Overview','Mismatch comparison concealed'); });
test('2C3 review HTML escapes rationale source claims and treatment text',function () { [$f,$s]=reviewFixture(); $f[0]->actor=1; $f[0]->sources[1]['citation_text']='<script>unsafe()</script>'; $f[0]->protocolRefs[0]['claim_scope']='<img src=x onerror=unsafe()>'; $f[0]->protocols[1]['overview_en']='<script>treatment()</script>'; $f[0]->actor=2; $s->requestReviewChanges(1,changesInput(['rationale'=>'<script>rationale()</script>'])); $html=renderReview('show',$s->reviewDetail(1)); check(str_contains($html,'&lt;script&gt;rationale()') && str_contains($html,'&lt;script&gt;unsafe()') && str_contains($html,'&lt;img src=x') && !str_contains($html,'<script>') && !str_contains($html,'<img src=x'),'Unsafe review HTML'); });
test('2C3 excluded items distinguished and no unsafe stored source link',function () { [$f,$s]=reviewFixture(); $f[0]->items[2]=array_replace($f[0]->items[1],['id'=>2,'is_included'=>0,'title_en'=>'Excluded fixture']); $f[0]->itemRefs[]=array_replace($f[0]->itemRefs[0],['id'=>2,'item_id'=>2]); $f[0]->sources[1]['url']='javascript:unsafe()'; $html=renderReview('show',$s->reviewDetail(1)); check(str_contains($html,'Excluded legacy/unused') && str_contains($html,'Excluded item reference — not approval support') && !str_contains($html,'href="javascript:') && !str_contains($html,'name="item_type"'),'Excluded content editable/unsafe or its references ambiguous'); });
test('2C3 review templates use labelled nonnested CSRF forms and no inline JS',function () { [$f,$s]=reviewFixture(); foreach (['index'=>$s->reviewListing([]),'show'=>$s->reviewDetail(1),'error'=>['error'=>'Safe error']] as $name=>$data) { $html=renderReview($name,$data); $doc=new DOMDocument(); $old=libxml_use_internal_errors(true); $doc->loadHTML($html); libxml_clear_errors(); libxml_use_internal_errors($old); $x=new DOMXPath($doc); foreach ($x->query('//form[@method="post"]') as $form) { check($x->query('.//input[@name="_csrf_token"]',$form)->length===1 && $x->query('.//input[@name="expected_revision"]',$form)->length===1 && ($x->query('.//input[@name="submission_id"]',$form)->length===1 || str_ends_with($form->getAttribute('action'),'/risk-eligibility')) && $x->query('.//form',$form)->length===0,'Incomplete review form'); } foreach ($x->query('//label[@for]') as $label) { check($x->query('//*[@id="'.$label->getAttribute('for').'"]')->length===1,'Unlabelled review control'); } } foreach (glob(dirname(__DIR__).'/app/Views/admin/treatment-review/*.php') as $file) { check(!preg_match('/<script\b|\bon\w+\s*=|innerHTML|javascript:/i',file_get_contents($file)),'Inline review script'); } });
test('2C3 review routes are exactly five internal GET/POST actions with no publication',function () { global $testConfiguration; $testConfiguration['base_path']=''; $loader=new class { public App\Core\Router $router; public function __construct() { $this->router=new App\Core\Router(); } public function router(): App\Core\Router { return $this->router; } public function load(): void { require dirname(__DIR__).'/routes/web.php'; } }; $loader->load(); $routes=(new ReflectionProperty(App\Core\Router::class,'routes'))->getValue($loader->router); $count=0; foreach ($routes as $verb=>$entries) { foreach ($entries as $route) { if ($route['controller']===App\Controllers\TreatmentReviewController::class) { ++$count; check(str_starts_with($route['uri'],'/admin/') && $verb===(in_array($route['method'],['approve','changesRequired','riskEligibility'],true) ? 'POST' : 'GET'),'Unsafe review route'); } check(!in_array($route['method'],['publish','withdrawProtocol'],true),'Publication route added'); } } check($count===5,'Review route count'); });
test('2C3 existing container resolves review controller without writes',function () { $container=new App\Core\Container(); $pdo=new GovernanceTransactionPDO(); (new ReflectionProperty($container,'instances'))->setValue($container,[App\Core\Database::class=>new GovernanceContainerDatabase($pdo)]); check($container->get(App\Controllers\TreatmentReviewController::class) instanceof App\Controllers\TreatmentReviewController && $pdo->begins===0,'Review dependency resolution'); });

// Phase 2C-3A: exercise the real view and existing service gates using memory only.
test('2C3A approved detail has no prerequisite warning and approval stays unpublished',function () {
    [$f,$s]=reviewFixture(); $s->approveReview(1,approvalInput());
    $before=serialize($f[0]); $d=$s->reviewDetail(1); $html=renderReview('show',$d);
    check($d['assessment']['issues'] && !$d['assessment']['canApprove'],'Approved state validation was weakened');
    check(!str_contains($html,'Approval prerequisites need attention:') && str_contains($html,'Review: approved · Publication: unpublished'),'Approved detail misleading');
    check($f[8]->publicTreatments()===[] && $f[0]->protocols[1]['published_locales']==='','Approval published content');
    rejects(fn()=>$s->approveReview(1,approvalInput()));
    check(serialize($f[0])===$before,'Detail or repeated approval changed state');
});
test('2C3A blocked pending detail retains warning and approval rejection',function () {
    [$f,$s]=reviewFixture(); $f[0]->sources[1]['status']='unverified';
    $before=serialize($f[0]); $d=$s->reviewDetail(1); $html=renderReview('show',$d);
    check(!$d['assessment']['canApprove'] && str_contains($html,'Approval prerequisites need attention:') && str_contains($html,'<fieldset disabled>'),'Pending warning/gate lost');
    rejects(fn()=>$s->approveReview(1,approvalInput())); check(serialize($f[0])===$before,'Blocked approval wrote');
});
test('2C3A ready pending detail has no prerequisite warning',function () {
    [$f,$s]=reviewFixture(); $d=$s->reviewDetail(1);
    check($d['assessment']['canApprove'] && !str_contains(renderReview('show',$d),'Approval prerequisites need attention:'),'Ready pending detail misleading');
});
foreach (['draft','changes_required','published','withdrawn'] as $state) {
    test('2C3A nonpending detail has no prerequisite warning '.$state,function () use ($state) {
        [$f,$s]=reviewFixture($state!=='draft');
        if ($state==='changes_required') { $s->requestReviewChanges(1,changesInput()); }
        if (in_array($state,['published','withdrawn'],true)) {
            $s->approveReview(1,approvalInput()); $f[0]->actor=3; $f[4]->publish(1,1,['en']);
            if ($state==='withdrawn') { $f[4]->withdraw(1,1,'approved','Fixture withdrawal'); }
            $f[0]->actor=2;
        }
        $before=serialize($f[0]); $html=renderReview('show',$s->reviewDetail(1));
        check(!str_contains($html,'Approval prerequisites need attention:') && !str_contains($html,'Approve the saved submitted revision'),'Nonpending detail offers misleading approval');
        check(str_contains($html,'Review: '.e($f[0]->protocols[1]['review_state']).' · Publication: '.e($f[0]->protocols[1]['publication_status'])) && serialize($f[0])===$before,'State labels lost or detail wrote');
    });
}
foreach (['evidence_supported'=>true,'insufficient_evidence'=>false,'evidence_does_not_support'=>false,'not_assessed'=>false,'unknown'=>false] as $classification=>$allowed) {
    test('2C3A initial evidence permission '.$classification,function () use ($classification,$allowed) {
        $f=fixture(); check($f[8]->evidenceAllowed($classification)===$allowed,'Initial evidence policy differs');
    });
}
test('2C3A supported reviewed content passes existing explicit publication gates',function () {
    $f=fixture(); reviewed($f); check($f[0]->protocols[1]['publication_status']==='unpublished','Approval published');
    $f[0]->actor=3; $f[4]->publish(1,1,['en']); check($f[0]->protocols[1]['publication_status']==='published','Supported content rejected');
});
foreach (['insufficient_evidence','evidence_does_not_support','not_assessed'] as $classification) {
    foreach (['protocol','included_item'] as $target) {
        test('2C3A existing publication rejects '.$target.' '.$classification,function () use ($classification,$target) {
            $f=fixture(); reviewed($f); $m=$f[0];
            // Simulate a historically saved approval of this exact content in memory.
            // A matching hash prevents a stale snapshot from masking the evidence gate.
            if ($target==='protocol') { $m->protocols[1]['evidence_classification']=$classification; }
            else { $m->items[1]['evidence_classification']=$classification; }
            $m->events[2]=array_replace($m->events[2],$f[2]->capture($f[5]->aggregate(1),['en','hi']));
            $m->actor=3; $before=serialize($m); rejects(fn()=>$f[4]->publish(1,1,['en']));
            check(serialize($m)===$before && $m->protocols[1]['publication_status']==='unpublished','Evidence denial mutated state');
        });
    }
}
test('2C3A wellness has no unsupported evidence publication exception',function () {
    $f=fixture(); reviewed($f); $m=$f[0]; $m->protocols[1]['content_purpose']='wellness_self_care'; $m->protocols[1]['evidence_classification']='insufficient_evidence';
    $m->events[2]=array_replace($m->events[2],$f[2]->capture($f[5]->aggregate(1),['en','hi']));
    $m->actor=3; $before=serialize($m); rejects(fn()=>$f[4]->publish(1,1,['en'])); check(serialize($m)===$before,'Wellness exception published');
});
foreach (['2000-01-01 00:00:00.000000','now'] as $due) {
    test('2C3A expired or due-now review blocks publication without changing approval '.$due,function () use ($due) {
        $f=fixture(); reviewed($f); $m=$f[0]; $m->events[2]['review_due_at']=$due==='now' ? $f[1]->now() : $due;
        $m->actor=3; $before=serialize($m); rejects(fn()=>$f[4]->publish(1,1,['en']));
        check(serialize($m)===$before && $m->protocols[1]['review_state']==='approved','Expiry mutated stored approval');
    });
}
test('2C3A legacy content remains unpublished with no invented evidence or approval',function () {
    $f=fixture(true); $m=$f[0]; $before=serialize($m); $m->actor=3;
    rejects(fn()=>$f[4]->publish(1,1,['en'])); $m->actor=1;
    check(serialize($m)===$before && $m->protocols[1]['publication_status']==='unpublished' && $m->protocols[1]['evidence_classification']==='not_assessed' && !$m->events && !$m->sources,'Legacy governance fabricated');
});

$reviewPassed=$passed-$earlierPassed;
echo "Treatment review: $reviewPassed tests passed; $foundationPassed foundation and $editorialPassed editorial tests also passed; no database/session/log writes.\n";
ob_end_flush();

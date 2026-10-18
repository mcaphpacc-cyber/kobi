<?php

declare(strict_types=1);

// Phase 2C-3B: database-free risk, withdrawal and public comparison regressions.
ob_start();
require __DIR__.'/treatment-review.php';
function riskInput(array $replace=[]): array {
    return array_replace(['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'pending_review','risk_eligibility'=>'eligible','rationale'=>'Explicit assessment under initial public-risk policy','independence_confirmed'=>'1'],$replace);
}
$riskStart=$passed;
foreach (['eligible'=>true,'not_eligible'=>false,'not_assessed'=>false,'invalid'=>false,''=>false] as $decision=>$allowed) {
    test('3B risk policy '.$decision,function () use ($decision,$allowed) { check((new App\Services\TreatmentGovernancePolicy())->riskAllowed($decision)===$allowed,'Risk policy differs'); });
}
test('3B risk policy rejects missing and nonstring decisions',function () { $p=new App\Services\TreatmentGovernancePolicy(); foreach ([null,1,[],true] as $v) { check(!$p->riskAllowed($v),'Invalid risk allowed'); } });
foreach (['not_eligible','not_assessed'] as $decision) {
    test('3B supported evidence with '.$decision.' blocks approval and publication',function () use ($decision) {
        [$f,$s]=reviewFixture(); $m=$f[0]; $m->protocols[1]['risk_eligibility']=$decision;
        rejects(fn()=>$s->approveReview(1,approvalInput(['risk_eligibility'=>$decision])));
        $f=fixture(); reviewed($f); $m=$f[0]; $m->protocols[1]['risk_eligibility']=$decision;
        $m->events[2]=array_replace($m->events[2],$f[2]->capture($f[5]->aggregate(1),['en','hi'])); $m->actor=3;
        $before=serialize($m); rejects(fn()=>$f[4]->publish(1,1,['en'])); check(serialize($m)===$before,'Risk rejection mutated');
    });
}
foreach ([null,'invalid','not_eligible','not_assessed',[],1] as $decision) {
    test('3B approval requires explicit eligible confirmation '.json_encode($decision),function () use ($decision) {
        [$f,$s]=reviewFixture(); $input=approvalInput(['risk_eligibility'=>$decision]); if ($decision===null) { unset($input['risk_eligibility']); }
        $before=serialize($f[0]); rejects(fn()=>$s->approveReview(1,$input)); check(serialize($f[0])===$before,'Invalid confirmation wrote');
    });
}
test('3B risk changes create hashed new revision requiring fresh submission and approval',function () {
    [$f,$s]=reviewFixture(); $m=$f[0]; $s->approveReview(1,approvalInput()); $old=$m->events[2];
    $s->recordRiskEligibility(1,riskInput(['expected_state'=>'approved','risk_eligibility'=>'not_eligible']));
    check($m->protocols[1]['content_revision']===2 && $m->protocols[1]['review_state']==='draft' && $m->protocols[1]['approved_review_id']===null && $m->events[2]===$old,'Risk change preserved approval or changed history');
    $riskEvent=$m->events[3]; check($riskEvent['event_type']==='risk_assessed' && json_decode($riskEvent['content_snapshot'],true)['protocol']['risk_eligibility']==='not_eligible' && hash('sha256',$riskEvent['content_snapshot'])===$riskEvent['content_hash'],'Risk decision not snapshotted');
    rejects(fn()=>$s->approveReview(1,approvalInput()));
    $s->recordRiskEligibility(1,riskInput(['expected_revision'=>'2','expected_state'=>'draft']));
    $m->actor=1; $s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'3','expected_state'=>'draft','locales'=>['en','hi']]);
    $m->actor=2; $s->approveReview(1,approvalInput(['expected_revision'=>'3','submission_id'=>'5']));
    check($m->protocols[1]['review_state']==='approved' && $m->protocols[1]['publication_status']==='unpublished','Fresh risk review failed');
});
test('3B pending risk change invalidates old submission and stale approval',function () {
    [$f,$s]=reviewFixture(); $f[0]->protocols[1]['risk_eligibility']='not_assessed';
    $s->recordRiskEligibility(1,riskInput()); check($f[0]->protocols[1]['content_revision']===2 && $f[0]->protocols[1]['review_state']==='draft','Risk change not material');
    rejects(fn()=>$s->approveReview(1,approvalInput()));
});
test('3B same risk decision does not create a revision or approval',function () { [$f,$s]=reviewFixture(); $before=serialize([$f[0]->protocols,$f[0]->events]); $s->recordRiskEligibility(1,riskInput()); check(serialize([$f[0]->protocols,$f[0]->events])===$before,'No-op risk wrote'); });
foreach ([null,'invalid',[],1] as $decision) {
    test('3B risk input rejects '.json_encode($decision),function () use ($decision) { [$f,$s]=reviewFixture(); $input=riskInput(['risk_eligibility'=>$decision]); if ($decision===null) { unset($input['risk_eligibility']); } $before=serialize($f[0]); rejects(fn()=>$s->recordRiskEligibility(1,$input)); check(serialize($f[0])===$before,'Invalid risk wrote'); });
}
foreach ([0,1,3] as $actor) {
    test('3B risk decision requires qualified reviewer '.$actor,function () use ($actor) { [$f,$s]=reviewFixture(); $f[0]->actor=$actor; rejects(fn()=>$s->recordRiskEligibility(1,riskInput(['risk_eligibility'=>'not_eligible']))); });
}
test('3B risk rejects wrong scope revoked grant missing qualification and self-assessment',function () {
    foreach (['scope','revoked','qualification','self'] as $case) {
        [$f,$s]=reviewFixture();
        if ($case==='scope') { $f[0]->protocols[1]['treatment_system_id']=2; }
        if ($case==='revoked') { $f[0]->grants[2]['revoked_at']=$f[1]->now(); }
        if ($case==='qualification') { $f[0]->grants[2]['qualification_scope']=' '; }
        if ($case==='self') { $f[0]->events[99]=['protocol_id'=>1,'actor_id'=>2,'event_type'=>'content_changed','content_revision'=>1]; }
        rejects(fn()=>$s->recordRiskEligibility(1,riskInput(['risk_eligibility'=>'not_eligible'])));
    }
});
test('3B risk requires independence and current revision and rejects mass assignment',function () {
    foreach ([['independence_confirmed'=>'0'],['expected_revision'=>'2'],['actor_id'=>'3'],['publication_status'=>'published'],['evidence_classification'=>'evidence_supported']] as $change) {
        [$f,$s]=reviewFixture(); $before=serialize($f[0]); rejects(fn()=>$s->recordRiskEligibility(1,riskInput($change))); check(serialize($f[0])===$before,'Risk request bypassed controls');
    }
});
test('3B risk controller enforces guest POST and CSRF boundaries',function () {
    foreach ([null,'bad',[]] as $token) { [$f,,$c]=reviewFixture(); $input=riskInput(['_csrf_token'=>$token]); if ($token===null) { unset($input['_csrf_token']); } $before=serialize($f[0]); reviewPost($c,'riskEligibility',$input); check(http_response_code()===403 && serialize($f[0])===$before,'Risk CSRF bypass'); }
    [$f,,$c]=reviewFixture(); $_SERVER['REQUEST_METHOD']='GET'; $_POST=riskInput(); $c->riskEligibility('1'); check(http_response_code()===422,'Risk GET allowed');
    $f[0]->actor=0; try { $c->riskEligibility('1'); throw new LogicException('Guest allowed'); } catch (EditorialRedirect $e) { check($e->target==='/login','Guest route'); }
});
test('3B legacy risk remains unassessed and editor cannot assign risk',function () {
    [$f,$s]=editorialFixture(true); check($f[0]->protocols[1]['risk_eligibility']==='not_assessed' && $f[0]->protocols[1]['publication_status']==='unpublished','Legacy risk auto-assigned');
    rejects(fn()=>$s->save(1,draftInput(['risk_eligibility'=>'eligible']))); rejects(fn()=>$f[4]->edit(1,1,'draft',['risk_eligibility'=>'eligible']));
});
$riskPassed=$passed-$riskStart;
test('3B persisted missing invalid or changed risk fails closed',function () {
    foreach (['missing','invalid','not_eligible'] as $case) {
        $f=fixture(); reviewed($f); if ($case==='missing') { unset($f[0]->protocols[1]['risk_eligibility']); } else { $f[0]->protocols[1]['risk_eligibility']=$case; }
        $f[0]->actor=3; rejects(fn()=>$f[4]->publish(1,1,['en']));
    }
});
$riskPassed=$passed-$riskStart;
function withdrawnFixture(): array { [$f,$s,$c]=editorialFixture(); published($f); $f[4]->withdraw(1,1,'approved','Explicit fixture withdrawal'); $f[0]->actor=1; return [$f,$s,$c]; }
$lifecycleStart=$passed;
test('3B withdrawn substantive edit enters unpublished draft exactly once with history',function () {
    [$f,$s]=withdrawnFixture(); $m=$f[0]; $history=$m->events;
    $s->save(1,draftInput(['overview_en'=>'Fresh post-withdrawal revision'],[1=>['instructions_en'=>'Fresh item']],1,'draft'));
    $p=$m->protocols[1]; check($p['content_revision']===2 && $p['review_state']==='draft' && $p['publication_status']==='unpublished' && $p['approved_review_id']===null && $p['published_locales']==='','Withdrawn edit state incorrect');
    foreach ($history as $id=>$event) { check($m->events[$id]===$event,'Withdrawal history changed'); }
    $s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'2','expected_state'=>'draft','locales'=>['en','hi']]);
    $m->actor=2; $s->approveReview(1,approvalInput(['expected_revision'=>'2','submission_id'=>'6']));
    check($m->protocols[1]['review_state']==='approved' && $m->protocols[1]['publication_status']==='unpublished','Fresh withdrawn lifecycle failed');
});
test('3B withdrawn no-op remains withdrawn and cannot submit directly into HTTP approval',function () {
    [$f,$s]=withdrawnFixture(); $before=serialize([$f[0]->protocols,$f[0]->events]); check(!$s->save(1,draftInput([],[],1,'draft')) && serialize([$f[0]->protocols,$f[0]->events])===$before,'No-op restored withdrawal');
    $f[0]->actor=2; rejects(fn()=>$s->approveReview(1,approvalInput(['expected_state'=>'draft']))); rejects(fn()=>$s->recordRiskEligibility(1,riskInput(['expected_state'=>'draft'])));
    $f[0]->actor=1; rejects(fn()=>$s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'draft','locales'=>['en']]));
    $f[0]->actor=3; rejects(fn()=>$f[4]->publish(1,1,['en']));
});
foreach ([0,2] as $actor) { test('3B unauthorized withdrawn edit '.$actor,function () use ($actor) { [$f,$s]=withdrawnFixture(); $f[0]->actor=$actor; rejects(fn()=>$s->save(1,draftInput(['overview_en'=>'Forbidden'],[],1,'draft'))); }); }
test('3B withdrawn edit rejects stale revision wrong scope and forged metadata',function () {
    foreach (['stale','scope','risk_eligibility','review_state','publication_status','approved_review_id','reviewer_id','published_locales','approval_metadata'] as $case) {
        [$f,$s]=withdrawnFixture(); if ($case==='scope') { $f[0]->protocols[1]['treatment_system_id']=2; }
        $input=draftInput(['overview_en'=>'Changed'],[],$case==='stale' ? 99 : 1,'draft');
        if (!in_array($case,['stale','scope'],true)) { $input['protocol'][$case]='forged'; }
        $before=serialize($f[0]); rejects(fn()=>$s->save(1,$input)); check(serialize($f[0])===$before,'Forged withdrawn edit wrote');
    }
});
test('3B withdrawn HTTP edit retains CSRF protection',function () { [$f,,$c]=withdrawnFixture(); $_SERVER['REQUEST_METHOD']='POST'; $_POST=draftInput(['overview_en'=>'Forbidden'],[],1,'draft'); $_POST['_csrf_token']='bad'; $before=serialize($f[0]); $c->save('1'); check(http_response_code()===403 && serialize($f[0])===$before,'Withdrawn edit CSRF bypass'); });
test('3B published risk changes and published edits remain prohibited',function () { $f=fixture(); published($f); $f[0]->actor=2; rejects(fn()=>$f[4]->recordRiskEligibility(1,1,'approved','not_eligible','Reason',true)); $f[0]->actor=1; rejects(fn()=>$f[4]->edit(1,1,'approved',['overview_en'=>'Forbidden'])); });
$lifecyclePassed=$passed-$lifecycleStart;
$comparisonStart=$passed;
foreach (['draft','pending_review','approved_unpublished','withdrawn','legacy','bad_evidence','bad_risk','stale_approval','published'] as $state) {
    test('3B comparison hold hides raw counts '.$state,function () use ($state) {
        $repo=new class($state) extends App\Repositories\DiseaseRepository {
            public function __construct(private string $state) {}
            public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array { return []; }
            public function findKnowledgeBySlug(string $slug,string $language='en'): ?array { if ($slug==='missing') { return null; } return ['disease'=>['id'=>1,'slug'=>$slug],'symptoms'=>[],'causes'=>[],'content'=>['overview_en'=>'Preserved'],'treatments'=>[['state'=>$this->state,'title_en'=>'Internal fixture']]]; }
        };
        $r=new ReflectionClass(App\Services\DiseaseService::class); $s=$r->newInstanceWithoutConstructor();
        $r->getProperty('diseaseReferenceService')->setValue($s,new App\Services\DiseaseReferenceService());
        $r->getProperty('repository')->setValue($s,$repo); $r->getProperty('treatmentPublicationService')->setValue($s,emptyPublicationReader()); $r->getProperty('comparisonBuilder')->setValue($s,new App\Algorithms\DiseaseComparisonBuilder());
        global $testConfiguration;
        foreach (['hold','governed','invalid'] as $mode) { $testConfiguration=['treatment_governance'=>['publication_mode'=>$mode]]; $data=$s->getComparison('left','right'); check($data['left']['treatments']===[] && $data['right']['treatments']===[] && $data['left']['content']['overview_en']==='Preserved','Raw comparison leak'); check($s->getKnowledgeBySlug('left')['treatments']===[],'Disease/comparison disagreement'); }
        check($s->getComparison('left','missing')===null,'Missing comparison changed');
    });
}
test('3B shared public boundary supports future reader without comparison predicate',function () {
    global $testConfiguration; $testConfiguration=['treatment_governance'=>['publication_mode'=>'governed']];
    $p=new App\Services\TreatmentGovernancePolicy(); $public=[['id'=>1,'treatment_system_id'=>1,'title'=>'Public fixture']];
    check($p->publicTreatments(fn()=>$public)===$public,'Future governed reader extension unavailable');
    $source=file_get_contents(dirname(__DIR__).'/app/Services/DiseaseService.php'); check(substr_count($source,'$this->treatmentPublicationService->getPublicTreatmentsForDisease(')===1,'Duplicated public boundary');
});
$comparisonPassed=$passed-$comparisonStart;
test('legacy disease detail and future governed comparison remain separate',function () {
    global $testConfiguration; $testConfiguration=['treatment_governance'=>['publication_mode'=>'governed']];
    $r=new ReflectionClass(App\Services\DiseaseService::class); $s=$r->newInstanceWithoutConstructor();
    $r->getProperty('diseaseReferenceService')->setValue($s,new App\Services\DiseaseReferenceService());
    $r->getProperty('repository')->setValue($s,new class extends App\Repositories\DiseaseRepository {
        public function __construct() {}
        public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array { return [['id'=>99,'treatment_system_id'=>1,'title_en'=>'Legacy fixture']]; }
        public function findKnowledgeBySlug(string $slug,string $language='en'): ?array { return ['disease'=>['id'=>1],'symptoms'=>[],'causes'=>[],'treatments'=>[['internal'=>'must disappear']]]; }
    });
    $r->getProperty('comparisonBuilder')->setValue($s,new App\Algorithms\DiseaseComparisonBuilder());
    // Synthetic projection delegate tests wiring; canonical eligibility has its own focused suite.
    $r->getProperty('treatmentPublicationService')->setValue($s,new class extends App\Services\TreatmentPublicationService {
        public function __construct() {}
        public function getPublicTreatmentsForDisease(int $diseaseId,string $locale='en'): array {
            return (new App\Services\TreatmentGovernancePolicy())->publicTreatments(fn()=>[['protocol_id'=>5,'title'=>'Projected public fixture','treatment_system_id'=>1]]);
        }
    });
    $d=$s->getKnowledgeBySlug('left'); $c=$s->getComparison('left','right');
    check($d['treatments'][0]['id']===99 && $c['left']['treatments']===$c['right']['treatments'] && count($c['left']['treatments'])===1 && !str_contains(json_encode($c),'Legacy fixture') && !isset($d['treatments'][0]['internal']),'Legacy/public separation failed');
    $testConfiguration=['treatment_governance'=>['publication_mode'=>'hold']]; check($s->getKnowledgeBySlug('left')['treatments'][0]['id']===99 && !$s->getComparison('left','right')['left']['treatments'],'Future delegate bypassed hold');
});
$comparisonPassed=$passed-$comparisonStart;
echo "Phase 2C-3B: $riskPassed risk, $lifecyclePassed lifecycle, $comparisonPassed comparison checks passed; no database/session/log writes.\n";
ob_end_flush();

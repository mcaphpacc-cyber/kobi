<?php
declare(strict_types=1);

// Database-free focused 4B-1 regressions. Existing assertions remain unmodified.
ob_start();
require __DIR__.'/treatment-review.php';
$hardeningBaseline=$passed;

final class HardeningPermissions extends App\Repositories\TreatmentEditorialPermissionRepository
{
    public ?Throwable $failure=null;
    public string $stage='active';
    public function __construct(private GovernanceMemoryPermissions $inner) {}
    private function fail(string $stage): void { if ($this->failure && $stage===$this->stage) { throw $this->failure; } }
    public function user(int $id): ?array { $this->fail('user'); return $this->inner->user($id); }
    public function lockUsers(array $ids): array { $this->fail('lockUsers'); return $this->inner->lockUsers($ids); }
    public function active(int $userId,int $systemId,bool $lock=false): ?array { $this->fail('active'); return $this->inner->active($userId,$systemId,$lock); }
    public function systems(int $userId): array { return $this->inner->systems($userId); }
}
function hardeningFixture(): array {
    [$f]=editorialFixture();
    $permissions=new HardeningPermissions($f[7]);
    $auth=new App\Services\TreatmentEditorialAuthorizationService(new GovernanceMemoryAuth($f[0]),$permissions,$f[1],$f[8]);
    $editor=new App\Services\TreatmentEditorialService($f[5],$f[6],$permissions,$auth,$f[4],$f[1]);
    return [$f,$permissions,$auth,$editor,new EditorialController($editor,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]),new ReviewControllerFixture($editor,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1])];
}
// Only the operation is caught; assertions run outside the catch and retain exit 1.
function hardeningFailure(callable $operation): ?Throwable { try { $operation(); } catch (Throwable $error) { return $error; } return null; }

test('4B1 permission present still authorizes',function () { [,$p,$a]=hardeningFixture(); check($a->can('edit',1) && $a->require('edit',1)===1,'Valid grant denied'); });
test('4B1 permission absent still denies',function () { [$f,,$a]=hardeningFixture(); $f[0]->grants=[]; check(!$a->can('edit',1),'Missing grant granted'); check(hardeningFailure(fn()=>$a->require('edit',1)) instanceof RuntimeException,'Missing grant not rejected'); });
test('4B1 revoked and wrong-scope grants stay denied',function () { [$f,,$a]=hardeningFixture(); check(!$a->can('edit',2),'Wrong scope granted'); $f[0]->grants[1]['revoked_at']='2026-01-01 00:00:00.000000'; check(!$a->can('edit',1),'Revoked granted'); });
foreach (['active','user','lockUsers'] as $stage) {
    foreach ([PDOException::class,RuntimeException::class,UnexpectedValueException::class] as $class) {
        test('4B1 permission failure propagates '.$stage.' '.$class,function () use ($stage,$class) {
            [,$p,$a]=hardeningFixture(); $cause=new $class('PRIVATE_SQL_HOST_CREDENTIAL_PATH'); $p->failure=$cause; $p->stage=$stage;
            $error=hardeningFailure(fn()=>$stage==='lockUsers' ? $a->require('edit',1,true) : $a->can('edit',1));
            check($error instanceof RuntimeException && $error->getPrevious()===$cause,'Failure swallowed or cause lost');
        });
    }
}
test('4B1 permission lookup assertion failure is not absence',function () { [,$p,$a]=hardeningFixture(); $p->failure=new AssertionError('Sentinel'); check(hardeningFailure(fn()=>$a->can('edit',1))===$p->failure,'Assertion converted to absence'); });
foreach (['admin','review'] as $kind) {
    foreach ([PDOException::class,RuntimeException::class,UnexpectedValueException::class] as $class) {
        test('4B1 '.$kind.' permission outage safe 503 '.$class,function () use ($kind,$class) {
            [$f,$p,,,$admin,$review]=hardeningFixture(); $f[0]->actor=$kind==='admin' ? 1 : 2;
            $before=serialize($f[0]); $p->failure=new $class('PRIVATE_SQL_HOST_CREDENTIAL_PATH');
            App\Core\Logger::$messages=[];
            if ($kind==='admin') { $admin->index(); $result=$admin->rendered; } else { $review->index(); $result=$review->rendered; }
            check(http_response_code()===503 && !str_contains(serialize($result),'PRIVATE_'),'Unsafe outage response');
            check(count(App\Core\Logger::$messages)>0 && str_contains(implode(' ',App\Core\Logger::$messages),$class),'Diagnostic class absent');
            check(serialize($f[0])===$before,'Outage wrote governance');
        });
    }
    test('4B1 '.$kind.' unauthorized direct route remains 403',function () use ($kind) { [$f,,,, $admin,$review]=hardeningFixture(); $f[0]->grants=[]; if ($kind==='admin') { $admin->edit('1'); } else { $review->show('1'); } check(http_response_code()===403,'Unauthorized route accepted'); });
    test('4B1 '.$kind.' unexpected runtime subclass safe 500',function () use ($kind) {
        [$f]=editorialFixture();
        $editor=new class($f[5],$f[6],$f[7],$f[3],$f[4],$f[1]) extends App\Services\TreatmentEditorialService {
            public function detail(int $id): array { throw new UnexpectedValueException('PRIVATE_INTERNAL_REPOSITORY'); }
            public function reviewDetail(int $id,array $input=[]): array { throw new UnexpectedValueException('PRIVATE_INTERNAL_REPOSITORY'); }
        };
        $controller=$kind==='admin' ? new EditorialController($editor,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]) : new ReviewControllerFixture($editor,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]);
        if ($kind==='admin') { $controller->edit('1'); } else { $controller->show('1'); }
        check(http_response_code()===500 && !str_contains(serialize($controller->rendered),'PRIVATE_'),'Unexpected failure disclosed');
    });
}
test('4B1 missing and forged CSRF reject direct POST',function () { foreach ([null,'forged'] as $token) { [$f,, $c]=editorialFixture(); $input=draftInput(['title_en'=>'No']); if ($token===null) { unset($input['_csrf_token']); } else { $input['_csrf_token']=$token; } $before=serialize($f[0]); controllerPost($c,'save',$input); check(http_response_code()===403 && serialize($f[0])===$before,'CSRF accepted'); } });
test('4B1 direct POST permission outage is generic and does not save',function () { [$f,$p,,,$admin]=hardeningFixture(); $p->failure=new PDOException('PRIVATE_SQL'); $before=serialize($f[0]); controllerPost($admin,'save',draftInput(['title_en'=>'No'])); check(http_response_code()===503 && !str_contains(serialize($admin->rendered),'PRIVATE_') && serialize($f[0])===$before,'Outage saved or disclosed'); });
test('4B1 stale save remains 409',function () { [$f,$e,$c]=editorialFixture(); $e->save(1,draftInput(['overview_en'=>'New'])); $before=serialize($f[0]); controllerPost($c,'save',draftInput(['overview_en'=>'Old'])); check(http_response_code()===409 && serialize($f[0])===$before,'Stale save accepted'); });
test('4B1 invalid content stays safe validation 422',function () { [$f,,$c]=editorialFixture(); controllerPost($c,'save',draftInput(['evidence_classification'=>'invalid'])); check(http_response_code()===422 && $c->rendered['data']['error']==='Invalid treatment governance value.','Validation suppressed'); });
test('4B1 invalid review transition stays rejected',function () { [$f,$e,$c]=reviewFixture(false); reviewPost($c,'approve',approvalInput(['expected_state'=>'draft'])); check(http_response_code()===409 && approvalCount($f[0])===0,'Invalid state approved'); });

foreach (['eligible','not_eligible','not_assessed'] as $risk) {
    test('4B1 submitted persisted risk displays '.$risk,function () use ($risk) {
        [$f,$e]=reviewFixture(); $event=&$f[0]->events[1]; $saved=json_decode($event['content_snapshot'],true); $saved['protocol']['risk_eligibility']=$risk;
        $event['content_snapshot']=$f[2]->canonical($saved); $event['content_hash']=hash('sha256',$event['content_snapshot']); unset($event);
        $detail=$e->reviewDetail(1); check($detail['assessment']['submittedContent']['protocol']['risk_eligibility']===$risk,'Saved risk omitted or current substituted');
        $submitted=$detail; $submitted['reviewContent']=$detail['assessment']['submittedContent']; $submitted['liveSources']=array_column($detail['sources'],null,'id');
        $html=renderReview('_content',$submitted); check(str_contains($html,'<strong>'.$risk.'</strong>'),'Saved decision not displayed');
        check(!str_contains($html,'clinically proven'),'Risk mislabeled');
        if ($risk!=='eligible') { check(!$detail['assessment']['matches'] && !$detail['assessment']['canApprove'],'Mismatch allowed approval'); }
    });
}
test('4B1 submitted eligible risk is independent from changed current row',function () { [$f,$e]=reviewFixture(); $f[0]->protocols[1]['risk_eligibility']='not_eligible'; $detail=$e->reviewDetail(1); check($detail['protocol']['risk_eligibility']==='not_eligible' && $detail['assessment']['submittedContent']['protocol']['risk_eligibility']==='eligible' && !$detail['assessment']['canApprove'],'Current substituted for saved decision'); });
foreach ([null,'unknown','',1,['eligible'], '<script>PRIVATE_RISK</script>'] as $risk) {
    test('4B1 malformed submitted risk fails closed '.json_encode($risk),function () use ($risk) {
        [$f,$e]=reviewFixture(); $saved=json_decode($f[0]->events[1]['content_snapshot'],true); $saved['protocol']['risk_eligibility']=$risk;
        $f[0]->events[1]['content_snapshot']=$f[2]->canonical($saved); $f[0]->events[1]['content_hash']=hash('sha256',$f[0]->events[1]['content_snapshot']);
        $detail=$e->reviewDetail(1); check($detail['assessment']['submittedContent']===null && !$detail['assessment']['canApprove'],'Malformed risk assigned meaning');
        check(str_contains(implode(' ',$detail['assessment']['issues']),'Submitted risk eligibility is unavailable or invalid'),'Malformed decision not surfaced');
        check(!str_contains(renderReview('show',$detail),'PRIVATE_RISK'),'Malformed content rendered');
    });
}
test('4B1 missing submitted risk is unavailable rather than inferred',function () { [$f,$e]=reviewFixture(); $saved=json_decode($f[0]->events[1]['content_snapshot'],true); unset($saved['protocol']['risk_eligibility']); $f[0]->events[1]['content_snapshot']=$f[2]->canonical($saved); $f[0]->events[1]['content_hash']=hash('sha256',$f[0]->events[1]['content_snapshot']); check($e->reviewDetail(1)['assessment']['submittedContent']===null,'Missing risk inferred'); });

$hardeningPassed=$passed-$hardeningBaseline;
echo "Treatment admin hardening: $hardeningPassed focused tests passed; existing governance/editorial/review suites retained; no database/session/log writes.\n";
ob_end_flush();

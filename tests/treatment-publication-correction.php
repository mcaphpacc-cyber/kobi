<?php

declare(strict_types=1);

// Reuse the existing canonical fixture/validators; no database or configuration writes.
ob_start();
require __DIR__.'/treatment-publication.php';
ob_end_clean();
$correctionBaseline = $publicPassed;

function correctionDisease(App\Services\TreatmentPublicationService $publication): App\Services\DiseaseService {
    $reflection = new ReflectionClass(App\Services\DiseaseService::class);
    $service = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('diseaseReferenceService')->setValue($service, new App\Services\DiseaseReferenceService());
    $reflection->getProperty('treatmentPublicationService')->setValue($service, $publication);
    $reflection->getProperty('comparisonBuilder')->setValue($service, new App\Algorithms\DiseaseComparisonBuilder());
    $reflection->getProperty('repository')->setValue($service, new class extends App\Repositories\DiseaseRepository {
        public function __construct() {}
        public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array { return []; }
        public function findKnowledgeBySlug(string $slug, string $language = 'en'): ?array {
            return ['disease'=>['id'=>1,'slug'=>$slug], 'symptoms'=>[], 'causes'=>[],
                'treatments'=>[['draft_secret'=>'must disappear']]];
        }
    });
    return $service;
}

publicTest('correction eligible under HOLD is CANDIDATE', function () {
    [$repo,$service] = publicFixture('hold'); $before = serialize($repo->rows);
    publicCheck($service->assessProtocolForPublicRead(1,1,'en') === ['status'=>'CANDIDATE'], 'HOLD changed eligibility');
    publicCheck(serialize($repo->rows) === $before, 'Assessment wrote content');
});
publicTest('correction ineligible under HOLD is DENY', function () {
    [$repo,$service,$snapshots] = publicFixture('hold');
    $repo->rows[0]['protocol']['title_en'] = ''; publicSign($repo->rows[0],$snapshots);
    publicCheck($service->assessProtocolForPublicRead(1,1) === ['status'=>'DENY'], 'Missing title allowed');
});
publicTest('correction eligible HOLD projection remains null', function () {
    [$repo,$service] = publicFixture('hold');
    publicCheck($service->assessProtocolForPublicRead(1,1)['status']==='CANDIDATE' && $service->getPublicTreatmentProjection(1,1)===null && count($repo->calls)===1, 'Projection bypassed HOLD');
});
publicTest('correction eligible HOLD governed retrieval remains empty; no legacy detail fixture', function () {
    [$repo,$service] = publicFixture('hold');
    publicCheck($service->assessProtocolForPublicRead(1,1)['status']==='CANDIDATE'
        && $service->getPublicTreatmentsForDisease(1)===[]
        && correctionDisease($service)->getKnowledgeBySlug('disease')['treatments']===[], 'Disease leaked candidate');
});
publicTest('correction eligible HOLD comparison remains empty', function () {
    [$repo,$service] = publicFixture('hold');
    publicCheck($service->assessProtocolForPublicRead(1,1)['status']==='CANDIDATE', 'Candidate missing');
    $comparison = correctionDisease($service)->getComparison('left','right');
    publicCheck($comparison['left']['treatments']===[] && $comparison['right']['treatments']===[]
        && !str_contains(json_encode($comparison),'draft_secret'), 'Comparison leaked raw counts or rows');
});
publicTest('correction HOLD assessment loads requested aggregate', function () {
    [$repo,$service] = publicFixture('hold');
    publicCheck($service->assessProtocolForPublicRead(1,1,'en')['status']==='CANDIDATE' && $repo->calls===[[1,1]], 'HOLD short-circuited assessment');
});
publicTest('correction source withdrawal under HOLD causes DENY', function () {
    [$repo,$service] = publicFixture('hold');
    publicCheck($service->assessProtocolForPublicRead(1,1)['status']==='CANDIDATE', 'Initial candidate missing');
    $repo->rows[0]['sources'][0]['status']='withdrawn'; $before=serialize($repo->rows);
    publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'DENY'] && count($repo->calls)===2 && serialize($repo->rows)===$before, 'Withdrawal not substantively assessed');
});
publicTest('correction expired review under HOLD causes DENY', function () {
    [$repo,$service] = publicFixture('hold'); $repo->rows[0]['approval']['review_due_at']='2029-12-31 23:59:59.999999';
    publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'DENY'] && count($repo->calls)===1, 'Expiry gate lost');
});
foreach (['not_assessed','not_eligible'] as $risk) {
    publicTest('correction risk '.$risk.' under HOLD causes DENY', function () use ($risk) {
        [$repo,$service,$snapshots] = publicFixture('hold'); $repo->rows[0]['protocol']['risk_eligibility']=$risk; publicSign($repo->rows[0],$snapshots);
        publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'DENY'] && count($repo->calls)===1, 'Risk gate lost');
    });
}
publicTest('correction unsupported evidence under HOLD causes DENY', function () {
    [$repo,$service,$snapshots] = publicFixture('hold'); $repo->rows[0]['protocol']['evidence_classification']='insufficient_evidence'; publicSign($repo->rows[0],$snapshots);
    publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'DENY'], 'Evidence gate lost');
});
publicTest('correction included item failure under HOLD denies entire protocol', function () {
    [$repo,$service,$snapshots] = publicFixture('hold'); $repo->rows[0]['items'][0]['instructions_en']=''; publicSign($repo->rows[0],$snapshots);
    publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'DENY'], 'Invalid included item omitted');
});
publicTest('correction approval hash mismatch under HOLD causes DENY', function () {
    [$repo,$service] = publicFixture('hold'); $repo->rows[0]['approval']['content_hash']=str_repeat('0',64);
    publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'DENY'], 'Hash integrity weakened');
});
publicTest('correction approval revision mismatch under HOLD causes DENY', function () {
    [$repo,$service] = publicFixture('hold'); $repo->rows[0]['approval']['content_revision']=2;
    publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'DENY'], 'Approval revision gate lost');
});
publicTest('correction assessment never consults exposure mode', function () {
    [$repo,,$snapshots] = publicFixture('hold');
    $policy = new class extends App\Services\TreatmentGovernancePolicy {
        public function mode(mixed $settings=null): string { throw new AssertionError('Exposure mode consulted by assessment'); }
    };
    $service = new App\Services\TreatmentPublicationService($repo,new PublicReadClock(),$snapshots,$policy);
    publicCheck($service->assessProtocolForPublicRead(1,1)===['status'=>'CANDIDATE'], 'Mode dependency remains');
});
publicTest('correction HOLD assessment propagates retrieval and programming failures', function () {
    [$repo,$service] = publicFixture('hold');
    foreach ([new RuntimeException('Safe retrieval failure'),new AssertionError('Programming sentinel')] as $failure) {
        $repo->failure=$failure;
        try { $service->assessProtocolForPublicRead(1,1); throw new LogicException('Failure swallowed'); }
        catch (Throwable $actual) { publicCheck($actual===$failure,'Failure converted to DENY'); }
    }
});
publicTest('correction invalid assessment context denies before querying', function () {
    [$repo,$service] = publicFixture('hold');
    foreach ([[0,1,'en'],[1,0,'en'],[1,1,'fr']] as $args) {
        publicCheck($service->assessProtocolForPublicRead(...$args)===['status'=>'DENY'], 'Invalid context allowed');
    }
    publicCheck($repo->calls===[], 'Invalid context queried');
});
$correctionPassed = $publicPassed - $correctionBaseline;
echo "Publication correction: $correctionPassed new tests passed; $correctionBaseline existing publication tests passed; no database/session/log writes.\n";

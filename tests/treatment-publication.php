<?php

declare(strict_types=1);

// Synthetic persisted aggregates only. No bootstrap, database, session or logs.
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require_once dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php';
    }
});
function config(?string $key = null): mixed { return ['publication_mode'=>'hold']; }

use App\Repositories\TreatmentPublicationRepository;
use App\Services\TreatmentPublicationService;
use App\Services\TreatmentGovernanceRules;
use App\Services\TreatmentGovernanceSnapshot;
use App\Services\TreatmentGovernancePolicy;

final class PublicReadMemoryRepository extends TreatmentPublicationRepository
{
    public array $rows = []; public array $calls = []; public ?Throwable $failure = null;
    public function __construct() {}
    public function candidates(int $diseaseId, ?int $protocolId = null): array {
        $this->calls[] = [$diseaseId, $protocolId];
        if ($this->failure) { throw $this->failure; }
        return $this->rows;
    }
}
final class PublicReadClock extends TreatmentGovernanceRules
{
    public function now(): string { return '2030-01-01 00:00:00.000000'; }
}
final class PublicReadPolicy extends TreatmentGovernancePolicy
{
    public function __construct(private string $testMode = 'governed') {}
    public function mode(mixed $settings = null): string { return $this->testMode; }
}
function publicFixture(string $mode = 'governed'): array {
    $rules = new PublicReadClock(); $snapshots = new TreatmentGovernanceSnapshot($rules);
    $repo = new PublicReadMemoryRepository();
    $p = array_fill_keys(TreatmentGovernanceRules::PROTOCOL_CONTENT, null);
    $p = array_replace($p, ['id'=>1,'disease_id'=>1,'treatment_system_id'=>1,'content_revision'=>3,
        'review_state'=>'approved','publication_status'=>'published','approved_review_id'=>10,'published_locales'=>'en',
        'evidence_classification'=>'evidence_supported','risk_eligibility'=>'eligible','content_purpose'=>'clinical_management']);
    foreach (['en','hi'] as $locale) { foreach (['title','overview','applicability','limitations','evidence_summary'] as $key) { $p[$key.'_'.$locale] = $key.' '.$locale; } }
    $item = ['id'=>1,'protocol_id'=>1,'is_included'=>1,'sort_order'=>1,'item_type'=>'therapy',
        'content_purpose'=>'clinical_management','evidence_classification'=>'evidence_supported'];
    foreach (['en','hi'] as $locale) { foreach (['title','instructions','limitations'] as $key) { $item[$key.'_'.$locale] = $key.' '.$locale; } }
    $source = array_replace(array_fill_keys(TreatmentGovernanceRules::SOURCE_CONTENT, null), [
        'id'=>1,'source_type'=>'guideline','title'=>'Citation title','citation_text'=>'Bibliographic citation',
        'url'=>'https://example.org/reference','status'=>'verified','verified_by'=>17,'verified_at'=>'2029-01-01 00:00:00.000000',
        'created_by'=>16,'verification_secret'=>'must not project']);
    $a = ['protocol'=>$p,'disease'=>['id'=>1],'system'=>['id'=>1,'is_active'=>1,'name_en'=>'System','name_hi'=>'प्रणाली','slug'=>'system'],
        'items'=>[$item], 'sources'=>[$source],
        'protocol_sources'=>[['id'=>1,'protocol_id'=>1,'source_id'=>1,'claim_scope'=>'Protocol scope','locator'=>null]],
        'item_sources'=>[['id'=>1,'item_id'=>1,'source_id'=>1,'claim_scope'=>'Item scope','locator'=>'Page 2']],
        'unresolved_invalidation'=>0,
        'approval'=>['id'=>10,'protocol_id'=>1,'content_revision'=>3,'event_type'=>'approved','actor_id'=>17,
            'occurred_at'=>'2029-01-01 00:00:00.000000','review_due_at'=>'2031-01-01 00:00:00.000000',
            'locales'=>'en','independence_confirmed'=>1,'notes'=>'Internal rationale']];
    $a['approval'] = array_replace($a['approval'], $snapshots->capture($a, ['en']));
    $repo->rows = [$a];
    return [$repo, new TreatmentPublicationService($repo,$rules,$snapshots,new PublicReadPolicy($mode)), $snapshots];
}
function publicSign(array &$a, TreatmentGovernanceSnapshot $snapshots): void {
    $a['approval'] = array_replace($a['approval'], $snapshots->capture($a, explode(',', $a['approval']['locales'])));
}
$publicPassed = 0;
function publicCheck(bool $value, string $message): void { if (!$value) { throw new LogicException($message); } }
function publicTest(string $name, callable $test): void {
    global $publicPassed;
    $test(); ++$publicPassed; echo "PASS $name\n";
}
function publicDenied(string $name, callable $change, bool $resign = false, string $locale = 'en'): void {
    publicTest($name, function () use ($change,$resign,$locale) {
        [$repo,$service,$snapshots] = publicFixture(); $change($repo->rows[0]);
        if ($resign) { publicSign($repo->rows[0], $snapshots); }
        $before = serialize($repo->rows);
        publicCheck($service->getPublicTreatmentProjection(1,1,$locale) === null, 'Denied projection leaked');
        publicCheck(($service->assessProtocolForPublicRead(1,1,$locale)['status'] === 'DENY'), 'Denied assessment passed');
        publicCheck($service->getPublicTreatmentsForDisease(1,$locale) === [], 'Denied disease results leaked');
        publicCheck(serialize($repo->rows) === $before, 'Denied read mutated persisted data');
    });
}

foreach (['draft','pending_review','changes_required'] as $state) { publicDenied($state.' denied', fn(&$a) => $a['protocol']['review_state'] = $state); }
foreach (['unpublished','withdrawn','unknown'] as $state) { publicDenied($state.' denied', fn(&$a) => $a['protocol']['publication_status'] = $state); }
publicTest('published approved current eligible candidate', function () {
    [$repo,$service] = publicFixture(); $before=serialize($repo->rows);
    publicCheck(($service->assessProtocolForPublicRead(1,1)['status'] === 'CANDIDATE') && count($service->getPublicTreatmentsForDisease(1))===1, 'Candidate denied');
    publicCheck(serialize($repo->rows)===$before, 'Candidate read mutated');
});
publicDenied('missing approval pointer', fn(&$a) => $a['protocol']['approved_review_id'] = null);
publicDenied('missing approval event', fn(&$a) => $a['approval'] = null);
publicDenied('wrong approval pointer', fn(&$a) => $a['protocol']['approved_review_id'] = 99);
publicDenied('wrong approval event type', fn(&$a) => $a['approval']['event_type'] = 'submitted');
publicDenied('approval for another protocol', fn(&$a) => $a['approval']['protocol_id'] = 2);
publicDenied('approval revision mismatch', fn(&$a) => $a['approval']['content_revision'] = 2);
publicDenied('malformed approval JSON', function (&$a) { $a['approval']['content_snapshot']='{bad'; $a['approval']['content_hash']=hash('sha256','{bad'); });
publicDenied('scalar approval JSON', function (&$a) { $a['approval']['content_snapshot']='null'; $a['approval']['content_hash']=hash('sha256','null'); });
publicDenied('approval hash mismatch', fn(&$a) => $a['approval']['content_hash'] = str_repeat('a',64));
publicDenied('missing approval hash', fn(&$a) => $a['approval']['content_hash'] = null);
publicDenied('current content differs', fn(&$a) => $a['protocol']['overview_en'] = 'New unpublished content');
publicDenied('excluded content change also invalidates full approved snapshot', function (&$a) { $a['items'][0]['is_included']=0; });
publicDenied('unresolved governance invalidation', fn(&$a) => $a['unresolved_invalidation'] = 1);
publicDenied('missing invalidation assessment', fn(&$a) => $a['unresolved_invalidation'] = null);
publicDenied('expired review', fn(&$a) => $a['approval']['review_due_at'] = '2029-12-31 23:59:59.999999');
publicDenied('due-now review', fn(&$a) => $a['approval']['review_due_at'] = '2030-01-01 00:00:00.000000');
publicDenied('missing review deadline', fn(&$a) => $a['approval']['review_due_at'] = null);
publicDenied('future-dated approval', fn(&$a) => $a['approval']['occurred_at'] = '2030-06-01 00:00:00.000000');
publicDenied('missing approval occurrence date', fn(&$a) => $a['approval']['occurred_at'] = null);
publicDenied('invalid review deadline', fn(&$a) => $a['approval']['review_due_at'] = '2031-02-31 00:00:00.000000');
publicDenied('unconfirmed independent approval', fn(&$a) => $a['approval']['independence_confirmed'] = 0);
publicDenied('missing internal approval rationale', fn(&$a) => $a['approval']['notes'] = '');
publicDenied('inactive system', fn(&$a) => $a['system']['is_active'] = 0);
publicDenied('missing system', fn(&$a) => $a['system'] = null);
publicDenied('mismatched system', fn(&$a) => $a['system']['id'] = 2);
publicDenied('missing disease', fn(&$a) => $a['disease'] = null);
publicDenied('mismatched disease identity', fn(&$a) => $a['disease']['id'] = 2);
publicDenied('mismatched protocol disease', fn(&$a) => $a['protocol']['disease_id'] = 2);
publicDenied('missing protocol', fn(&$a) => $a['protocol'] = null);
foreach (['insufficient_evidence','evidence_does_not_support','not_assessed','unknown',''] as $evidence) {
    publicDenied('protocol evidence denied '.$evidence, fn(&$a) => $a['protocol']['evidence_classification'] = $evidence);
}
foreach (['not_eligible','not_assessed','unknown','',null] as $risk) {
    publicDenied('protocol risk denied '.json_encode($risk), fn(&$a) => $a['protocol']['risk_eligibility'] = $risk);
}
foreach (['title_en','overview_en','applicability_en','limitations_en'] as $field) { publicDenied('required protocol '.$field, fn(&$a) => $a['protocol'][$field] = '   ', true); }
publicDenied('missing purpose', fn(&$a) => $a['protocol']['content_purpose'] = null, true);
publicDenied('no included items', fn(&$a) => $a['items'][0]['is_included'] = 0, true);
publicDenied('invalid included item type', fn(&$a) => $a['items'][0]['item_type'] = 'unknown');
publicDenied('included item wrong protocol', fn(&$a) => $a['items'][0]['protocol_id'] = 2, true);
foreach (['title_en','instructions_en','limitations_en'] as $field) { publicDenied('required item '.$field, fn(&$a) => $a['items'][0][$field] = '', true); }
foreach (['insufficient_evidence','evidence_does_not_support','not_assessed','unknown'] as $evidence) {
    publicDenied('item evidence denied '.$evidence, fn(&$a) => $a['items'][0]['evidence_classification'] = $evidence);
}
publicDenied('invalid item purpose', fn(&$a) => $a['items'][0]['content_purpose'] = null, true);
publicDenied('missing protocol reference', fn(&$a) => $a['protocol_sources'] = [], true);
publicDenied('missing item reference', fn(&$a) => $a['item_sources'] = [], true);
publicDenied('missing directly referenced source', fn(&$a) => $a['sources'] = [], true);
foreach (['unverified','withdrawn','unknown'] as $status) { publicDenied('read-time source '.$status, fn(&$a) => $a['sources'][0]['status'] = $status); }
publicDenied('missing source verifier', fn(&$a) => $a['sources'][0]['verified_by'] = null);
publicDenied('missing source verification date', fn(&$a) => $a['sources'][0]['verified_at'] = null);
publicDenied('wrong protocol reference parent', fn(&$a) => $a['protocol_sources'][0]['protocol_id'] = 2, true);
publicDenied('wrong item reference parent', fn(&$a) => $a['item_sources'][0]['item_id'] = 2, true);
publicDenied('missing source claim scope', fn(&$a) => $a['protocol_sources'][0]['claim_scope'] = ' ', true);
foreach (['javascript:alert(1)','file:///secret','ftp://example.org/citation','not-a-url'] as $url) { publicDenied('unsafe citation URL '.$url, fn(&$a) => $a['sources'][0]['url'] = $url, true); }
publicDenied('approved but unpublished locale', function (&$a) { $a['approval']['locales']='en,hi'; }, true, 'hi');
publicDenied('published but unapproved locale', fn(&$a) => $a['protocol']['published_locales'] = 'en,hi', false, 'hi');
publicDenied('no unapproved English fallback for Hindi', fn(&$a) => $a['protocol']['title_hi'] = null, false, 'hi');
publicDenied('invalid stored locale', fn(&$a) => $a['protocol']['published_locales'] = 'en,fr');
publicTest('explicit approved published Hindi works without fallback', function () {
    [$repo,$service,$snapshots]=publicFixture(); $repo->rows[0]['approval']['locales']='hi'; $repo->rows[0]['protocol']['published_locales']='hi'; publicSign($repo->rows[0],$snapshots);
    publicCheck($service->getPublicTreatmentProjection(1,1,'hi')['title']==='title hi' && $service->getPublicTreatmentProjection(1,1,'en')===null,'Locale substitution');
});
publicTest('English can be published without Hindi', function () { [, $s]=publicFixture(); publicCheck(($s->assessProtocolForPublicRead(1,1,'en')['status'] === 'CANDIDATE') && ($s->assessProtocolForPublicRead(1,1,'hi')['status'] === 'DENY'),'English/Hindi policy'); });
foreach (['hold','unknown',''] as $mode) {
    publicTest('deployment mode blocks exposure independently '.$mode, function () use ($mode) { [$repo,$s]=publicFixture($mode); publicCheck($s->assessProtocolForPublicRead(1,1)===['status'=>'CANDIDATE'] && !$s->getPublicTreatmentsForDisease(1) && $s->getPublicTreatmentProjection(1,1)===null && count($repo->calls)===1,'Assessment mode coupling or exposure bypass'); });
}
publicTest('unknown requested locale and invalid IDs fail before retrieval', function () {
    [$repo,$s]=publicFixture(); publicCheck(!$s->getPublicTreatmentsForDisease(1,'fr') && !$s->getPublicTreatmentsForDisease(0) && $s->getPublicTreatmentProjection(0,1)===null && !$repo->calls,'Invalid request queried');
});
publicTest('cross-disease/protocol ID cannot retrieve projection', function () {
    [, $s]=publicFixture(); publicCheck($s->getPublicTreatmentProjection(1,2)===null && $s->getPublicTreatmentProjection(2,1)===null,'Protocol ID bypass');
});
publicTest('source withdrawal after approval immediately denies next read without a write', function () {
    [$repo,$s]=publicFixture(); publicCheck(($s->assessProtocolForPublicRead(1,1)['status'] === 'CANDIDATE'),'Initial candidate missing');
    $repo->rows[0]['sources'][0]['status']='withdrawn'; $before=serialize($repo->rows);
    publicCheck(($s->assessProtocolForPublicRead(1,1)['status'] === 'DENY') && serialize($repo->rows)===$before,'Read repaired source invalidation');
});
publicTest('projection has exact deliberate protocol item reference allowlists', function () {
    [$repo,$s]=publicFixture(); $a=&$repo->rows[0];
    foreach (['reviewer_identity','permissions','user','health_profile','preferences','internal_review_rationale','secret'] as $key) { $a[$key]='private'; $a['protocol'][$key]='private'; $a['items'][0][$key]='private'; $a['sources'][0][$key]='private'; }
    $p=$s->getPublicTreatmentProjection(1,1);
    publicCheck(array_keys($p)===['protocol_id','disease_id','treatment_system_id','treatment_system_name','treatment_system_slug','locale','title','overview','content_purpose','evidence_classification','evidence_summary','applicability','limitations','included_items','references'],'Protocol allowlist changed');
    publicCheck(array_keys($p['included_items'][0])===['item_id','item_type','title','instructions','limitations','references'],'Item allowlist changed');
    publicCheck(array_keys($p['references'][0])===['reference_id','source_id','claim_scope','locator','source_type','title','author_or_organization','publication','citation_text','url','identifier','publication_date','edition_or_version','accessed_on'],'Citation allowlist changed');
    $encoded=json_encode($p,JSON_THROW_ON_ERROR);
    foreach (['reviewer_identity','permissions','internal_review_rationale','content_snapshot','content_hash','actor_id','verified_by','verification_secret','health_profile','preferences','private'] as $forbidden) { publicCheck(!str_contains($encoded,$forbidden),'Private field leaked '.$forbidden); }
});
publicTest('excluded item and its references never projected', function () {
    [$repo,$s,$snapshots]=publicFixture(); $a=&$repo->rows[0]; $excluded=array_replace($a['items'][0],['id'=>2,'is_included'=>0,'sort_order'=>2,'title_en'=>'Excluded private item']);
    $a['items'][]=$excluded; $a['item_sources'][]=array_replace($a['item_sources'][0],['id'=>2,'item_id'=>2]); publicSign($a,$snapshots);
    $p=$s->getPublicTreatmentProjection(1,1); publicCheck(count($p['included_items'])===1 && !str_contains(json_encode($p),'Excluded'),'Excluded item leak');
});
publicTest('one invalid included item rejects whole protocol', function () {
    [$repo,$s,$snapshots]=publicFixture(); $a=&$repo->rows[0]; $a['items'][]=array_replace($a['items'][0],['id'=>2,'sort_order'=>2,'instructions_en'=>'']); publicSign($a,$snapshots);
    publicCheck($s->getPublicTreatmentProjection(1,1)===null,'Invalid item silently omitted');
});
publicTest('systems protocols items and citations have deterministic ordering', function () {
    [$repo,$s,$snapshots]=publicFixture(); $a=&$repo->rows[0];
    $a['items'][]=array_replace($a['items'][0],['id'=>2,'sort_order'=>2]); $a['item_sources'][]=array_replace($a['item_sources'][0],['id'=>2,'item_id'=>2]);
    $a['sources'][]=array_replace($a['sources'][0],['id'=>2]); $a['protocol_sources'][]=array_replace($a['protocol_sources'][0],['id'=>2,'source_id'=>2]);
    $a['items']=array_reverse($a['items']); $a['protocol_sources']=array_reverse($a['protocol_sources']); publicSign($a,$snapshots);
    $b=$a; $b['protocol']['id']=2; $b['protocol']['treatment_system_id']=2; $b['system']['id']=2; $b['approval']['protocol_id']=2;
    foreach ($b['items'] as &$item) { $item['protocol_id']=2; } unset($item);
    foreach ($b['protocol_sources'] as &$ref) { $ref['protocol_id']=2; } unset($ref); publicSign($b,$snapshots);
    $repo->rows=[$b,$a]; $p=$s->getPublicTreatmentsForDisease(1);
    publicCheck(array_column($p,'treatment_system_id')===[1,2] && array_column($p[0]['included_items'],'item_id')===[1,2] && array_column($p[0]['references'],'reference_id')===[1,2],'Non-deterministic order');
});
publicTest('duplicate included sort order denied under existing governance rules', function () {
    [$repo,$s,$snapshots]=publicFixture(); $a=&$repo->rows[0]; $a['items'][]=array_replace($a['items'][0],['id'=>2]); publicSign($a,$snapshots); publicCheck(($s->assessProtocolForPublicRead(1,1)['status'] === 'DENY'),'Duplicate governed order allowed');
});
publicTest('retrieval errors and programming errors are not silently denied', function () {
    [$repo,$s]=publicFixture();
    foreach ([new RuntimeException('Safe retrieval failure'), new AssertionError('Programming sentinel')] as $failure) {
        $repo->failure=$failure;
        try { $s->getPublicTreatmentsForDisease(1); throw new LogicException('Error swallowed'); } catch (Throwable $actual) { publicCheck($actual===$failure,'Error changed'); }
    }
});
publicTest('legacy disease detail stays separate from governed comparison under HOLD and governed policy', function () {
    foreach (['hold','governed'] as $mode) {
        [$repo,$publication]=publicFixture($mode); $reflection=new ReflectionClass(App\Services\DiseaseService::class); $d=$reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('diseaseReferenceService')->setValue($d,new App\Services\DiseaseReferenceService());
        $reflection->getProperty('treatmentPublicationService')->setValue($d,$publication);
        $reflection->getProperty('comparisonBuilder')->setValue($d,new App\Algorithms\DiseaseComparisonBuilder());
        $reflection->getProperty('repository')->setValue($d,new class extends App\Repositories\DiseaseRepository {
            public function __construct() {}
            public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array { return [['id'=>99,'treatment_system_id'=>1,'title_en'=>'Legacy fixture','title_hi'=>'पुराना विवरण','overview_en'=>'Existing overview','overview_hi'=>'पुराना विवरण']]; }
            public function findKnowledgeBySlug(string $slug,string $language='en'): ?array { return $slug==='missing' ? null : ['disease'=>['disease_id'=>1,'slug'=>$slug],'content'=>['overview_en'=>'Preserved'],'symptoms'=>[],'causes'=>[],'treatments'=>[['draft_secret'=>'raw row']]]; }
        });
        $disease=$d->getKnowledgeBySlug('left'); $comparison=$d->getComparison('left','right');
        publicCheck($disease['treatments'][0]['id']===99 && $comparison['left']['treatments']===$comparison['right']['treatments'],'Legacy/public separation failed');
        publicCheck(count($comparison['left']['treatments'])===($mode==='hold' ? 0 : 1) && !str_contains(json_encode($comparison),'draft_secret') && !str_contains(json_encode($comparison),'Legacy fixture'),'Raw leak/hold bypass');
        publicCheck($disease['content']['overview_en']==='Preserved' && $d->getComparison('left','missing')===null,'Unrelated disease behavior changed');
        if ($mode==='hold') { publicCheck(!$repo->calls,'HOLD queried treatments'); }
        publicCheck($d->getKnowledgeBySlug('left','hi')['treatments'][0]['title_hi']==='पुराना विवरण' && $publication->getPublicTreatmentsForDisease(1,'hi')===[],'Legacy Hindi/public locale separation failed');
    }
});
publicTest('legacy repository read is explicitly scoped to disease detail', function () {
    $source=file_get_contents(dirname(__DIR__).'/app/Repositories/DiseaseRepository.php');
    publicCheck(str_contains($source,'findLegacyTreatmentsForDiseaseDetail') && str_contains($source,'WHERE tp.disease_id = :id') && str_contains($source,"'treatments' => []"),'Unscoped legacy query or aggregate leak');
});
publicTest('preferences reorder only eligible projections and cannot grant access', function () {
    [$repo,$publication,$snapshots]=publicFixture(); $base=$repo->rows[0];
    foreach ([2,3] as $id) {
        $a=$base; $a['protocol']['id']=$id; $a['protocol']['treatment_system_id']=$id;
        $a['protocol']['approved_review_id']=$id*10; $a['system']['id']=$id;
        $a['approval']['id']=$id*10; $a['approval']['protocol_id']=$id;
        foreach ($a['items'] as &$item) { $item['protocol_id']=$id; } unset($item);
        foreach ($a['protocol_sources'] as &$ref) { $ref['protocol_id']=$id; } unset($ref);
        publicSign($a,$snapshots);
        if ($id===3) { $a['sources'][0]['status']='withdrawn'; }
        $repo->rows[]=$a;
    }
    $reflection=new ReflectionClass(App\Services\DiseaseService::class); $d=$reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('diseaseReferenceService')->setValue($d,new App\Services\DiseaseReferenceService());
    $reflection->getProperty('treatmentPublicationService')->setValue($d,$publication);
    $reflection->getProperty('comparisonBuilder')->setValue($d,new App\Algorithms\DiseaseComparisonBuilder());
    $reflection->getProperty('treatmentPreferenceService')->setValue($d,new class extends App\Services\TreatmentPreferenceService {
        public function __construct() {}
        public function getDisplayOrder(): array { return [3,2,1]; }
    });
    $reflection->getProperty('repository')->setValue($d,new class extends App\Repositories\DiseaseRepository {
        public function __construct() {}
        public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array { return [['id'=>1,'treatment_system_id'=>1],['id'=>2,'treatment_system_id'=>2],['id'=>3,'treatment_system_id'=>3]]; }
        public function findKnowledgeBySlug(string $slug,string $language='en'): ?array { return ['disease'=>['id'=>1],'symptoms'=>[],'causes'=>[]]; }
    });
    publicCheck(array_column($d->getKnowledgeBySlug('disease')['treatments'],'treatment_system_id')===[3,2,1],'Legacy preference order changed');
    publicCheck(array_column($d->getComparison('left','right')['left']['treatments'],'treatment_system_id')===[2,1],'Comparison preference boundary diverged');
});
publicTest('protocol IDs provide deterministic tie-break within one system', function () {
    [$repo,$s,$snapshots]=publicFixture(); $a=$repo->rows[0]; $a['protocol']['id']=2; $a['protocol']['approved_review_id']=20;
    $a['approval']['id']=20; $a['approval']['protocol_id']=2; $a['items'][0]['protocol_id']=2; $a['protocol_sources'][0]['protocol_id']=2; publicSign($a,$snapshots);
    array_unshift($repo->rows,$a); publicCheck(array_column($s->getPublicTreatmentsForDisease(1),'protocol_id')===[1,2],'Protocol tie-break unstable');
});
// Exercise actual batched repository SQL/transaction orchestration without a connection.
final class PublicReadPDO extends PDO
{
    public bool $open = false; public string $isolation = 'REPEATABLE-READ';
    public int $commits = 0, $rollbacks = 0; public array $queries = [], $snapshot = [];
    public ?Throwable $failure = null; public bool $withdrawDuringRead = false;
    public int $candidateCount = 1;
    public function __construct(public array $aggregate) {}
    public function inTransaction(): bool { return $this->open; }
    public function prepare(string $query, array $options = []): PDOStatement|false { return new PublicReadStatement($this,$query); }
    public function commit(): bool { ++$this->commits; $this->open=false; return true; }
    public function rollBack(): bool { ++$this->rollbacks; $this->open=false; return true; }
    public function rows(string $query,array $params): array {
        $this->queries[]=['sql'=>$query,'params'=>$params];
        if (str_contains($query,'@@tx_isolation')) { return [['isolation_level'=>$this->isolation]]; }
        if (str_starts_with($query,'START TRANSACTION')) { $this->open=true; $this->snapshot=$this->aggregate; return []; }
        if ($this->failure) { throw $this->failure; }
        $a=$this->snapshot;
        if (str_starts_with($query,'SELECT p.*')) {
            if ($this->withdrawDuringRead) { $this->aggregate['sources'][0]['status']='withdrawn'; }
            $p=$a['protocol'];
            if ($p['review_state']!=='approved' || $p['publication_status']!=='published' || $p['disease_id']!==$params['disease_id'] || (isset($params['protocol_id']) && $p['id']!==$params['protocol_id'])) { return []; }
            $joined = array_merge($p,['existing_disease_id'=>$a['disease']['id'],'existing_system_id'=>$a['system']['id'],
                'system_active'=>$a['system']['is_active'],'system_name_en'=>$a['system']['name_en'],
                'system_name_hi'=>$a['system']['name_hi'],'system_slug'=>$a['system']['slug'],
                'unresolved_invalidation'=>$a['unresolved_invalidation']]);
            $rows=[];
            for ($id=1; $id<=$this->candidateCount; ++$id) { $rows[]=array_replace($joined,['id'=>$id]); }
            return $rows;
        }
        foreach (['treatment_protocol_items'=>'items','treatment_protocol_sources'=>'protocol_sources','treatment_item_sources'=>'item_sources','treatment_sources'=>'sources','treatment_protocol_events'=>'approval'] as $table=>$key) {
            if (str_contains($query,'FROM '.$table.' ')) { return $key==='approval' ? [$a[$key]] : $a[$key]; }
        }
        throw new AssertionError('Unexpected repository SQL');
    }
}
final class PublicReadStatement extends PDOStatement
{
    private array $rows = [];
    public function __construct(private PublicReadPDO $pdo,private string $sql) {}
    public function execute(?array $params = null): bool { $this->rows=$this->pdo->rows($this->sql,$params??[]); return true; }
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0): mixed { return $this->rows[0]??false; }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args): array { return $this->rows; }
}
function realPublicRepository(PublicReadPDO $pdo): TreatmentPublicationRepository {
    $reflection=new ReflectionClass(App\Core\Database::class); $database=$reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($database,$pdo);
    return new TreatmentPublicationRepository($database);
}
publicTest('actual repository batches aggregate in read-only consistent snapshot with bound IDs', function () {
    [$memory]=publicFixture(); $pdo=new PublicReadPDO($memory->rows[0]); $repository=realPublicRepository($pdo);
    $rows=$repository->candidates(1,1); publicCheck(count($rows)===1 && $rows[0]['approval']['id']===10 && count($rows[0]['items'])===1,'Aggregate incomplete');
    publicCheck(count($pdo->queries)===8 && $pdo->queries[1]['sql']==='START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY' && $pdo->commits===1 && !$pdo->open,'Transaction/query count changed');
    foreach ($pdo->queries as $query) { publicCheck(!preg_match('/\b(INSERT|UPDATE|DELETE|REPLACE|ALTER|SET|FOR UPDATE)\b/i',$query['sql']),'Write or lock on public read'); }
    publicCheck($pdo->queries[2]['params']===['disease_id'=>1,'protocol_id'=>1],'Protocol identifiers not bound');
});
publicTest('actual repository has constant query count with many candidate protocols', function () {
    [$memory]=publicFixture(); $pdo=new PublicReadPDO($memory->rows[0]); $pdo->candidateCount=50;
    $rows=realPublicRepository($pdo)->candidates(1);
    publicCheck(count($rows)===50 && count($pdo->queries)===8,'Query count grows with protocol count');
    publicCheck(count($pdo->queries[3]['params'])===50 && count($pdo->queries[4]['params'])===50,'Child queries were not batched');
});
publicTest('actual repository refuses unsupported isolation and nesting', function () {
    [$memory]=publicFixture(); $pdo=new PublicReadPDO($memory->rows[0]); $r=realPublicRepository($pdo);
    $pdo->isolation='READ-COMMITTED'; try { $r->candidates(1); throw new LogicException('Unsafe isolation accepted'); } catch (RuntimeException $e) { publicCheck(!$pdo->open && count($pdo->queries)===1,'Unsafe isolation changed transaction'); }
    $pdo->open=true; try { $r->candidates(1); throw new LogicException('Nested read accepted'); } catch (RuntimeException $e) { publicCheck($pdo->open && !$pdo->rollbacks,'Caller transaction disturbed'); }
});
publicTest('actual repository rolls back database failure and preserves safe error', function () {
    [$memory]=publicFixture(); $pdo=new PublicReadPDO($memory->rows[0]); $r=realPublicRepository($pdo); $pdo->failure=new PDOException('Private SQL/path detail');
    try { $r->candidates(1); throw new LogicException('Database failure swallowed'); } catch (RuntimeException $e) { publicCheck(!str_contains($e->getMessage(),'Private') && $e->getPrevious()===$pdo->failure && $pdo->rollbacks===1 && !$pdo->open,'Unsafe database failure'); }
});
publicTest('actual repository rolls back and rethrows programming errors', function () {
    [$memory]=publicFixture(); $pdo=new PublicReadPDO($memory->rows[0]); $r=realPublicRepository($pdo); $pdo->failure=new AssertionError('Read sentinel');
    try { $r->candidates(1); throw new LogicException('Assertion swallowed'); } catch (AssertionError $e) { publicCheck($e===$pdo->failure && $pdo->rollbacks===1 && !$pdo->open,'Assertion not preserved'); }
});
publicTest('consistent snapshot avoids mixed source aggregate and next read sees invalidation', function () {
    [$memory,,$snapshots]=publicFixture(); $pdo=new PublicReadPDO($memory->rows[0]); $pdo->withdrawDuringRead=true;
    $rules=new PublicReadClock(); $s=new TreatmentPublicationService(realPublicRepository($pdo),$rules,$snapshots,new PublicReadPolicy());
    publicCheck(($s->assessProtocolForPublicRead(1,1)['status'] === 'CANDIDATE'),'Mixed snapshot caused denial');
    publicCheck(($s->assessProtocolForPublicRead(1,1)['status'] === 'DENY') && $pdo->commits===2 && !$pdo->open,'Next read failed to see withdrawal');
});
echo "Treatment publication: $publicPassed tests passed; no database/session/log writes.\n";

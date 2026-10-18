<?php

declare(strict_types=1);

// Database-free manifest, service, read-path and production-view verification.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

use App\Services\DiseaseReferenceService;
use App\Services\DiseaseService;
use App\Repositories\DiseaseRepository;

$checks = 0;
function checkReferences(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
class ReferenceFixture extends DiseaseReferenceService
{
    public function __construct(private array $fixture) {}
    protected function loadManifest(): array { return $this->fixture; }
}

$root = dirname(__DIR__);
$manifest = require $root . '/resources/disease-references.php';
$report = file_get_contents($root . '/docs/task-41a-disease-reference-mapping.md');
$verifiedTable = explode('## D.', explode('## C.', $report)[1])[0];
$expected = [];
foreach (explode("\n", $verifiedTable) as $line) {
    if (!preg_match('/^\| [^|]+ \| \d+ \|/', $line)) { continue; }
    $cells = array_map('trim', array_slice(explode('|', $line), 1, -1));
    preg_match('/^\[(.*)\]\((.*)\)$/', $cells[5], $link);
    $key = $cells[1] . ':' . $cells[2];
    $expected[$key] ??= ['disease_id' => (int) $cells[1], 'section' => $cells[2], 'sources' => []];
    $expected[$key]['sources'][] = ['organization' => $cells[4], 'title' => $link[1], 'url' => $link[2], 'date' => $cells[6], 'date_type' => $cells[7]];
}
checkReferences(count($manifest) === 38 && $manifest === array_values($expected), 'Manifest differs from exact approved section/source metadata');
checkReferences(count(array_unique(array_column($manifest, 'disease_id'))) === 8, 'Wrong disease scope');
checkReferences(count(array_unique(array_column($manifest, 'section'))) === 5, 'Wrong section scope');
checkReferences(array_sum(array_map(fn($m) => count($m['sources']), $manifest)) === 57, 'Wrong source-link count');
$service = new DiseaseReferenceService();
checkReferences($service->getForSection(1, 'overview') === [], 'Migraine Overview attribution');
checkReferences($service->getForSection(201, 'overview') === [], 'Acne Overview attribution');
checkReferences($service->getForSection(999, 'causes') === [] && $service->getForSection(-1, 'causes') === [], 'Out-of-scope disease');
checkReferences($service->getForSection(1, 'treatment') === [] && $service->getForSection(1, 'risk-factors') === [], 'Invalid section');
foreach ($manifest as $m) {
    $expectedPublic = array_map(function ($source) {
        $public = array_intersect_key($source, array_flip(['organization', 'title', 'url']));
        if (!str_starts_with($source['date_type'], 'Displayed')) {
            $public['date'] = $source['date']; $public['date_type'] = $source['date_type'];
        }
        return $public;
    }, $m['sources']);
    checkReferences($service->getForSection($m['disease_id'], $m['section']) === $expectedPublic, 'Incorrect public projection');
}
checkReferences($service->getForSection(364, 'causes')[0]['url'] === 'https://www.cdc.gov/diabetes/about/diabetic-ketoacidosis.html', 'Original CDC link changed');
$fixture = ['disease_id' => 120, 'section' => 'overview', 'sources' => [['organization' => 'Org <script>', 'title' => 'Title <script>', 'url' => 'https://example.org/?a=1&b=2', 'internal_reviewer' => 'PRIVATE']]];
$public = (new ReferenceFixture([$fixture]))->getForSection(120, 'overview');
checkReferences(array_keys($public[0]) === ['organization', 'title', 'url'], 'Missing dates invented or internal metadata exposed');
foreach (['javascript:alert(1)', 'data:text/html,bad', 'file:///private', 'ftp://example.org', '/relative', 'not-a-url'] as $url) {
    $bad = $fixture; $bad['sources'][0]['url'] = $url;
    try { (new ReferenceFixture([$bad]))->getForSection(120, 'overview'); throw new RuntimeException('Unsafe URL accepted'); }
    catch (UnexpectedValueException) { $checks++; }
}
foreach (['id', 'section', 'title', 'organization', 'date_type', 'unmapped', 'duplicate'] as $invalid) {
    $bad = $fixture;
    if ($invalid === 'id') { $bad['disease_id'] = '120'; }
    if ($invalid === 'section') { $bad['section'] = 'faqs'; }
    if (in_array($invalid, ['title', 'organization'], true)) { $bad['sources'][0][$invalid] = ' '; }
    if ($invalid === 'date_type') { $bad['sources'][0]['date_type'] = 'KOBI review'; }
    if ($invalid === 'unmapped') { $bad['disease_id'] = 1; }
    $rows = $invalid === 'duplicate' ? [$bad, $bad] : [$bad];
    try { (new ReferenceFixture($rows))->getForSection(120, 'overview'); throw new RuntimeException('Invalid metadata accepted'); }
    catch (UnexpectedValueException) { $checks++; }
}

class ReferenceDiseaseRepository extends DiseaseRepository
{
    public array $calls = [];
    public function __construct() {}
    public function findKnowledgeBySlug(string $slug, string $language = 'en'): ?array {
        $this->calls[] = 'knowledge';
        return ['disease' => ['disease_id' => 364], 'content' => ['overview_en' => 'Unchanged stored wording'], 'symptoms' => ['unchanged'], 'treatments' => []];
    }
    public function findLegacyTreatmentsForDiseaseDetail(int $diseaseId): array {
        $this->calls[] = 'legacy';
        return [['treatment_system_id' => 1, 'overview_en' => 'Unchanged legacy treatment']];
    }
}
$repo = new ReferenceDiseaseRepository();
$reflection = new ReflectionClass(DiseaseService::class);
$args = [];
foreach ($reflection->getConstructor()->getParameters() as $parameter) {
    $class = $parameter->getType()->getName();
    $args[] = $class === DiseaseRepository::class ? $repo : ($class === DiseaseReferenceService::class ? $service : (new ReflectionClass($class))->newInstanceWithoutConstructor());
}
$diseaseService = $reflection->newInstanceArgs($args);
$read = $diseaseService->getKnowledgeBySlug('synthetic');
checkReferences($read['content']['overview_en'] === 'Unchanged stored wording' && $read['symptoms'] === ['unchanged'], 'Existing knowledge altered');
checkReferences($read['treatments'][0]['overview_en'] === 'Unchanged legacy treatment' && $repo->calls === ['knowledge', 'legacy'], 'Treatment read path or query count altered');
checkReferences($read['section_references']['causes'] === $service->getForSection(364, 'causes'), 'Read-path references missing');
checkReferences($diseaseService->getKnowledgeBySlug('synthetic', 'hi')['section_references'] === [], 'English references attributed to Hindi');

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return 'https://kobi.example.invalid' . $path; }
function csrfField(): string { return '<input type="hidden" value="synthetic">'; }
function buildPreventionTips(string $text): array { return [$text]; }
$fixture['sources'][0]['date'] = '<unsafe date>';
$fixture['sources'][0]['date_type'] = 'Reviewed';
$knowledge = ['disease' => ['disease_id' => 120, 'disease_en' => 'Synthetic disease', 'slug' => 'synthetic'],
    'content' => array_fill_keys(['overview_en', 'causes_en', 'risk_factors_en', 'diagnosis_en', 'prevention_en'], 'Stored section <unsafe>'),
    'symptoms' => [], 'causes' => [], 'treatments' => [['treatment_system_id' => 1, 'title_en' => 'Legacy treatment', 'overview_en' => 'Stored legacy treatment']],
    'diet' => ['recommended' => [], 'avoid' => []], 'faqs' => [],
    'section_references' => ['overview' => (new ReferenceFixture([$fixture]))->getForSection(120, 'overview'), 'treatment' => $public]];
$relatedDiseases = [];
ob_start(); require $root . '/app/Views/disease/show.php'; $html = ob_get_clean();
libxml_use_internal_errors(true); $dom = new DOMDocument(); $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html); $xpath = new DOMXPath($dom);
checkReferences($xpath->query('//aside[contains(@aria-labelledby,"-sources")]')->length === 1, 'Duplicate or unrelated reference block');
checkReferences($xpath->query('//section[@id="treatment"]//aside')->length === 0 && str_contains($html, 'Stored legacy treatment'), 'Treatment display/reference boundary changed');
checkReferences(!str_contains($html, '<script>') && str_contains($html, 'Org &lt;script&gt;') && str_contains($html, '&lt;unsafe date&gt;'), 'Reference escaping failed');
checkReferences(str_contains($html, 'a=1&amp;b=2') && str_contains($html, 'rel="noopener noreferrer"'), 'External link safety changed');
checkReferences($xpath->query('//section[@id="diagnosis"]')->length === 1 && str_contains($html, '#diagnosis'), 'Diagnosis/navigation changed');
checkReferences(!str_contains($html, 'PRIVATE') && !str_contains($html, 'Source unavailable'), 'Internal metadata or empty fallback exposed');
echo "$checks database-free disease reference checks passed.\n";

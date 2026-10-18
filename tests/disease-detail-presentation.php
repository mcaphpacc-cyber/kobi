<?php
declare(strict_types=1);

// Render the production view with synthetic content; no bootstrap or database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cases = ['all', 'partial', 'overview', 'empty', 'whitespace', 'causes-text', 'risk-only'];
if (!isset($argv[1])) {
    foreach ($cases as $case) {
        $process = proc_open([PHP_BINARY, __FILE__, $case], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        if (proc_close($process) !== 0 || trim($output) !== 'PASS') {
            fwrite(STDERR, "FAIL $case: $error $output\n"); exit(1);
        }
    }
    echo "7 database-free Disease Detail presentation scenarios passed.\n"; exit;
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return 'https://kobi.example.invalid' . $path; }
function buildPreventionTips(string $text): array {
    return array_slice(array_filter(array_map('trim', preg_split('/[.;]\s*/', strip_tags($text)))), 0, 6);
}
function csrfField(): string { return '<input type="hidden" name="_csrf_token" value="synthetic">'; }
function verifyPresentation(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, $message . "\n"); exit(1); }
}
$case = $argv[1];
$content = [];
$knowledge = ['disease' => ['disease_id' => 1, 'disease_en' => 'Synthetic disease', 'slug' => 'synthetic'],
    'content' => [], 'symptoms' => [], 'causes' => [], 'treatments' => [], 'diet' => ['recommended' => [], 'avoid' => []], 'faqs' => []];
$relatedDiseases = [];
$expected = [];
if ($case === 'all' || $case === 'partial' || $case === 'overview') {
    $content['overview_en'] = 'Stored overview <script>unsafe</script>';
    $expected[] = 'overview';
}
if ($case === 'all' || $case === 'partial' || $case === 'risk-only') {
    $content['risk_factors_en'] = 'Stored risk wording'; $expected[] = 'risk-factors';
}
if ($case === 'all' || $case === 'causes-text') {
    $content['causes_en'] = "Stored cause wording\nUnchanged second line"; $expected[] = 'causes';
}
if ($case === 'all') {
    $knowledge['symptoms'] = [['symptom_en' => str_repeat('LongSymptom', 20)]];
    $knowledge['causes'] = [['cause_en' => 'Linked cause']];
    $content['diagnosis_en'] = 'Stored diagnosis'; $content['prevention_en'] = 'Stored prevention.';
    $knowledge['treatments'] = [['treatment_system_id' => 1, 'title_en' => 'Legacy treatment', 'overview_en' => 'Stored treatment']];
    $knowledge['diet']['recommended'] = [['item_en' => 'Stored diet']];
    $knowledge['faqs'] = [['question_en' => 'Stored question?', 'answer_en' => 'Stored answer']];
    $expected = ['overview', 'symptoms', 'causes', 'risk-factors', 'diagnosis', 'prevention', 'treatment', 'diet', 'faqs'];
}
if ($case === 'whitespace') {
    $content = array_fill_keys(['overview_en', 'causes_en', 'risk_factors_en', 'diagnosis_en', 'prevention_en'], " \n ");
}
$knowledge['content'] = $content;
ob_start(); require dirname(__DIR__) . '/app/Views/disease/show.php'; $html = ob_get_clean();
libxml_use_internal_errors(true);
$dom = new DOMDocument(); $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
$xpath = new DOMXPath($dom);
$ids = [];
foreach ($xpath->query('//*[@id]') as $node) {
    $id = $node->getAttribute('id');
    verifyPresentation(!isset($ids[$id]) && preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id), 'Invalid or duplicate ID');
    $ids[$id] = true;
}
$actual = [];
foreach ($xpath->query('//section[@id]') as $section) {
    $actual[] = $section->getAttribute('id');
    verifyPresentation($xpath->query('.//h2', $section)->length > 0, 'Major section lacks h2');
}
$links = [];
foreach ($xpath->query('//nav[@aria-label="On this page"]//a') as $link) {
    $id = substr($link->getAttribute('href'), 1); $links[] = $id;
    verifyPresentation(isset($ids[$id]) && trim($link->textContent) !== '', 'Navigation lacks meaningful target/name');
}
sort($actual); sort($expected); sort($links);
verifyPresentation($actual === $expected && $links === $expected, 'Sections and navigation differ from populated content');
verifyPresentation(!str_contains($html, '<script>unsafe') && str_contains($html, 'Synthetic disease'), 'Escaping/title changed');
foreach ($content as $key => $value) {
    if (trim($value) !== '') { verifyPresentation(str_contains($html, nl2br(e($value))), 'Stored wording missing or altered: ' . $key); }
}
if ($case === 'all') {
    verifyPresentation(str_contains($html, 'Legacy information') && str_contains($html, 'Stored treatment') && str_contains($html, 'data-bs-target="#collapse0"'), 'Legacy treatment display changed');
    verifyPresentation($xpath->query('//section[@id="faqs"]/div')->length === 1, 'FAQ structure invalid');
}
echo "PASS\n";

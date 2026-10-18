<?php
declare(strict_types=1);
// Reuse unchanged canonical summary, Dashboard and immutable-sharing regressions.
require __DIR__ . '/api-medical-summary.php';

class SummaryPolicyRows extends App\Repositories\HealthInsurancePolicyRepository
{
    public array $rows = [], $reads = [];
    public function __construct() {}
    public function findByProfileId(int $profileId): array { $this->reads[] = $profileId; return $this->rows[$profileId] ?? []; }
}
class SummaryPageController extends App\Controllers\AccountController
{
    public array $rendered = [];
    protected function view(string $view, array $data = []): void { $this->rendered = [$view, $data]; }
}

$checks = 0;
$auth->userId = 11; $auth->active = true;
$profiles->revoke = false; $profiles->revokeAtRead = null;
$profiles->rows = [2 => $profile(2), 99 => $profile(99, 'family', 'owner', 99)];
$conditions->rows = [2 => [
    ['id' => 1, 'condition_name' => 'Recorded active condition', 'status' => 'active', 'diagnosed_on' => '2024-01-02', 'doctor_hospital' => 'Recorded hospital'],
    ['id' => 2, 'condition_name' => 'Recorded resolved condition', 'status' => 'resolved', 'diagnosed_on' => '2020-01-02', 'resolved_on' => '2021-01-02'],
]];
$medicines->rows = [2 => [['id' => 1, 'brand_name' => 'Recorded medicine', 'generic_name' => 'Recorded generic', 'status' => 'current', 'strength' => '10 mg', 'dose' => 'Recorded dose', 'frequency' => 'Recorded frequency', 'route' => 'oral']]];
$allergies->rows = [2 => [['id' => 1, 'allergen_name' => 'Recorded allergen', 'category' => 'food', 'reaction' => 'Recorded reaction', 'severity' => 'mild']]];
$procedures->rows = [2 => [['id' => 1, 'procedure_name' => 'Recorded procedure', 'procedure_type' => 'surgery', 'performed_on' => '2018-01-02']]];
$policies = new SummaryPolicyRows();
$policies->rows = [2 => [[
    'id' => 75, 'health_profile_id' => 2, 'created_by' => 11,
    'provider_name' => 'Recorded <Insurer>', 'policy_name' => 'Recorded & Policy', 'policy_type' => 'family_floater',
    'status' => 'active', 'sum_insured' => '125000.50', 'currency_code' => 'INR', 'start_date' => '2024-01-02', 'expiry_date' => '2027-01-02',
    'policy_number' => 'PRIVATE POLICY NUMBER', 'general_notes' => 'PRIVATE NOTES', 'renewal_notes' => 'PRIVATE RENEWAL',
]]];
$unused = static fn (string $class): object => (new ReflectionClass($class))->newInstanceWithoutConstructor();
$insurance = new App\Services\HealthInsuranceService($policies,
    $unused(App\Repositories\HealthInsuranceMemberRepository::class), $unused(App\Repositories\HealthInsuranceContactRepository::class),
    $unused(App\Repositories\HealthInsuranceDocumentRepository::class), $unused(App\Repositories\HealthDocumentRepository::class), $profiles, $auth);
$controller = (new ReflectionClass(SummaryPageController::class))->newInstanceWithoutConstructor();
foreach (['authService' => $auth, 'medicalSummaryService' => $summary, 'healthInsuranceService' => $insurance] as $name => $dependency) {
    (new ReflectionProperty(App\Controllers\AccountController::class, $name))->setValue($controller, $dependency);
}
$before = serialize([$conditions->rows, $medicines->rows, $allergies->rows, $procedures->rows, $policies->rows]);
$controller->medicalSummary(2);
$check($controller->rendered[0] === 'account/medical-summary', 'Existing browser view retained');
$data = $controller->rendered[1];
$check($data['summary'] === $summary->getSummary(2) && $data['insurancePolicies'] === $policies->rows[2] && $data['medicalSummaryStyles'] === true, 'Canonical summary unchanged, authorized insurance separate from snapshot');
$renderSummary = static function (array $data): string { extract($data); ob_start(); require dirname(__DIR__) . '/app/Views/account/medical-summary.php'; return ob_get_clean(); };
$html = $renderSummary($data);
foreach (['Patient Information', 'Active Medical Conditions', 'Current Medicines', 'Allergies', 'Surgeries &amp; Procedures', 'Historical / Resolved Conditions', 'Medical Timeline', 'Reports &amp; Documents', 'Insurance'] as $heading) {
    $check(str_contains($html, $heading), 'Existing section retained: ' . $heading);
}
foreach (['Recorded active condition', 'Recorded resolved condition', 'Recorded medicine', 'Recorded reaction', 'Recorded procedure', 'Recorded &lt;Insurer&gt;', 'Recorded &amp; Policy', 'Family Floater', '125,000.50', '02 Jan 2024', '02 Jan 2027'] as $value) {
    $check(str_contains($html, $value), 'Recorded value/escaping retained');
}
$check(!str_contains($html, 'PRIVATE') && !str_contains($html, 'health_profile_id') && !str_contains($html, 'created_by'), 'Insurance secrets/notes/ownership metadata omitted');
$check(!preg_match('/<style\b|\sstyle\s*=|\son\w+\s*=/i', $html), 'No inline style or script handlers');
$check(str_contains($html, 'table-responsive') && str_contains($html, 'medical-summary-patient'), 'Responsive table and patient layout');
foreach (['/2/timeline', '/2/documents', '/2/medical-summary/share', '/2/medical-summary/shares'] as $path) $check(str_contains($html, $path), 'Existing navigation retained');
$check(str_contains(preg_replace('/\s+/', ' ', strip_tags($html)), 'recorded by the health profile owner.'), 'Owner-recorded disclaimer retained');
$check($before === serialize([$conditions->rows, $medicines->rows, $allergies->rows, $procedures->rows, $policies->rows]), 'Summary rendering does not mutate recorded data');

$policies->rows[2][0]['start_date'] = null; $policies->rows[2][0]['expiry_date'] = null; $policies->rows[2][0]['sum_insured'] = null;
$controller->medicalSummary(2); $nullable = $renderSummary($controller->rendered[1]);
$check(substr_count($nullable, 'Not specified') === 3, 'Null insurance coverage/dates are not invented');
$profiles->rows[2]['role'] = 'viewer'; $controller->medicalSummary(2); $viewer = $renderSummary($controller->rendered[1]);
$check(str_contains($viewer, 'Recorded &lt;Insurer&gt;') && !str_contains($viewer, 'Share Medical Summary') && !str_contains($viewer, 'Manage Shared Links'), 'Existing viewer read and owner-only share controls retained');
$policies->rows = []; $controller->medicalSummary(2); $empty = $renderSummary($controller->rendered[1]);
$check(str_contains($empty, 'No insurance information has been recorded for this health profile.') && !str_contains($empty, 'module is implemented'), 'Real empty collection has clear empty state');
$reads = $policies->reads;
try { $controller->medicalSummary(99); throw new LogicException('Foreign profile accepted'); } catch (RuntimeException $e) { $check($e->getMessage() === 'Health profile not found.', 'Summary denies foreign profile'); }
try { $insurance->getPolicies(99); throw new LogicException('Foreign insurance accepted'); } catch (RuntimeException $e) { $check($e->getMessage() === 'Health profile not found.', 'Insurance independently authorizes profile'); }
$auth->userId = null;
try { $controller->medicalSummary(2); throw new LogicException('Anonymous accepted'); } catch (DashboardRedirect $e) { $check($e->getMessage() === '/login', 'Existing anonymous redirect'); }
$check($reads === $policies->reads, 'No policy lookup before authorization');
$auth->userId = 11; $profiles->rows[2]['role'] = 'owner';
$apiBefore = count($policies->reads);
$apiResult = (new App\Services\ApiMedicalSummaryService($summary))->detail(new App\Services\ApiAuthPrincipal(11, 1), '2');
$check(array_keys($apiResult) === ['profile', 'counts'] && count($policies->reads) === $apiBefore, 'API contract and reads do not expand to insurance');
$newShareRows = new SummaryShares(); $newShares = new App\Services\MedicalSummaryShareService($newShareRows, $summary, $profileService, $auth);
$issued = $newShares->createShare(2, 7); $frozen = json_decode($newShareRows->rows[$issued['id']]['snapshot'], true, 32, JSON_THROW_ON_ERROR);
$check(!isset($frozen['insurancePolicies']) && !isset($frozen['insurance']) && count($policies->reads) === $apiBefore, 'Sharing snapshot does not read/include insurance');
$check(abs(strtotime($issued['expires_at']) - time() - 7 * 86400) < 3, 'Existing seven-day expiry preserved');

function baseUrl(): string { return '/kobi/public'; }
function asset(string $path): string { return '/kobi/public/assets/' . $path; }
function config(string $key): string { return 'KOBI'; }
$renderHeader = static function (bool $enabled): string { $medicalSummaryStyles = $enabled; ob_start(); require dirname(__DIR__) . '/app/Views/layouts/header.php'; return ob_get_clean(); };
$check(str_contains($renderHeader(true), 'css/medical-summary.css?v=') && !str_contains($renderHeader(false), 'medical-summary.css'), 'Same-origin stylesheet only on summary page');
$bootstrap = file_get_contents(dirname(__DIR__) . '/bootstrap/app.php');
$check(str_contains($bootstrap, 'style-src \'self\';') && !str_contains($bootstrap, 'unsafe-inline'), 'Strict CSP unchanged');
$check(session_status() === PHP_SESSION_NONE, 'No session/database fixtures');
echo "Medical Summary presentation/insurance: $checks checks passed; in-memory fixtures only.\n";

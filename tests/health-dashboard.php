<?php

declare(strict_types=1);

// Database-free tests: repository constructors intentionally do not connect.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path): string { return '/kobi/public' . $path; }
class DashboardRedirect extends RuntimeException {}
function redirect(string $path): never { throw new DashboardRedirect($path); }

class DashboardAuth extends App\Services\AuthService
{
    public ?int $userId = 11;
    public bool $active = true;
    public function __construct() {}
    public function id(): ?int { return $this->userId; }
    public function check(): bool { return $this->userId !== null; }
    public function user(): ?array { return $this->active && $this->check() ? ['id' => $this->userId] : null; }
}
class DashboardProfiles extends App\Repositories\HealthProfileRepository
{
    public array $rows = [];
    public bool $revoke = false;
    public ?int $revokeAtRead = null;
    public int $reads = 0;
    public function __construct() {}
    public function findAccessibleByUser(int $userId): array {
        $this->reads++;
        return array_values(array_filter($this->rows, fn ($row) => $row['member_user_id'] === $userId));
    }
    public function findAccessible(int $profileId, int $userId): ?array {
        $this->reads++;
        return !$this->revoke && $this->reads !== $this->revokeAtRead && isset($this->rows[$profileId]) && $this->rows[$profileId]['member_user_id'] === $userId
            ? $this->rows[$profileId] : null;
    }
}
trait DashboardRecords
{
    public array $rows = [];
    public array $reads = [];
    public function __construct() {}
    private function records(int $id): array { $this->reads[] = $id; return $this->rows[$id] ?? []; }
}
class DashboardConditions extends App\Repositories\HealthConditionRepository {
    use DashboardRecords;
    public function findByProfile(int $healthProfileId): array { return $this->records($healthProfileId); }
}
class DashboardMedicines extends App\Repositories\HealthMedicineRepository {
    use DashboardRecords;
    public function findByProfileId(int $profileId): array { return $this->records($profileId); }
}
class DashboardAllergies extends App\Repositories\HealthAllergyRepository {
    use DashboardRecords;
    public function findByProfileId(int $profileId): array { return $this->records($profileId); }
}
class DashboardProcedures extends App\Repositories\HealthProcedureRepository {
    use DashboardRecords;
    public function findByProfileId(int $profileId): array { return $this->records($profileId); }
}
class DashboardDocuments extends App\Repositories\HealthDocumentRepository {
    use DashboardRecords;
    public function countByProfileId(int $profileId): int { return count($this->records($profileId)); }
    public function findByProfileId(int $profileId): array { throw new RuntimeException('Do not load document metadata.'); }
}
class DashboardSaved extends App\Repositories\UserSavedDiseaseRepository {
    use DashboardRecords;
    public function findSavedDiseasesByUser(int $userId): array { return $this->records($userId); }
}
class DashboardSession extends App\Core\Session {
    public function __construct(private DashboardAuth $auth) {}
    public function get(string $key, mixed $default = null): mixed { return $key === 'auth_user_id' ? $this->auth->id() : $default; }
}
class DashboardController extends App\Controllers\PersonalHealthDashboardController {
    public array $rendered = [];
    protected function view(string $view, array $data = []): void { $this->rendered = [$view, $data]; }
}

$auth = new DashboardAuth();
$profiles = new DashboardProfiles();
$profile = static fn (int $id, string $type = 'self', string $role = 'owner', int $user = 11): array => [
    'id' => $id, 'full_name' => '<script>private-name</script>', 'profile_type' => $type,
    'role' => $role, 'member_user_id' => $user, 'date_of_birth' => '1990-01-01', 'private_notes' => 'PRIVATE',
];
$profiles->rows = [2 => $profile(2), 3 => $profile(3, 'family'), 99 => $profile(99, 'self', 'owner', 99)];
$profileService = new App\Services\HealthProfileService($profiles, $auth);
$conditions = new DashboardConditions();
$medicines = new DashboardMedicines();
$allergies = new DashboardAllergies();
$procedures = new DashboardProcedures();
$documents = new DashboardDocuments();
$saved = new DashboardSaved();
$conditions->rows[2] = [['id' => 1, 'status' => 'active'], ['id' => 2, 'status' => 'chronic'], ['id' => 3, 'status' => 'resolved']];
$medicines->rows[2] = [['id' => 1, 'status' => 'current', 'notes' => 'PRIVATE'], ['id' => 2, 'status' => 'stopped'], ['id' => 3, 'status' => 'historical']];
$allergies->rows[2] = [['id' => 1]];
$procedures->rows[2] = [['id' => 1], ['id' => 2]];
$documents->rows[2] = [['storage_path' => 'PRIVATE']];
$saved->rows = [11 => [['id' => 1], ['id' => 2]], 99 => [['id' => 99]]];
$summary = new App\Services\MedicalSummaryService($conditions, $medicines, $allergies, $procedures, $profileService, new App\Services\ApiHealthProfileService($profiles));
$storage = (new ReflectionClass(App\Services\HealthDocumentStorageService::class))->newInstanceWithoutConstructor();
$documentService = new App\Services\HealthDocumentService($documents, $profileService, $storage, $auth);
$savedService = new App\Services\SavedDiseaseService($saved, new DashboardSession($auth));
$service = new App\Services\HealthDashboardService($auth, $profileService, $summary, $documentService, $savedService);
$checks = 0;
$check = static function (bool $pass, string $label) use (&$checks): void {
    if (!$pass) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
};
$render = static function (array $dashboard): string {
    ob_start();
    require dirname(__DIR__) . '/app/Views/account/health-dashboard.php';
    return ob_get_clean();
};
$data = $service->getDashboard();
$check($data['selected_profile']['id'] === 2, 'unique owned self default');
$check($data['counts'] === ['conditions' => 2, 'historical_conditions' => 1, 'allergies' => 1, 'procedures' => 2, 'current_medications' => 1, 'documents' => 1], 'all counts and current-only status');
$check($data['saved_disease_count'] === 2 && $saved->reads === [11], 'saved data scoped to account');
$check(count($data['profiles']) === 2, 'other user profile excluded');
$check(!str_contains(json_encode($data), 'PRIVATE') && !str_contains(json_encode($data), 'date_of_birth'), 'minimal private projection');
$check($profiles->reads === 3, 'single list and existing fresh summary/document access guards');
foreach ([$conditions, $medicines, $allergies, $procedures, $documents] as $repository) {
    $check($repository->reads === [2], 'one domain read for selected profile only');
}
$html = $render($data);
$check(substr_count($html, '<section ') === 6, 'exactly six areas');
$check(str_contains($html, '&lt;script&gt;private-name&lt;/script&gt;') && !str_contains($html, '<script>'), 'profile name escaped; no inline script');
foreach (['conditions', 'allergies', 'procedures', 'medicines', 'documents', 'medical-summary'] as $destination) {
    $check(str_contains($html, '/account/health-records/2/' . $destination), 'existing destination ' . $destination);
}
foreach (['99', '0', '-1', 'garbage', '1.2', ['2'], '92233720368547758080'] as $invalid) {
    $before = count($conditions->reads);
    $invalidData = $service->getDashboard($invalid);
    $check($invalidData['selected_profile'] === null && $invalidData['counts'] === null && $invalidData['selection_unavailable'], 'invalid/inaccessible selection denied');
    $check(count($conditions->reads) === $before, 'no clinical reads on invalid selection');
    $check(!str_contains($render($invalidData), '/health-records/2/'), 'no silent fallback links');
}
foreach (['owner', 'editor', 'viewer'] as $role) {
    $profiles->rows[3] = $profile(3, 'family', $role);
    $empty = $service->getDashboard('3');
    $check($empty['selected_profile']['role'] === $role && array_sum($empty['counts']) === 0, 'authorized role ' . $role);
    $emptyHtml = $render($empty);
    foreach (['No conditions entries recorded.', 'No allergies entries recorded.', 'No procedures entries recorded.', 'No current medications recorded.', 'No documents yet.', 'View medical summary'] as $text) {
        $check(str_contains($emptyHtml, $text), 'empty profile state ' . $text);
    }
}
$profiles->rows[2]['profile_type'] = 'family';
$check($service->getDashboard()['selected_profile'] === null, 'ambiguous profiles require chooser');
unset($profiles->rows[2]);
$check($service->getDashboard()['selected_profile']['id'] === 3, 'single accessible profile default');
$profiles->revoke = true;
$before = count($conditions->reads);
$revoked = $service->getDashboard('3');
$check($revoked['counts'] === null && $revoked['selected_profile'] === null && $revoked['selection_unavailable'], 'fresh access rejects revoked profile');
$check(count($conditions->reads) === $before, 'revocation prevents domain read');
$profiles->revoke = false;
$profiles->revokeAtRead = $profiles->reads + 3;
$before = count($documents->reads);
$revoked = $service->getDashboard('3');
$check($revoked['counts'] === null && $revoked['selected_profile'] === null, 'revocation at document guard discards partial summary');
$check(count($documents->reads) === $before, 'document guard denies count read after revocation');
$profiles->revokeAtRead = null;
$profiles->rows = [];
$saved->rows[11] = [];
$none = $service->getDashboard();
$check($none['counts'] === null && $none['selected_profile'] === null, 'no profile state');
$check(str_contains($render($none), 'Set up a health profile') && str_contains($render($none), 'No saved diseases yet.'), 'setup and saved empty state');
$controller = new DashboardController($auth, $service);
$_GET = [];
$controller->index();
$check($controller->rendered[0] === 'account/health-dashboard', 'authenticated controller rendering');
foreach ([false, true] as $anonymous) {
    $auth->userId = $anonymous ? null : 11;
    $auth->active = false;
    $before = $profiles->reads;
    try { $controller->index(); throw new RuntimeException('Anonymous render permitted'); }
    catch (DashboardRedirect $exception) { $check($exception->getMessage() === '/login', 'anonymous/inactive account redirects'); }
    $check($profiles->reads === $before, 'denied controller does not read health data');
}
try { $service->getDashboard(); throw new RuntimeException('Anonymous service permitted'); }
catch (RuntimeException $exception) { $check($exception->getMessage() === 'Authentication required.', 'service authentication guard'); }
$check(str_contains(file_get_contents(dirname(__DIR__) . '/app/Controllers/PersonalHealthDashboardController.php'), 'private, no-store'), 'private response cache control');
echo "PASS: {$checks} dashboard checks (in-memory; no database connection or writes).\n";

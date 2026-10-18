<?php

declare(strict_types=1);

// Reuse the database-free dashboard harness and run its unchanged regression checks.
require __DIR__ . '/health-dashboard.php';
$baselineChecks = $checks;
$keys = static fn (array $data): array => array_column($data['guidance'], 'key');
$reset = static function () use ($auth, $profiles, $profile, $conditions, $allergies, $procedures, $medicines, $documents, $saved): void {
    $auth->userId = 11;
    $auth->active = true;
    $profiles->revoke = false;
    $profiles->revokeAtRead = null;
    $profiles->rows = [2 => $profile(2), 99 => $profile(99, 'self', 'owner', 99)];
    $conditions->rows = [2 => [['id' => 1, 'status' => 'active']], 99 => []];
    $allergies->rows = [2 => [['id' => 1]], 99 => []];
    $procedures->rows = [2 => [['id' => 1]], 99 => []];
    $medicines->rows = [2 => [['id' => 1, 'status' => 'current']], 99 => []];
    $documents->rows = [2 => [['id' => 1]], 99 => []];
    $saved->rows = [11 => [['id' => 1]], 99 => []];
};
$reset();
$populated = $service->getDashboard();
$check($keys($populated) === ['summary'], 'populated state offers actual derived summary, not fabricated setup');
$check($populated['guidance'][0]['title'] === 'Your Medical Summary is available', 'summary availability without a freshness/completeness claim');
$check(!str_contains($render($populated), 'up to date'), 'no unjustified freshness claim');
$check(substr_count($render($populated), '<section ') === 6, 'six original areas intact alongside compact guidance');
$check(str_contains($render($populated), 'Suggested next steps'), 'guidance heading visible');

foreach ([['conditions', $conditions], ['allergies', $allergies], ['procedures', $procedures], ['medications', $medicines], ['documents', $documents]] as [$key, $repository]) {
    $reset();
    $repository->rows[2] = [];
    $data = $service->getDashboard();
    $check($keys($data) === [$key], 'missing state gives relevant ' . $key . ' action');
    $check(str_starts_with($data['guidance'][0]['path'], '/account/health-records/2/'), 'action targets selected authorized profile');
    $check(count($data['guidance']) <= 3, 'bounded guidance');
    $check($service->getDashboard()['guidance'] === $data['guidance'], 'stable ordering/content');
}
$reset();
$conditions->rows[2] = [['id' => 1, 'status' => 'resolved'], ['id' => 2, 'status' => 'historical']];
$check($keys($service->getDashboard()) === ['summary'], 'historical-only conditions are not an empty condition list');
$reset();
$medicines->rows[2] = [['id' => 1, 'status' => 'stopped'], ['id' => 2, 'status' => 'historical']];
$check($keys($service->getDashboard()) === ['medications'], 'historical medicine entries do not count as current');
$reset();
$saved->rows[11] = [];
$check($keys($service->getDashboard()) === ['saved_diseases'], 'account saved empty state offers reading action');
$check($service->getDashboard()['guidance'][0]['path'] === '/account/saved-diseases', 'saved action uses existing account destination');

// A fully empty selected profile has a derived summary, not a missing persisted summary.
$reset();
foreach ([$conditions, $allergies, $procedures, $medicines, $documents] as $repository) { $repository->rows[2] = []; }
$saved->rows[11] = [];
$empty = $service->getDashboard();
$check($keys($empty) === ['conditions', 'allergies', 'procedures'], 'core empty entries first; exactly three initial actions');
$check(str_contains($render($empty), 'View medical summary'), 'empty profile still has summary destination');
$check(!str_contains($render($empty), 'Create a Medical Summary'), 'no nonexistent summary-creation workflow');
$check(str_contains($empty['guidance'][0]['description'], 'if you want'), 'conditions are optional');
$check(str_contains($empty['guidance'][1]['description'], 'if you want'), 'allergies are optional');
$check(str_contains($empty['guidance'][2]['description'], 'if you want'), 'procedures are optional');
$check($service->getDashboard()['guidance'] === $empty['guidance'], 'empty state deterministic');
$conditions->rows[2] = [['id' => 1, 'status' => 'active']];
$allergies->rows[2] = [['id' => 1]];
$procedures->rows[2] = [['id' => 1]];
$check($keys($service->getDashboard()) === ['medications', 'documents', 'saved_diseases'], 'remaining empty-state priority deterministic');
$check(str_contains($service->getDashboard()['guidance'][0]['description'], 'If you take medications'), 'medications never presumed necessary');

foreach (['owner', 'editor', 'viewer'] as $role) {
    $reset();
    $profiles->rows[2]['role'] = $role;
    foreach ([$conditions, $allergies, $procedures, $medicines, $documents] as $repository) { $repository->rows[2] = []; }
    $data = $service->getDashboard();
    $check($keys($data) === ($role === 'viewer' ? ['summary'] : ['conditions', 'allergies', 'procedures']), 'role-appropriate guidance for ' . $role);
}
$reset();
$profiles->rows = [];
$noProfile = $service->getDashboard();
$check($keys($noProfile) === ['profile'] && $noProfile['guidance'][0]['path'] === '/account/health-records', 'no profile uses existing workflow without creating data');
$check($noProfile['counts'] === null, 'no manufactured profile counts');
$reset();
$profiles->rows[2]['profile_type'] = 'family';
$profiles->rows[3] = $profile(3, 'family');
$ambiguous = $service->getDashboard();
$check($keys($ambiguous) === ['profile'] && $ambiguous['guidance'][0]['title'] === 'Choose a health profile', 'ambiguous selection does not manufacture setup');

$reset();
foreach (['99', 'invalid', ['2']] as $invalid) {
    $data = $service->getDashboard($invalid);
    $check($keys($data) === ['profile'], 'no foreign-profile clinical guidance');
    $check(!str_contains(json_encode($data['guidance']), '/health-records/99'), 'foreign profile absent from destinations');
}
$profiles->revoke = true;
$check($keys($service->getDashboard('2')) === ['profile'], 'revoked profile guidance limited to selection');
$reset();

// The exact same read budget applies: guidance adds no repository reads or writes.
$profiles->reads = 0;
foreach ([$conditions, $allergies, $procedures, $medicines, $documents, $saved] as $repository) { $repository->reads = []; }
$data = $service->getDashboard();
$check($profiles->reads === 3, 'guidance adds no profile query');
foreach ([$conditions, $allergies, $procedures, $medicines, $documents] as $repository) {
    $check($repository->reads === [2], 'one selected-profile domain read');
}
$check($saved->reads === [11], 'one account-scoped saved read');

// Verify every emitted action against actual GET routes, without running the app.
$routes = file_get_contents(dirname(__DIR__) . '/routes/web.php');
$reset();
$allActions = [];
foreach ([['conditions', $conditions], ['allergies', $allergies], ['procedures', $procedures], ['medications', $medicines], ['documents', $documents]] as [$key, $repository]) {
    $reset(); $repository->rows[2] = [];
    $allActions[] = $service->getDashboard()['guidance'][0];
}
$reset(); $allActions[] = $service->getDashboard()['guidance'][0];
$saved->rows[11] = []; $allActions[] = $service->getDashboard()['guidance'][0];
$profiles->rows = []; $allActions[] = $service->getDashboard()['guidance'][0];
foreach ($allActions as $action) {
    $route = str_replace('/health-records/2/', '/health-records/{profileId}/', $action['path']);
    $check(preg_match('~router\(\)->get\(\s*' . preg_quote("'" . $route . "'", '~') . '~', $routes) === 1, 'existing GET destination ' . $action['key']);
    $check(preg_match('/diagnos|risk|health score|dosage|interaction|should see|up to date|incomplete|complete your/i', $action['title'] . ' ' . $action['description']) === 0, 'organizational wording only');
}
$reset();
$auth->userId = null;
$before = $profiles->reads;
try { $service->getDashboard(); throw new RuntimeException('Anonymous guidance permitted'); }
catch (RuntimeException $exception) { $check($exception->getMessage() === 'Authentication required.', 'anonymous guidance denied'); }
$check($profiles->reads === $before, 'anonymous guidance no profile read');
echo 'PASS: ' . ($checks - $baselineChecks) . " guidance checks (in-memory; no database connection or writes).\n";

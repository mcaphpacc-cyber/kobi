<?php
declare(strict_types=1);
require __DIR__ . '/api-auth-unit-support.php';
require __DIR__ . '/api-saved-symptom-checks-support.php';

class SavedApiMemory extends App\Repositories\SavedSymptomCheckRepository
{
    public array $rows = [], $reads = [];
    public function __construct() {}
    public function findForUser(int $id, int $userId): ?array
    { $this->reads[] = ['detail', $id, $userId]; return isset($this->rows[$id]) && $this->rows[$id]['user_id'] === $userId ? $this->rows[$id] : null; }
    public function countApiForUser(int $userId): int { return count($this->owned($userId)); }
    public function listApiForUser(int $userId, int $limit, int $offset): array
    { $this->reads[] = ['list', $userId, $limit, $offset]; return array_slice($this->owned($userId), $offset, $limit); }
    private function owned(int $userId): array
    { $rows = array_values(array_filter($this->rows, fn ($r) => $r['user_id'] === $userId)); usort($rows, fn ($a, $b) => [$b['checked_at'], $b['id']] <=> [$a['checked_at'], $a['id']]); return $rows; }
}
class SavedApiSql extends App\Repositories\SavedSymptomCheckRepository
{
    public array $queries = [];
    public function __construct() {}
    protected function fetch(string $sql, array $params = []): ?array { $this->queries[] = [$sql, $params]; return ['total' => '4']; }
    protected function fetchAll(string $sql, array $params = []): array { $this->queries[] = [$sql, $params]; return []; }
}
$checks = 0;
function sa(bool $ok, string $message): void { global $checks; if (!$ok) throw new LogicException($message); ++$checks; }
function savedDenied(callable $action, int $status): array
{ try { $action(); throw new LogicException('Expected denial'); } catch (App\Services\ApiAuthException $e) { sa($e->status === $status, 'Safe status'); return [$e->errorCode, $e->getMessage()]; } }

$rows = new SavedApiMemory();
$auth = (new ReflectionClass(App\Services\AuthService::class))->newInstanceWithoutConstructor();
$domain = new App\Services\SavedSymptomCheckService($rows, $auth, new App\Core\Session(), new SavedApiUnavailableChecker());
$api = new App\Services\ApiSavedSymptomCheckService($rows, $domain);
$actor = new App\Services\ApiAuthPrincipal(1, 1);
sa($api->collection($actor, []) === ['data' => [], 'meta' => ['page' => 1, 'page_size' => 20, 'total' => 0, 'has_more' => false]], 'Empty account');
$rows->rows[1] = savedApiRow();
$expected = savedApiExpected();
sa($api->detail($actor, '1', []) === $expected, 'Exact nested whitelist, stored labels, scores, explanations and unsorted historical order');
$metadata = array_diff_key($expected, ['snapshot' => true]);
sa($api->collection($actor, [])['data'] === [$metadata], 'One metadata-only saved check');
foreach (range(2, 55) as $id) $rows->rows[$id] = savedApiRow($id);
$rows->rows[56] = savedApiRow(56, 2);
$rows->rows[1]['checked_at'] = '2026-01-03 01:02:03'; // collection order uses checked_at before ID
$page = $api->collection($actor, []);
sa(array_column($page['data'], 'id') === array_map('strval', array_merge([1], range(55, 37))), 'Checked date then numeric ID ties');
sa($page['meta'] === ['page' => 1, 'page_size' => 20, 'total' => 55, 'has_more' => true], 'Default twenty and owner count');
sa(count($api->collection($actor, ['page_size' => '50'])['data']) === 50, 'Maximum fifty');
sa(count($api->collection($actor, ['page_size' => '50', 'page' => '2'])['data']) === 5, 'Last page');
sa($api->collection($actor, ['page' => '10000'])['data'] === [], 'Beyond last page');
$api->collection($actor, ['page_size' => '50', 'page' => '10000']);
sa(end($rows->reads) === ['list', 1, 50, 499950], 'Maximum offset');
sa($api->collection(new App\Services\ApiAuthPrincipal(2, 2), [])['data'][0]['id'] === '56', 'B list contains only B');
$unknown = savedDenied(fn () => $api->detail($actor, '999', []), 404);
sa(savedDenied(fn () => $api->detail($actor, '56', []), 404) === $unknown, 'Foreign and missing indistinguishable');
sa($api->detail(new App\Services\ApiAuthPrincipal(2, 2), '56', [])['id'] === '56', 'B owns detail');
$reads = $rows->reads;
foreach (['0', '01', '-1', '2147483648', 'x'] as $id) savedDenied(fn () => $api->detail($actor, $id, []), 422);
foreach ([['page' => '0'], ['page' => '01'], ['page' => '10001'], ['page_size' => '51'], ['page_size' => '0'], ['user_id' => '2'], ['profile_id' => '1']] as $q) savedDenied(fn () => $api->collection($actor, $q), 422);
foreach ([['page' => []], ['page' => null], ['page' => "\xff"]] as $q) savedDenied(fn () => $api->collection($actor, $q), 400);
savedDenied(fn () => $api->detail($actor, '1', ['page' => '1']), 400);
sa($reads === $rows->reads, 'Bad requests rejected before row reads');
$rows->rows[1] = savedApiRow();
$before = serialize($rows->rows);
sa($api->detail($actor, '1', []) === $expected, 'Unavailable current matcher and label dependencies never called');
sa(serialize($rows->rows) === $before && session_status() === PHP_SESSION_NONE, 'No data/session mutations');
$zero = savedApiSnapshot(); $zero['results'] = []; $zero['summary'] = ['total_matches' => 0, 'displayed_matches' => 0];
$rows->rows[1] = savedApiRow(1, 1, $zero);
sa($api->detail($actor, '1', [])['snapshot']['results'] === [], 'Zero-result historical record');
foreach (['{', str_repeat('x', 262145), json_encode(array_replace(savedApiSnapshot(), ['version' => 99])), json_encode(array_replace(savedApiSnapshot(), ['disclaimer' => 'changed']))] as $bad) {
    $rows->rows[1] = savedApiRow(); $rows->rows[1]['snapshot_json'] = $bad;
    try { $api->detail($actor, '1', []); throw new LogicException('Bad history accepted'); } catch (RuntimeException $e) { sa($e->getMessage() === 'This saved symptom check is unavailable. You can delete it from your saved list.', 'Shared safe decoder, no repair'); }
}
foreach (['checked_at' => '2026-01-03 00:00:00', 'selected_symptom_count' => 1, 'displayed_result_count' => 0, 'saved_at' => '2026-02-30 00:00:00'] as $key => $value) {
    $rows->rows[1] = savedApiRow(); $rows->rows[1][$key] = $value;
    try { $api->detail($actor, '1', []); throw new LogicException('Bad mirror accepted'); } catch (UnexpectedValueException) { sa(true, 'Stored metadata fails safely'); }
}
$sql = new SavedApiSql(); sa($sql->countApiForUser(1) === 4, 'Integer owner count'); $sql->listApiForUser(1, 100, -1); $sql->findForUser(1, 2);
foreach ($sql->queries as [$query, $params]) sa(str_contains($query, 'user_id=:user') && isset($params['user']), 'Repository ownership binding');
sa(str_contains($sql->queries[1][0], 'ORDER BY checked_at DESC,id DESC LIMIT 50 OFFSET 0') && !str_contains($sql->queries[1][0], 'snapshot_json'), 'Bounded lightweight SQL');
sa($sql->queries[2][1] === ['id' => 1, 'user' => 2] && str_contains($sql->queries[2][0], 'id=:id AND user_id=:user'), 'ID and owner in same detail query');
// Exercise the storage-size boundary without increasing limits or truncating historical text.
$large = savedApiSnapshot();
$large['selected'] = array_map(fn ($id) => ['id' => $id, 'label' => 's'], range(1, 50));
foreach ($large['results'] as &$item) { $item['explanation'] = array_fill(0, 52, 'x'); $item['reason'] = array_fill(0, 3, 'y'); } unset($item);
$fields = [];
foreach ($large['selected'] as &$item) $fields[] = &$item['label']; unset($item);
foreach ($large['results'] as &$item) { foreach ($item['explanation'] as &$text) $fields[] = &$text; unset($text); foreach ($item['reason'] as &$text) $fields[] = &$text; unset($text); } unset($item);
foreach ($fields as &$text) {
    $length = strlen(json_encode($large, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    $text .= str_repeat('z', min(2048 - strlen($text), 262144 - $length));
} unset($text, $fields);
$rows->rows[1] = savedApiRow(1, 1, $large);
sa(strlen($rows->rows[1]['snapshot_json']) === 262144, 'Exact stored snapshot byte ceiling');
$largeDto = $api->detail($actor, '1', []);
sa($largeDto['snapshot']['results'][1]['explanation'] === $large['results'][1]['explanation'], 'Near-limit history not truncated');
ob_start(); App\Core\PublicApiResponse::success($largeDto); $response = json_decode(ob_get_clean(), true, 32, JSON_THROW_ON_ERROR);
sa(($response['success'] ?? false) === true ? $response['data'] === $largeDto : $response['error']['code'] === 'INTERNAL_ERROR', 'Existing response ceiling returns complete history or safe error');
$large['results'][0]['explanation'][0] .= 'x'; $rows->rows[1] = savedApiRow(1, 1, $large);
try { $api->detail($actor, '1', []); throw new LogicException('Oversized snapshot accepted'); } catch (RuntimeException) { sa(true, 'Oversized history rejected without truncation'); }
preg_match('/RewriteRule (\^api\/v1\/\S+) - \[E=HTTP_AUTHORIZATION/', file_get_contents(dirname(__DIR__) . '/public/.htaccess'), $rule);
foreach (['api/v1/saved-symptom-checks', 'api/v1/saved-symptom-checks/2'] as $path) sa(preg_match('~' . $rule[1] . '~', $path) === 1, 'Scoped forwarding covers approved path');
foreach (['api/v1/saved-symptom-checks/2/history', 'api/v1/saved-symptom-checks-other', 'api/v1/diseases', 'account/saved-symptom-checks'] as $path) sa(preg_match('~' . $rule[1] . '~', $path) === 0, 'Forwarding does not broaden to unrelated paths');
echo "Saved Symptom Checks API unit: $checks checks passed.\n";

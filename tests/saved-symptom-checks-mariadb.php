<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/saved-symptom-checks.php';
// The in-memory harness already loads App classes and test-only view helpers.
// Load environment values privately, without the web/bootstrap helper side effects.
function env(string $key, mixed $default = null): mixed {
    static $values = null;
    if ($values === null) {
        $values = [];
        foreach (file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) { continue; }
            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $values[trim($name)] = trim($value);
        }
    }
    return $values[$key] ?? $default;
}
$db = new App\Core\Database();
$pdo = $db->connection();
$target = 'kobi_saved_checks_recovery_20261013';
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'medical1') { throw new RuntimeException('Normal connection identity differs.'); }
$certificate = json_decode(file_get_contents(__DIR__ . '/.saved-checks-recovery/verified.json'), true, 512, JSON_THROW_ON_ERROR);
if ($certificate['status'] !== 'RECOVERY_VERIFIED' || $certificate['recovery_database'] !== $target) { throw new RuntimeException('Disposable recovery certificate missing.'); }
$pdo->exec('USE `' . $target . '`');
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== $target) { throw new RuntimeException('Isolation guard failed.'); }
$sqlMode = $pdo->query('SELECT @@sql_mode')->fetchColumn();
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database/saved-symptom-checks.sql'));
class SavedTestDatabase extends App\Core\Database {
    public function __construct(private PDO $testConnection) {}
    public function connection(): PDO { return $this->testConnection; }
}
$repository = new App\Repositories\SavedSymptomCheckRepository(new SavedTestDatabase($pdo));
$auth->uid = 1;
$service = new App\Services\SavedSymptomCheckService($repository, $auth, $session, $checker);
$start = $checks;
$pdo->beginTransaction();
try {
    $key = $service->capture($zero)['key'];
    $id = $service->save($key);
    verify($service->get($id)['snapshot']['results'] === [], 'MariaDB roundtrip');
    verify($service->save($key) === $id, 'MariaDB idempotency');
    $auth->uid = 2;
    denied(fn() => $service->get($id), 'Saved symptom check not found.');
    denied(fn() => $service->delete($id), 'Saved symptom check not found.');
    verify($service->page(1)['rows'] === [], 'MariaDB other owner list');
    $auth->uid = 1;
    for ($i = 0; $i < 24; $i++) { $service->save($service->capture($zero)['key']); }
    verify(count($service->page(1)['rows']) === 20 && $service->page(1)['has_next'], 'MariaDB pagination');
    verify(count($service->page(2)['rows']) === 5, 'MariaDB second page');
    verify(!isset($service->page(1)['rows'][0]['snapshot_json']), 'MariaDB metadata only list');
    foreach ([['selected_symptom_count' => 0], ['displayed_result_count' => 11], ['snapshot_json' => '{broken'], ['snapshot_json' => json_encode(str_repeat('x',262144))], ['user_id' => 2147483647], ['check_key' => $key]] as $bad) {
        $params = ['user_id' => 1, 'check_key' => bin2hex(random_bytes(32)), 'checked_at' => gmdate('Y-m-d H:i:s'), 'saved_at' => gmdate('Y-m-d H:i:s'), 'selected_symptom_count' => 2, 'displayed_result_count' => 0, 'snapshot_json' => '{}'];
        $params = array_replace($params, $bad);
        try {
            $statement = $pdo->prepare('INSERT INTO user_saved_symptom_checks ('.implode(',',array_keys($params)).') VALUES ('.implode(',',array_fill(0,count($params),'?')).')');
            $statement->execute(array_values($params));
            throw new LogicException('Integrity constraint accepted invalid fixture.');
        } catch (PDOException $exception) { verify(true, 'MariaDB CHECK/FK/unique rejection'); }
    }
    $service->delete($id);
    denied(fn() => $service->get($id), 'Saved symptom check not found.');
} finally { $pdo->rollBack(); }
verify((int)$pdo->query('SELECT COUNT(*) FROM user_saved_symptom_checks')->fetchColumn() === 0, 'isolated fixtures rolled back');
verify($sqlMode === $pdo->query('SELECT @@sql_mode')->fetchColumn(), 'SQL mode unchanged');
echo 'PASS: ' . ($checks - $start) . " isolated MariaDB checks; all fixtures rolled back.\n";

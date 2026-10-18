<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';

// Only --deploy-normal authorizes the one additive statement. Default is read-only verification.
$deploy = ($argv[1] ?? '') === '--deploy-normal';
$pdo = (new App\Core\Database())->connection();
$name = $pdo->query('SELECT DATABASE()')->fetchColumn();
if ($name !== 'medical1') { throw new RuntimeException('Unexpected normal database; stopped.'); }
$ddl = file_get_contents(dirname(__DIR__) . '/database/saved-symptom-checks.sql');
if (substr_count($ddl, 'CREATE TABLE') !== 1 || preg_match('/\b(?:DROP|ALTER|INSERT|UPDATE|DELETE FROM|SET SQL_MODE)\b/i', $ddl)) { throw new RuntimeException('Unexpected DDL scope.'); }
$mode = $pdo->query('SELECT @@SESSION.sql_mode session_mode,@@GLOBAL.sql_mode global_mode')->fetch();
$fingerprint = static function (PDO $pdo): array {
    $result = [];
    foreach ($pdo->query("SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME")->fetchAll() as $table) {
        $name = $table['TABLE_NAME'];
        if ($name === 'user_saved_symptom_checks') { continue; }
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $name)) { throw new RuntimeException('Unsupported object name.'); }
        $definition = $pdo->query('SHOW CREATE TABLE `' . $name . '`')->fetch();
        $rows = $pdo->query('SELECT * FROM `' . $name . '`')->fetchAll();
        $serialized = array_map(fn ($row) => serialize($row), $rows); sort($serialized);
        $result[$name] = hash('sha256', serialize([$definition,$serialized]));
    }
    $result['__triggers'] = hash('sha256', serialize($pdo->query('SELECT * FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME')->fetchAll()));
    return $result;
};
$before = $fingerprint($pdo);
$exists = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_saved_symptom_checks'")->fetchColumn();
if ($deploy) {
    define('SSC_RECOVERY_LIBRARY', true);
    require __DIR__ . '/saved-symptom-checks-recovery.php';
    $proof = json_decode(file_get_contents(SSC_BACKUP_DIR . '/verified.json'), true, 512, JSON_THROW_ON_ERROR);
    sscAssert($proof['status'] === 'RECOVERY_VERIFIED' && hash_file('sha256', SSC_BACKUP_DIR . '/before.bin') === $proof['backup_sha256']
        && hash('sha256', $ddl) === $proof['artifact_sha256'] && sscDigest(sscCapture($pdo), 'medical1') === $proof['normal_fingerprint'], 'Verified recovery checkpoint or normal state differs; no deployment.');
    if ($exists !== 0) { throw new RuntimeException('Table already exists; no redeployment performed.'); }
    $pdo->exec($ddl);
    if ($pdo->query('SHOW WARNINGS')->fetchAll() !== []) { throw new RuntimeException('DDL warning; stopped.'); }
}
$definition = $pdo->query('SHOW CREATE TABLE user_saved_symptom_checks')->fetch()['Create Table'];
foreach (['uq_saved_check_user_key','idx_saved_check_user_time','fk_saved_check_user','chk_saved_check_counts','chk_saved_check_json'] as $object) {
    if (!str_contains($definition,$object)) { throw new RuntimeException('Missing expected schema object.'); }
}
$columns = $pdo->query('SHOW COLUMNS FROM user_saved_symptom_checks')->fetchAll();
if (array_column($columns,'Field') !== ['id','user_id','check_key','checked_at','saved_at','selected_symptom_count','displayed_result_count','snapshot_json']) { throw new RuntimeException('Unexpected columns.'); }
if (str_contains($definition,'health_profile_id') || !str_contains($definition,'ON DELETE CASCADE') || !str_contains($definition,'ENGINE=InnoDB')) { throw new RuntimeException('Schema relationship/engine differs.'); }
if ($mode !== $pdo->query('SELECT @@SESSION.sql_mode session_mode,@@GLOBAL.sql_mode global_mode')->fetch() || $before !== $fingerprint($pdo)) { throw new RuntimeException('SQL mode or existing schema/data changed.'); }
echo json_encode(['status'=>'PASS','mode'=>$deploy?'one normal additive CREATE TABLE':'read-only verification','database'=>$name,'server_version'=>$pdo->query('SELECT VERSION()')->fetchColumn(),'sql_mode'=>$mode,'existing_objects_and_rows_preserved'=>true,'columns'=>array_column($columns,'Field'),'saved_rows'=>(int)$pdo->query('SELECT COUNT(*) FROM user_saved_symptom_checks')->fetchColumn()],JSON_PRETTY_PRINT),"\n";

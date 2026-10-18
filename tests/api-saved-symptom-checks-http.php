<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('API_IDENTITY_HTTP_LIBRARY', true);
require __DIR__ . '/api-identity-http.php';
require __DIR__ . '/api-saved-symptom-checks-support.php';

function savedPage(string $path, string $token, int $page, int $size, int $total, ?array $body = null, array $extra = []): array
{
    global $pem;
    $c = curl_init('https://localhost:18866/api/v1/' . $path);
    $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array_merge(['Connection: close', 'Authorization: Bearer ' . $token], $extra),
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CAINFO => $pem,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10];
    if ($body !== null) $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
    curl_setopt_array($c, $options); $raw = curl_exec($c);
    ih(is_string($raw) && curl_errno($c) === 0 && curl_getinfo($c, CURLINFO_SSL_VERIFYRESULT) === 0, 'Verified collection HTTPS');
    ih(curl_getinfo($c, CURLINFO_HTTP_CODE) === 200, 'Collection 200');
    $n = curl_getinfo($c, CURLINFO_HEADER_SIZE); curl_close($c); $head = substr($raw, 0, $n); $text = substr($raw, $n);
    ih(preg_match('/^Content-Type: application\/json; charset=UTF-8\s*$/im', $head) === 1, 'JSON UTF8');
    ih(preg_match('/^Cache-Control: no-store\s*$/im', $head) === 1, 'No-store');
    ih(preg_match('/^X-Content-Type-Options: nosniff\s*$/im', $head) === 1, 'Nosniff');
    ih(!preg_match('/^(?:Set-Cookie|Access-Control-Allow-Origin|Access-Control-Allow-Credentials):/im', $head), 'No cookies/CORS');
    $json = json_decode($text, true, 16, JSON_THROW_ON_ERROR);
    ih(array_keys($json) === ['success', 'data', 'meta'] && $json['success'] === true, 'Exact envelope');
    ih($json['meta'] === ['api_version' => 'v1', 'page' => $page, 'page_size' => $size, 'total' => $total, 'has_more' => $page * $size < $total], 'Exact paging metadata');
    foreach ($json['data'] as $row) {
        ih(array_keys($row) === ['id', 'checked_at', 'saved_at', 'selected_symptom_count', 'displayed_result_count'], 'Five-field list projection');
        ih(is_string($row['id']) && is_int($row['selected_symptom_count']) && is_int($row['displayed_result_count']), 'List types');
    }
    ih(!str_contains($text, 'PRIVATE') && !str_contains($text, 'snapshot_json') && !str_contains($text, 'check_key'), 'List privacy');
    return $json['data'];
}

$source = apiFixtureSource(); apiFixtureCreate($source); $db = apiFixtureConnect(); $p = $db->connection();
$storage = dirname(__DIR__) . '/storage/cache/api-saved-symptom-checks-http-' . bin2hex(random_bytes(8));
mkdir($storage, 0700); $pem = $storage . '/tls.pem'; $processes = [];
try {
    $p->exec(file_get_contents(dirname(__DIR__) . '/database/saved-symptom-checks.sql'));
    $password = bin2hex(random_bytes(18));
    foreach ([100001, 100002, 100003] as $id) apiFixtureUser($p, $password, $id);
    $ssl = 'D:/xampp/apache/conf/openssl.cnf';
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'config' => $ssl]);
    $csr = openssl_csr_new(['commonName' => 'localhost'], $key, ['config' => $ssl]);
    $cert = openssl_csr_sign($csr, null, $key, 1, ['config' => $ssl]);
    openssl_x509_export($cert, $public); openssl_pkey_export($key, $private, null, ['config' => $ssl]);
    file_put_contents($pem, $public . $private); unset($private, $key);
    $rateKey = bin2hex(random_bytes(32)); $environment = getenv();
    $environment['KOBI_API_TEST_RATE_KEY'] = $rateKey; $environment['KOBI_API_TEST_STORAGE'] = $storage;
    $server = proc_open([PHP_BINARY, '-S', '127.0.0.1:18865', __DIR__ . '/api-identity-http-router.php'], [0 => ['pipe', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes, dirname(__DIR__), $environment);
    $processes[] = [$server, $pipes]; fclose($pipes[0]);
    $proxy = proc_open([PHP_BINARY, __DIR__ . '/api-auth-http.php', '--tls-proxy'], [0 => ['pipe', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes);
    $processes[] = [$proxy, $pipes]; fwrite($pipes[0], json_encode(['pem' => $pem])); fclose($pipes[0]);
    $ready = false;
    for ($i = 0; $i < 30; $i++) { usleep(100000); $socket = @stream_socket_client('tcp://127.0.0.1:18865', $errno, $error, 0.1); if ($socket) { fclose($socket); $ready = true; break; } }
    ih($ready, 'Upstream ready'); usleep(300000);
    $families = [];
    foreach ([100001, 100001, 100002, 100003] as $id) $families[] = identityHttp('auth/login', null, 200, 'POST', ['email' => 'fixture' . $id . '@example.invalid', 'password' => $password]);
    [$a, $device, $b, $empty] = $families;
    $insert = $p->prepare('INSERT INTO user_saved_symptom_checks(id,user_id,check_key,checked_at,saved_at,selected_symptom_count,displayed_result_count,snapshot_json) VALUES (?,?,?,?,?,?,?,?)');
    $route = 'saved-symptom-checks';
    ih(savedPage($route, $a['access_token'], 1, 20, 0) === [], 'Initially empty account');
    $one = savedApiRow(1, 100001); $insert->execute(array_values($one));
    ih(count(savedPage($route, $a['access_token'], 1, 20, 1)) === 1, 'Single saved check');
    ih(identityHttp($route . '/1', $a['access_token']) === savedApiExpected(), 'Exact nested detail and stored scores/explanations/order');
    foreach (range(2, 55) as $id) $insert->execute(array_values(savedApiRow($id, 100001)));
    $insert->execute(array_values(savedApiRow(56, 100002)));
    $p->exec("UPDATE user_saved_symptom_checks SET checked_at='2026-01-03 01:02:03',snapshot_json=JSON_SET(snapshot_json,'$.checked_at','2026-01-03 01:02:03') WHERE id=1");
    $snapshot = [];
    foreach (['users', 'user_auth_tokens', 'user_api_sessions', 'user_api_refresh_tokens', 'user_saved_symptom_checks'] as $table) $snapshot[$table] = $p->query('SELECT * FROM ' . $table . ' ORDER BY id')->fetchAll();
    $orderedIds = array_map('strval', array_merge([1], range(55, 2)));
    foreach ([['', 1, 20], ['?page=2', 2, 20], ['?page=3', 3, 20], ['?page_size=50', 1, 50], ['?page_size=50&page=2', 2, 50], ['?page=10000', 10000, 20]] as [$query, $page, $size]) {
        $data = savedPage($route . $query, $a['access_token'], $page, $size, 55);
        ih(array_column($data, 'id') === array_slice($orderedIds, ($page - 1) * $size, $size), 'SQL checked_at/id order and bounded pages');
    }
    ih(array_column(savedPage($route, $b['access_token'], 1, 20, 1), 'id') === ['56'], 'B lists only B');
    ih(savedPage($route, $empty['access_token'], 1, 20, 0) === [], 'Other empty account');
    ih(identityHttp($route . '/56', $b['access_token']) === savedApiExpected(56), 'B owns detail');
    $missing = identityHttp($route . '/9999', $a['access_token'], 404);
    ih(identityHttp($route . '/56', $a['access_token'], 404) === $missing, 'Foreign/nonexistent parity');
    identityHttp($route . '/2', $b['access_token'], 404);
    ih(identityHttp($route . '/2', $device['access_token']) === savedApiExpected(2), 'Another device same owner');
    ih(identityHttp($route . '/2', $a['access_token'], 200, 'GET', ['user_id' => '100002'], ['Cookie: PHPSESSID=not-an-api-actor', 'X-User-ID: 100002']) === savedApiExpected(2), 'Body/cookie/header cannot override owner');
    ih(array_column(savedPage($route, $a['access_token'], 1, 20, 55, ['user_id' => '100002'], ['X-User-ID: 100002']), 'id') === array_slice($orderedIds, 0, 20), 'List principal only');
    ih((int) $p->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('diseases','symptoms')")->fetchColumn() === 0, 'Current disease/symptom tables unavailable');
    ih(identityHttp($route . '/2', $a['access_token']) === savedApiExpected(2), 'Historical data independent of absent current labels/matcher tables');
    foreach ([$route, $route . '/2'] as $path) {
        foreach ([null, 'malformed', 'ka1_' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')] as $token) identityHttp($path, $token, 401);
        identityHttp($path, $a['refresh_token'], 401);
        foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $method) identityHttp($path, null, 405, $method);
    }
    foreach (['page=0', 'page=01', 'page=10001', 'page_size=51', 'page_size=0', 'user_id=100002', 'profile_id=1', 'sort=id'] as $query) identityHttp($route . '?' . $query, $a['access_token'], 422);
    identityHttp($route . '?page[]=1', $a['access_token'], 400); identityHttp($route . '?page=%FF', $a['access_token'], 400);
    identityHttp($route . '?page=0', null, 401); identityHttp($route . '/2?page=1', $a['access_token'], 400);
    foreach (['0', '01', '-1', 'abc', '2147483648', 'history'] as $id) identityHttp($route . '/' . $id, $a['access_token'], 422);
    foreach ([$route . '/2/history', 'health-profiles/1/saved-symptom-checks'] as $path) identityHttp($path, $a['access_token'], 404);
    foreach ($snapshot as $table => $rows) ih($p->query('SELECT * FROM ' . $table . ' ORDER BY id')->fetchAll() === $rows, 'GET preserves all synthetic rows in ' . $table);
    // No nullable fields exist here. Non-strict MariaDB may coerce NULL on UPDATE;
    // do not mistake NOT NULL metadata for a strict-mode write guarantee.
    ih((int) $p->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_saved_symptom_checks' AND IS_NULLABLE='YES'")->fetchColumn() === 0, 'No nullable saved-check columns');
    $p->exec("UPDATE user_saved_symptom_checks SET saved_at='0000-00-00 00:00:00' WHERE id=2");
    identityHttp($route . '/2', $a['access_token'], 500);
    $p->exec("UPDATE user_saved_symptom_checks SET saved_at='2026-01-02 04:05:06' WHERE id=2");
    $update = $p->prepare('UPDATE user_saved_symptom_checks SET snapshot_json=? WHERE id=2');
    foreach (['malformed', json_encode(array_replace(savedApiSnapshot(), ['version' => 99])), json_encode(array_replace(savedApiSnapshot(), ['disclaimer' => 'changed']))] as $json) {
        if ($json === 'malformed') {
            try { $update->execute([$json]); throw new LogicException('Malformed JSON accepted'); } catch (PDOException $e) { ih($e->getCode() === '23000', 'DB JSON CHECK'); }
        } else { $update->execute([$json]); identityHttp($route . '/2', $a['access_token'], 500); }
    }
    $update->execute([savedApiRow()['snapshot_json']]);
    $p->exec('UPDATE user_saved_symptom_checks SET displayed_result_count=0 WHERE id=2'); identityHttp($route . '/2', $a['access_token'], 500);
    $p->exec('UPDATE user_saved_symptom_checks SET displayed_result_count=2 WHERE id=2');
    $zero = savedApiSnapshot(); $zero['results'] = []; $zero['summary'] = ['total_matches' => 0, 'displayed_matches' => 0];
    $insert->execute(array_values(savedApiRow(57, 100001, $zero)));
    ih(identityHttp($route . '/57', $a['access_token'])['snapshot']['results'] === [], 'SQL zero-result snapshot');
    $sid = $p->prepare('SELECT id FROM user_api_sessions WHERE public_id=?'); $sid->execute([$a['session_id']]);
    $bucket = hash_hmac('sha256', "access_family\0" . $sid->fetchColumn(), $rateKey); $file = $storage . '/cache/api-auth/counters.json';
    $counters = json_decode(file_get_contents($file), true); $count = $counters[$bucket]['count'];
    identityHttp($route . '/2', $a['access_token']); $counters = json_decode(file_get_contents($file), true);
    ih($counters[$bucket]['count'] === $count + 1, 'One existing family admission per GET');
    $counters[$bucket]['count'] = 120; file_put_contents($file, json_encode($counters)); identityHttp($route . '/2', $a['access_token'], 429);
    $counters[$bucket]['count'] = 0; file_put_contents($file, json_encode($counters));
    $s = $p->prepare('UPDATE user_api_sessions SET created_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),last_refreshed_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),access_expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 600 SECOND) WHERE public_id=?');
    $s->execute([$a['session_id']]);
    foreach ([$route, $route . '/2'] as $path) identityHttp($path, $a['access_token'], 401);
    identityHttp('auth/logout', $device['access_token'], 200, 'POST', []);
    foreach ([$route, $route . '/2'] as $path) identityHttp($path, $device['access_token'], 401);
    foreach ([$a, $b, $empty] as $issued) { $next = identityHttp('auth/refresh', null, 200, 'POST', ['refresh_token' => $issued['refresh_token']]); identityHttp('auth/logout', $next['access_token'], 200, 'POST', []); }
    ih((int) $p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn() === 0, 'Zero active disposable families');
} finally {
    foreach (array_reverse($processes) as [$process, $pipes]) { if (is_resource($process)) { proc_terminate($process); foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_close($process); } }
    apiFixtureDrop($source);
    if (is_file($storage . '/cache/api-auth/counters.json')) { unlink($storage . '/cache/api-auth/counters.json'); rmdir($storage . '/cache/api-auth'); rmdir($storage . '/cache'); }
    if (is_file($pem)) unlink($pem); rmdir($storage);
}
echo "Saved Symptom Checks API MariaDB + verified HTTPS: $checks checks passed; disposable database/server/files removed.\n";

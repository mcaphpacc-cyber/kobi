<?php
declare(strict_types=1);
// Only the guarded disposable CLI server behind the verified loopback TLS harness.
if(PHP_SAPI!=='cli-server'){http_response_code(404);exit;}
require __DIR__.'/api-auth-fixture.php';
apiFixtureConnect();
$_ENV['API_AUTH_RATE_KEY']=(string)getenv('KOBI_API_TEST_RATE_KEY');
$_ENV['APP_STORAGE_PATH']=(string)getenv('KOBI_API_TEST_STORAGE');
$_ENV['APP_BASE_PATH']='';
$_SERVER['HTTPS']='on';
if(!str_starts_with($_SERVER['REQUEST_URI']??'', '/api/v1/')){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';

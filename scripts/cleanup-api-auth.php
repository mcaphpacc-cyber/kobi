<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/vendor/autoload.php';
if (($argv[1]??'')!=='--expired-api-only') { fwrite(STDERR,"Use --expired-api-only; removes only API families expired/revoked over seven days ago.\n");exit(1); }
$repo=new App\Repositories\ApiAuthRepository(new App\Core\Database());
$count=$repo->cleanup(gmdate('Y-m-d H:i:s',time()-7*86400));
echo "Expired API families removed: $count (maximum 500 per invocation).\n";

<?php

declare(strict_types=1);

use App\Core\App;

/*
|--------------------------------------------------------------------------
| Bootstrap KOBI
|--------------------------------------------------------------------------
*/

$config = require dirname(__DIR__) . '/app/Config/Config.php';

date_default_timezone_set($config['timezone']);

session_start();

header('Content-Type: text/html; charset=UTF-8');

/*
|--------------------------------------------------------------------------
| Start Application
|--------------------------------------------------------------------------
*/

$app = new App();

$app->run();
<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap KOBI
|--------------------------------------------------------------------------
*/

$config = require dirname(__DIR__) . '/app/Config/Config.php';

date_default_timezone_set($config['timezone']);

session_start();

header('Content-Type: text/html; charset=UTF-8');
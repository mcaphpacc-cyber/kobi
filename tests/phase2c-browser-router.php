<?php
declare(strict_types=1);
// Loopback-only validation entry point. Application source/configuration unchanged.
if (!in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true)) { http_response_code(403); exit; }
$root=dirname(__DIR__);
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
if (str_starts_with((string)$path,'/assets/')) { return false; }
require $root.'/vendor/autoload.php';
env('DB_HOST'); // Load configuration first; then isolate this process only.
$_ENV['DB_DATABASE']='kobi_phase2c_validation_20261008';
$_ENV['APP_URL']='http://127.0.0.1:8127';
$_ENV['APP_BASE_PATH']='';
$_ENV['APP_DEBUG']='false';
$_ENV['APP_STORAGE_PATH']=$root.'/tests/phase2c-browser-storage';
$_ENV['TREATMENT_PUBLICATION_MODE']='hold';
$_ENV['MAIL_DRIVER']='log';
$sessions=$root.'/tests/phase2c-browser-storage/sessions';
if (!is_dir($sessions)) { mkdir($sessions,0700,true); }
session_name('KOBI_PHASE2C_VALIDATION');
session_save_path($sessions);
require $root.'/bootstrap/app.php';

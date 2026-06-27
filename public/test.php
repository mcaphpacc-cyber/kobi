<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;

$db = new Database();

echo "<h2>✅ Database Connected Successfully</h2>";

echo "<pre>";

print_r($db->connection()->query("SELECT DATABASE()")->fetch());

echo "</pre>";
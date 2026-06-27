<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| KOBI Front Controller
|--------------------------------------------------------------------------
*/

require dirname(__DIR__) . '/vendor/autoload.php';

require dirname(__DIR__) . '/bootstrap/app.php';

?>
<!doctype html>
<html lang="<?= e(config('locale')); ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title><?= e(config('name')); ?></title>

<style>

body{

    font-family:Arial,Helvetica,sans-serif;

    background:#f5f5f5;

    margin:0;

    padding:60px;

}

.container{

    max-width:900px;

    margin:auto;

    background:white;

    padding:40px;

    border-radius:10px;

    box-shadow:0 0 20px rgba(0,0,0,.08);

}

h1{

    color:#0d6efd;

}

.success{

    color:green;

    font-size:20px;

}

</style>

</head>

<body>

<div class="container">

<h1><?= e(config('name')); ?></h1>

<p><strong>Version:</strong> <?= e(config('version')); ?></p>

<p class="success">✔ KOBI Core bootstrapped successfully.</p>

<p>Congratulations! Your custom framework is now running.</p>

</div>

</body>

</html>
<!DOCTYPE html>
<html lang="<?= e(config('locale')); ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title><?= e($title) ?></title>

<style>

body{

    font-family:Arial, Helvetica, sans-serif;
    background:#f5f5f5;
    margin:0;
    padding:60px;

}

.card{

    max-width:900px;
    margin:auto;
    background:white;
    padding:40px;
    border-radius:10px;
    box-shadow:0 0 15px rgba(0,0,0,.08);

}

h1{

    color:#0d6efd;

}

</style>

</head>

<body>

<div class="card">

<h1><?= e($title) ?></h1>

<p><strong>Version:</strong> <?= e($version) ?></p>

<p>✅ View Engine is working.</p>

<p>✅ Controller is working.</p>

<p>✅ MVC architecture has started.</p>

</div>

</body>

</html>
<!doctype html>
<html lang="<?= e(config('locale')) ?>">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1">

<title><?= e($title ?? config('name')) ?></title>

<meta
    name="description"
    content="<?= e($description ?? 'Medical Knowledge Platform') ?>">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css"
rel="stylesheet">

<link
rel="stylesheet"
href="<?= asset('css/theme.css') ?>">

<link
rel="stylesheet"
href="<?= asset('css/components.css') ?>">

<link
rel="stylesheet"
href="<?= asset('css/app.css') ?>">

</head>

<body>
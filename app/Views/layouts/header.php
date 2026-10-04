<!doctype html>
<html
    lang="en"
    data-base-url="<?= e(baseUrl()) ?>"
    data-api-base="<?= e(url('api')) ?>"
>

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
href="<?= asset('css/bootstrap.min.css') ?>"
rel="stylesheet">

<link
href="<?= asset('css/bootstrap-icons.css') ?>"
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

<link
rel="stylesheet"
href="<?= asset('css/symptom-checker.css') ?>">

<link
    rel="stylesheet"
    href="<?= asset('css/disease.css'); ?>">

<link
    rel="stylesheet"
    href="<?= asset('css/discovery.css') ?>">

<script src="<?= asset('js/kobi-config.js') ?>"></script>

</head>

<body>
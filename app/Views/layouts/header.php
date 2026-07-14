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

</head>
<script>
window.KOBI = Object.freeze({

    baseUrl: "<?= url(); ?>",

    apiBase: "<?= url('api'); ?>",

    locale: "<?= config('locale'); ?>"

});
</script>

<body>
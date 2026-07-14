<?php require __DIR__.'/header.php'; ?>

<?php require __DIR__.'/navbar.php'; ?>

<main class="container py-4">

<?= $content ?>

</main>

<?php require __DIR__.'/footer.php'; ?>

<script src="<?= asset('js/app.js') ?>"></script>

<?php if (str_contains($_SERVER['REQUEST_URI'], "symptom-checker")) {?>
    <script src="<?= asset('js/symptom-checker.js') ?>"></script>
<?php }?>
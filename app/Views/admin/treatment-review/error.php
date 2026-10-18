<div class="container py-4">
    <h1 class="h3">Treatment review request</h1>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <p>Reload the current revision and submission before making another decision.</p>
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/review')) ?>">Review queue</a>
</div>

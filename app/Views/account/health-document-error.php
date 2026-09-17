<?php
$error =
    $error
    ?? 'Medical document could not be opened.';
?>

<div class="container py-5">

    <div class="card shadow-sm">

        <div class="card-body text-center py-5">

            <h1 class="h4 mb-3">
                Medical Document Unavailable
            </h1>

            <p class="text-muted mb-4">
                <?= htmlspecialchars($error) ?>
            </p>

            <a
                href="<?= url(
                    '/account/health-records'
                ) ?>"
                class="btn btn-outline-primary"
            >
                Back to Health Records
            </a>

        </div>

    </div>

</div>
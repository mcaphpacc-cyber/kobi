<?php

declare(strict_types=1);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">

                    <h1 class="h4 mb-3">
                        Email Verification Failed
                    </h1>

                    <div class="alert alert-danger">
                        <?= e($error ?? 'The verification link is invalid or has expired.') ?>
                    </div>

                    <p class="text-muted">
                        Please request a new verification email
                        and try again.
                    </p>

                    <a
                        href="<?= e(url('/login')) ?>"
                        class="btn btn-primary"
                    >
                        Go to Login
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>
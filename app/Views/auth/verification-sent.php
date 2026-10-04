<?php
declare(strict_types=1);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">

                    <h1 class="h4 mb-3">
                        Verify Your Email
                    </h1>

                    <p class="text-muted">
                        Your KOBI account has been created successfully.
                    </p>

                    <p>
                        We have sent a verification link to:
                    </p>

                    <p class="fw-semibold">
                        <?= e($email ?? '') ?>
                    </p>

                    <p class="text-muted small">
                        Please check your inbox and click the verification
                        link to activate your account.
                    </p>

                    <p class="text-muted small mb-0">
                        The verification link expires after 24 hours.
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>
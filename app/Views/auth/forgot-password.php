<?php

declare(strict_types=1);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h1 class="h4 mb-3 text-center">
                        Forgot Password
                    </h1>

                    <?php if (!empty($message)): ?>

                        <div class="alert alert-success">
                            <?= e($message) ?>
                        </div>

                    <?php endif; ?>

                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger">
                            <?= e($error) ?>
                        </div>

                    <?php endif; ?>

                    <p class="text-muted">
                        Enter the email address associated with
                        your KOBI account.
                    </p>

                    <form
                        method="post"
                        action="<?= e(url('/forgot-password')) ?>"
                    >

                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label
                                for="email"
                                class="form-label"
                            >
                                Email address
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= e($email ?? '') ?>"
                                maxlength="255"
                                required
                                autocomplete="email"
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Send Password Reset Email
                        </button>

                    </form>

                    <div class="text-center mt-3">
                        <a href="<?= e(url('/login')) ?>">
                            Back to Login
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
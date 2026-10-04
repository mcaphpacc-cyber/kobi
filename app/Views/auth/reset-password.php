<?php

declare(strict_types=1);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h1 class="h4 mb-3 text-center">
                        Reset Password
                    </h1>

                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger">
                            <?= e($error) ?>
                        </div>

                    <?php endif; ?>

                    <p class="text-muted">
                        Enter your new password below.
                    </p>

                    <form
                        method="post"
                        action="<?= e(url('/reset-password')) ?>"
                    >

                        <?= csrfField() ?>

                        <input
                            type="hidden"
                            name="token"
                            value="<?= e($token ?? '') ?>"
                        >

                        <div class="mb-3">
                            <label
                                for="password"
                                class="form-label"
                            >
                                New password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                minlength="8"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="mb-3">
                            <label
                                for="password_confirmation"
                                class="form-label"
                            >
                                Confirm new password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="password_confirmation"
                                name="password_confirmation"
                                minlength="8"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Reset Password
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
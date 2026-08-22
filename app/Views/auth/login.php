<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">

        <div class="card shadow-sm border-0">

            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">

                    <h1 class="h3 mb-2">
                        Login to KOBI
                    </h1>

                    <p class="text-muted mb-0">
                        Access your KOBI account.
                    </p>

                </div>

                <?php if (!empty($error)): ?>

                    <div
                        class="alert alert-danger"
                        role="alert">

                        <?= e($error) ?>

                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    action="<?= url('/login') ?>"
                    novalidate>

                    <?= csrfField() ?>

                    <div class="mb-3">

                        <label
                            for="email"
                            class="form-label">

                            Email address

                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="<?= e($old['email'] ?? '') ?>"
                            autocomplete="email"
                            required>

                    </div>

                    <div class="mb-3">

                        <label
                            for="password"
                            class="form-label">

                            Password

                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100">

                        Login

                    </button>

                </form>

                <div class="text-center mt-4">

                    <span class="text-muted">
                        Don't have an account?
                    </span>

                    <a href="<?= url('/register') ?>">
                        Create one
                    </a>

                </div>

            </div>

        </div>

    </div>
</div>
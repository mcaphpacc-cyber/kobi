<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">

        <div class="card shadow-sm border-0">

            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">

                    <h1 class="h3 mb-2">
                        Create Your KOBI Account
                    </h1>

                    <p class="text-muted mb-0">
                        Create an account to use KOBI's personalized features.
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
                    action="<?= url('/register') ?>"
                    novalidate>

                    <?= csrfField() ?>

                    <div class="mb-3">

                        <label
                            for="name"
                            class="form-label">

                            Name

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="<?= e($old['name'] ?? '') ?>"
                            autocomplete="name"
                            maxlength="100"
                            required>

                    </div>

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
                            maxlength="255"
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
                            autocomplete="new-password"
                            minlength="8"
                            required>

                        <div class="form-text">
                            Password must be at least 8 characters long.
                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100">

                        Create Account

                    </button>

                </form>

                <div class="text-center mt-4">

                    <span class="text-muted">
                        Already have an account?
                    </span>

                    <a href="<?= url('/login') ?>">
                        Login
                    </a>

                </div>

            </div>

        </div>

    </div>
</div>
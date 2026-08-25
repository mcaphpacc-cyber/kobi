<nav class="navbar navbar-expand-lg navbar-dark bg-primary">

    <div class="container">

        <a
            class="navbar-brand fw-bold"
            href="<?= url('/') ?>">

            KOBI

        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbar">

            <span class="navbar-toggler-icon"></span>

        </button>

        <div
            class="collapse navbar-collapse"
            id="navbar">

            <ul class="navbar-nav me-auto">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= url('/') ?>">

                        Home

                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= url('/diseases') ?>">

                        Diseases

                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= url('/symptom-checker'); ?>">

                        Symptom Checker

                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= url('/compare'); ?>">

                        Compare

                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= url('/discovery'); ?>">

                        Knowledge Discovery

                    </a>

                </li>

                <li class="nav-item">

                    <a class="nav-link disabled">

                        Body Parts

                    </a>

                </li>

                <li class="nav-item">

                    <a class="nav-link disabled">

                        Symptoms

                    </a>

                </li>

                <li class="nav-item">

                    <a class="nav-link disabled">

                        Search

                    </a>

                </li>

            </ul>

            <!-- Authentication -->

            <?php if ($auth !== null && $auth->check()): ?>

                <div class="d-flex align-items-center gap-3">

                    <span class="navbar-text">

                        Hello,
                        <?= e($auth->user()['name'] ?? 'User') ?>

                    </span>

                    <a
                        class="btn btn-outline-light btn-sm"
                        href="<?= url('/account') ?>">

                        My Account

                    </a>

                    <form
                        method="POST"
                        action="<?= url('/logout') ?>"
                        class="d-inline">

                        <?= csrfField() ?>

                        <button
                            type="submit"
                            class="btn btn-outline-light btn-sm">

                            Logout

                        </button>

                    </form>

                </div>

            <?php else: ?>

                <div class="d-flex align-items-center gap-2">

                    <a
                        class="btn btn-outline-light btn-sm"
                        href="<?= url('/login') ?>">

                        Login

                    </a>

                    <a
                        class="btn btn-light btn-sm text-primary"
                        href="<?= url('/register') ?>">

                        Register

                    </a>

                </div>

            <?php endif; ?>

            <span class="navbar-text ms-3">

                v<?= e(config('version')) ?>

            </span>

        </div>

    </div>

</nav>
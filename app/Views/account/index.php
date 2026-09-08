<div class="container py-5">

    <div class="row g-4">

        <!-- Account Sidebar -->

        <div class="col-12 col-lg-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h2 class="h5 mb-3">
                        My Account
                    </h2>

                    <div class="list-group">

                        <a
                            href="<?= e(url('/account')) ?>"
                            class="list-group-item list-group-item-action active"
                        >
                            <i class="bi bi-person me-2"></i>
                            Account
                        </a>

                        <a
                            href="<?= e(url('/account/health-records')) ?>"
                            class="list-group-item list-group-item-action"
                        >
                            <i class="bi bi-heart-pulse me-2"></i>
                            Health Records
                        </a>

                        <a
                            href="<?= e(url('/account/saved-diseases')) ?>"
                            class="list-group-item list-group-item-action"
                        >
                            <i class="bi bi-bookmark-heart me-2"></i>
                            Saved Diseases
                        </a>

                        <a
                            href="<?= e(
                                url('/account/treatment-preferences')
                            ) ?>"
                            class="list-group-item list-group-item-action"
                        >
                            <i class="bi bi-sliders me-2"></i>
                            Treatment Preferences
                        </a>

                    </div>


                    <div class="border-top mt-4 pt-3">

                        <div class="text-muted small mb-2">
                            Account Settings
                        </div>

                        <div class="list-group">

                            <a
                                href="#profile"
                                class="list-group-item list-group-item-action"
                            >
                                <i class="bi bi-person-gear me-2"></i>
                                Update Profile
                            </a>

                            <a
                                href="#password"
                                class="list-group-item list-group-item-action"
                            >
                                <i class="bi bi-key me-2"></i>
                                Change Password
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Account Content -->

        <div class="col-12 col-lg-9">

            <div class="mb-4">

                <div class="d-flex flex-column flex-md-row
                            justify-content-between
                            align-items-md-center
                            gap-2">

                    <div>

                        <h1 class="h3 mb-1">
                            My Account
                        </h1>

                        <p class="text-muted mb-0">
                            Manage your KOBI account.
                        </p>

                    </div>

                    <span class="badge text-bg-success align-self-start">
                        <?= e(
                            ucfirst($user['status'] ?? 'active')
                        ) ?>
                    </span>

                </div>

            </div>


            <!-- Profile -->

            <div
                class="card shadow-sm"
                id="profile"
            >

                <div class="card-body">

                    <h2 class="h5 mb-4">
                        Profile
                    </h2>


                    <?php if (!empty($error)): ?>

                        <div
                            class="alert alert-danger"
                            role="alert"
                        >
                            <?= e($error) ?>
                        </div>

                    <?php endif; ?>


                    <?php if (
                        isset($_GET['profile_updated']) &&
                        $_GET['profile_updated'] === '1'
                    ): ?>

                        <div
                            class="alert alert-success"
                            role="alert"
                        >
                            Your profile has been updated
                            successfully.
                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        action="<?= e(
                            url('/account/profile')
                        ) ?>"
                    >

                        <?= csrfField() ?>


                        <div class="mb-3">

                            <label
                                for="name"
                                class="form-label"
                            >
                                Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                value="<?= e(
                                    $user['name'] ?? ''
                                ) ?>"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= e(
                                    $user['email'] ?? ''
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Save Changes
                        </button>

                    </form>

                </div>

            </div>


            <!-- Saved Diseases -->

            <div class="card shadow-sm mt-4">

                <div class="card-body">

                    <div class="d-flex
                                flex-column flex-md-row
                                justify-content-between
                                align-items-md-center
                                gap-3">

                        <div>

                            <h2 class="h5 mb-2">

                                <i class="bi bi-bookmark-heart me-2"></i>

                                Saved Diseases

                            </h2>

                            <p class="text-muted mb-0">
                                View and manage diseases you have saved.
                            </p>

                        </div>


                        <a
                            href="<?= e(
                                url('/account/saved-diseases')
                            ) ?>"
                            class="btn btn-outline-primary"
                        >

                            <i class="bi bi-bookmark me-1"></i>

                            View Saved Diseases

                        </a>

                    </div>

                </div>

            </div>


            <!-- Treatment Preferences -->

            <div class="card shadow-sm mt-4">

                <div class="card-body">

                    <div class="d-flex
                                flex-column flex-md-row
                                justify-content-between
                                align-items-md-center
                                gap-3">

                        <div>

                            <h2 class="h5 mb-2">

                                <i class="bi bi-sliders me-2"></i>

                                Treatment Preferences

                            </h2>

                            <p class="text-muted mb-0">
                                Choose the order in which treatment
                                approaches appear on disease pages.
                            </p>

                        </div>


                        <a
                            href="<?= e(
                                url('/account/treatment-preferences')
                            ) ?>"
                            class="btn btn-outline-primary"
                        >

                            <i class="bi bi-sliders me-1"></i>

                            Manage Preferences

                        </a>

                    </div>

                </div>

            </div>


            <!-- Change Password -->

            <div
                class="card shadow-sm mt-4"
                id="password"
            >

                <div class="card-body">

                    <h2 class="h5 mb-4">
                        Change Password
                    </h2>


                    <?php if (!empty($passwordError)): ?>

                        <div
                            class="alert alert-danger"
                            role="alert"
                        >
                            <?= e($passwordError) ?>
                        </div>

                    <?php endif; ?>


                    <?php if (
                        isset($_GET['password_updated']) &&
                        $_GET['password_updated'] === '1'
                    ): ?>

                        <div
                            class="alert alert-success"
                            role="alert"
                        >
                            Your password has been changed
                            successfully.
                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        action="<?= e(
                            url('/account/password')
                        ) ?>"
                    >

                        <?= csrfField() ?>


                        <div class="mb-3">

                            <label
                                for="current_password"
                                class="form-label"
                            >
                                Current Password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label
                                for="new_password"
                                class="form-label"
                            >
                                New Password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="new_password"
                                name="new_password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label
                                for="new_password_confirmation"
                                class="form-label"
                            >
                                Confirm New Password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="new_password_confirmation"
                                name="new_password_confirmation"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Change Password
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>
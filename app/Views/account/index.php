<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="mb-4">
                <h1 class="h3 mb-1">
                    My Account
                </h1>

                <p class="text-muted mb-0">
                    Manage your KOBI account.
                </p>

                <span class="badge text-bg-success align-self-start align-self-md-center">
                    <?= e(ucfirst($user['status'] ?? 'active')) ?>
                </span>
            </div>

            <div class="card">

                <div class="card-body">

                    <h2 class="h5 mb-4">
                        Profile
                    </h2>

                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger" role="alert">
                            <?= e($error) ?>
                        </div>

                    <?php endif; ?>

                    <?php if (
                        isset($_GET['profile_updated']) &&
                        $_GET['profile_updated'] === '1'
                    ): ?>

                        <div class="alert alert-success" role="alert">
                            Your profile has been updated successfully.
                        </div>

                    <?php endif; ?>

                    <form
                        method="POST"
                        action="<?= e(url('/account/profile')) ?>"
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
                                value="<?= e($user['name'] ?? '') ?>"
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
                                value="<?= e($user['email'] ?? '') ?>"
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

                <div class="card mt-4">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

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
                                href="<?= e(url('/account/saved-diseases')) ?>"
                                class="btn btn-outline-primary"
                            >
                                <i class="bi bi-bookmark me-1"></i>
                                View Saved Diseases
                            </a>

                        </div>

                    </div>

                </div>

                <div class="card mt-4">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h2 class="h5 mb-2">
                                    <i class="bi bi-sliders me-2"></i>
                                    Treatment Preferences
                                </h2>

                                <p class="text-muted mb-0">
                                    Choose the order in which treatment approaches
                                    appear on disease pages.
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

                <div class="card mt-4">

                    <div class="card-body">

                    <h2 class="h5 mb-4">
                        Change Password
                    </h2>

                    <?php if (!empty($passwordError)): ?>

                        <div class="alert alert-danger" role="alert">
                            <?= e($passwordError) ?>
                        </div>

                    <?php endif; ?>

                    <?php if (
                        isset($_GET['password_updated']) &&
                        $_GET['password_updated'] === '1'
                    ): ?>

                        <div class="alert alert-success" role="alert">
                            Your password has been changed successfully.
                        </div>

                    <?php endif; ?>

                    <form
                        method="POST"
                        action="<?= e(url('/account/password')) ?>"
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
<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?= url(
                '/account/health-records/' .
                (int) $profile['id']
            ) ?>"
            class="btn btn-outline-secondary mb-3"
        >
            ← Back to Health Record
        </a>

        <h1 class="mb-1">
            Edit Health Profile
        </h1>

        <p class="text-muted mb-0">
            Update basic profile information.
        </p>

    </div>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <h4 class="mb-0">
                Profile Information
            </h4>

        </div>

        <div class="card-body">

            <form
                method="POST"
                action="<?= url(
                    '/account/health-records/' .
                    (int) $profile['id']
                ) ?>"
                novalidate
            >

                <?= csrfField() ?>


                <div class="mb-4">

                    <label
                        for="full_name"
                        class="form-label"
                    >
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-control"
                        maxlength="150"
                        value="<?= e(
                            $profile['full_name'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <div class="mb-4">

                    <label
                        for="date_of_birth"
                        class="form-label"
                    >
                        Date of Birth
                    </label>

                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        class="form-control"
                        value="<?= e(
                            $profile['date_of_birth'] ?? ''
                        ) ?>"
                    >

                </div>


                <div class="mb-4">

                    <label
                        for="gender"
                        class="form-label"
                    >
                        Gender
                    </label>

                    <?php
                    $gender =
                        $profile['gender']
                        ?? 'unspecified';
                    ?>

                    <select
                        id="gender"
                        name="gender"
                        class="form-select"
                    >

                        <option
                            value="unspecified"
                            <?= $gender === 'unspecified'
                                ? 'selected'
                                : '' ?>
                        >
                            Prefer not to specify
                        </option>

                        <option
                            value="male"
                            <?= $gender === 'male'
                                ? 'selected'
                                : '' ?>
                        >
                            Male
                        </option>

                        <option
                            value="female"
                            <?= $gender === 'female'
                                ? 'selected'
                                : '' ?>
                        >
                            Female
                        </option>

                        <option
                            value="other"
                            <?= $gender === 'other'
                                ? 'selected'
                                : '' ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <div class="d-flex gap-2">

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            (int) $profile['id']
                        ) ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
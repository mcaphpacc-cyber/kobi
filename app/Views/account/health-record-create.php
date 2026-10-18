<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?= url('/account/health-records') ?>"
            class="btn btn-outline-secondary mb-3"
        >
            ← Back to Health Records
        </a>

        <h1 class="mb-1">
            Add Family Health Profile
        </h1>

        <p class="text-muted mb-0">
            Create a health profile for a family member.
        </p>

    </div>


    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">

        <div class="card-header bg-white">
            <h4 class="mb-0">
                Family Member Information
            </h4>
        </div>

        <div class="card-body">

            <form
                method="POST"
                action="<?= url('/account/health-records') ?>"
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
                        value="<?= e($old['full_name'] ?? '') ?>"
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
                        value="<?= e($old['date_of_birth'] ?? '') ?>"
                    >

                </div>


                <div class="mb-4">

                    <label
                        for="gender"
                        class="form-label"
                    >
                        Gender
                    </label>

                    <select
                        id="gender"
                        name="gender"
                        class="form-select"
                    >
                        <option value="unspecified" <?= ($old['gender'] ?? 'unspecified') === 'unspecified' ? 'selected' : '' ?>>
                            Prefer not to specify
                        </option>

                        <option value="male" <?= ($old['gender'] ?? '') === 'male' ? 'selected' : '' ?>>
                            Male
                        </option>

                        <option value="female" <?= ($old['gender'] ?? '') === 'female' ? 'selected' : '' ?>>
                            Female
                        </option>

                        <option value="other" <?= ($old['gender'] ?? '') === 'other' ? 'selected' : '' ?>>
                            Other
                        </option>
                    </select>

                </div>


                <div class="alert alert-info">

                    <strong>Privacy:</strong>

                    This profile will be managed by you.
                    You can add and manage its health information
                    later.

                </div>


                <div class="d-flex gap-2">

                    <a
                        href="<?= url('/account/health-records') ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Create Health Profile
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

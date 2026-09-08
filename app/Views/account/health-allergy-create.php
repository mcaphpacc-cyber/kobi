<div class="container py-4">

    <div class="mb-4">

        <div class="text-muted small mb-1">
            Health Record
        </div>

        <h1 class="h3 mb-1">
            <?= e($profile['full_name']) ?>
        </h1>

        <div class="text-muted">
            Add Allergy
        </div>

    </div>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <form
                method="post"
                action="<?= url(
                    '/account/health-records/' .
                    $profile['id'] .
                    '/allergies'
                ) ?>"
            >

                <?= csrfField() ?>


                <div class="mb-3">

                    <label
                        for="allergen"
                        class="form-label"
                    >
                        Allergen
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="allergen"
                        name="allergen"
                        value="<?= e(
                            $_POST['allergen'] ?? ''
                        ) ?>"
                        required
                        maxlength="255"
                        placeholder="e.g. Penicillin, Peanuts, Dust"
                    >

                    <div class="form-text">
                        Enter the substance, medicine, food or other item
                        known to cause an allergic reaction.
                    </div>

                </div>


                <div class="mb-3">

                    <label
                        for="category"
                        class="form-label"
                    >
                        Category
                        <span class="text-danger">*</span>
                    </label>

                    <?php
                    $selectedCategory =
                        $_POST['category'] ?? 'other';
                    ?>

                    <select
                        class="form-select"
                        id="category"
                        name="category"
                        required
                    >

                        <option
                            value="medication"
                            <?= $selectedCategory === 'medication'
                                ? 'selected'
                                : '' ?>
                        >
                            Medication
                        </option>

                        <option
                            value="food"
                            <?= $selectedCategory === 'food'
                                ? 'selected'
                                : '' ?>
                        >
                            Food
                        </option>

                        <option
                            value="environmental"
                            <?= $selectedCategory === 'environmental'
                                ? 'selected'
                                : '' ?>
                        >
                            Environmental
                        </option>

                        <option
                            value="other"
                            <?= $selectedCategory === 'other'
                                ? 'selected'
                                : '' ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <div class="mb-3">

                    <label
                        for="reaction"
                        class="form-label"
                    >
                        Reaction
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="reaction"
                        name="reaction"
                        value="<?= e(
                            $_POST['reaction'] ?? ''
                        ) ?>"
                        maxlength="500"
                        placeholder="e.g. Rash, swelling, breathing difficulty"
                    >

                    <div class="form-text">
                        Describe the reaction that occurred, if known.
                    </div>

                </div>


                <div class="mb-3">

                    <label
                        for="severity"
                        class="form-label"
                    >
                        Severity
                    </label>

                    <?php
                    $selectedSeverity =
                        $_POST['severity'] ?? '';
                    ?>

                    <select
                        class="form-select"
                        id="severity"
                        name="severity"
                    >

                        <option value="">
                            Not specified
                        </option>

                        <option
                            value="mild"
                            <?= $selectedSeverity === 'mild'
                                ? 'selected'
                                : '' ?>
                        >
                            Mild
                        </option>

                        <option
                            value="moderate"
                            <?= $selectedSeverity === 'moderate'
                                ? 'selected'
                                : '' ?>
                        >
                            Moderate
                        </option>

                        <option
                            value="severe"
                            <?= $selectedSeverity === 'severe'
                                ? 'selected'
                                : '' ?>
                        >
                            Severe
                        </option>

                        <option
                            value="life-threatening"
                            <?= $selectedSeverity === 'life-threatening'
                                ? 'selected'
                                : '' ?>
                        >
                            Life-threatening
                        </option>

                    </select>

                    <div class="form-text">
                        Record the known or reported severity.
                        KOBI does not determine severity automatically.
                    </div>

                </div>


                <div class="mb-3">

                    <label
                        for="identified_on"
                        class="form-label"
                    >
                        Identified On
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="identified_on"
                        name="identified_on"
                        value="<?= e(
                            $_POST['identified_on'] ?? ''
                        ) ?>"
                        max="<?= date('Y-m-d') ?>"
                    >

                    <div class="form-text">
                        Optional. Enter the date when the allergy
                        or reaction was identified, if known.
                    </div>

                </div>


                <div class="mb-4">

                    <label
                        for="notes"
                        class="form-label"
                    >
                        Notes
                    </label>

                    <textarea
                        class="form-control"
                        id="notes"
                        name="notes"
                        rows="4"
                        placeholder="Add any additional information about this allergy..."
                    ><?= e(
                        $_POST['notes'] ?? ''
                    ) ?></textarea>

                </div>


                <div class="d-flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-check-lg me-1"></i>
                        Save Allergy
                    </button>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            $profile['id'] .
                            '/allergies'
                        ) ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>


    <div class="mt-4">

        <a
            href="<?= url(
                '/account/health-records/' .
                $profile['id']
            ) ?>"
            class="btn btn-outline-secondary"
        >
            ← Back to Health Record
        </a>

    </div>

</div>
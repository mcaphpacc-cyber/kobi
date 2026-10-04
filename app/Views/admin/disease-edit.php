<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="h3 mb-1">
                Edit Disease
            </h1>

            <div class="text-muted">
                <?= htmlspecialchars(
                    $disease['disease_en']
                ) ?>
            </div>

        </div>

        <a
            href="<?= url(
                'disease/' .
                $disease['slug']
            ) ?>"
            class="btn btn-outline-secondary"
            target="_blank"
        >
            View Disease
        </a>

    </div>

    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <?php if (
        isset($_GET['saved'])
        &&
        $_GET['saved'] === '1'
    ): ?>

        <div class="alert alert-success">
            Disease metadata updated successfully.
        </div>

    <?php endif; ?>

    <form
        method="post"
        action="<?= url(
            'admin/diseases/' .
            (int) $disease['id'] .
            '/update'
        ) ?>"
    >

        <?= csrfField() ?>

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <h2 class="h5 mb-4">
                    Disease Classification
                </h2>

                <div class="row g-4">

                    <div class="col-md-6">

                        <label
                            for="severity_level"
                            class="form-label"
                        >
                            Severity
                        </label>

                        <select
                            id="severity_level"
                            name="severity_level"
                            class="form-select"
                            required
                        >

                            <?php
                            $severity =
                                $disease['severity_level']
                                ?? 'moderate';
                            ?>

                            <option
                                value="mild"
                                <?= $severity === 'mild'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Mild
                            </option>

                            <option
                                value="moderate"
                                <?= $severity === 'moderate'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Moderate
                            </option>

                            <option
                                value="urgent"
                                <?= $severity === 'urgent'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Urgent
                            </option>

                            <option
                                value="emergency"
                                <?= $severity === 'emergency'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Emergency
                            </option>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label
                            for="urgency_level"
                            class="form-label"
                        >
                            Urgency
                        </label>

                        <?php
                        $urgency =
                            $disease['urgency_level']
                            ?? '';
                        ?>

                        <select
                            id="urgency_level"
                            name="urgency_level"
                            class="form-select"
                        >

                            <option value="">
                                Not yet classified
                            </option>

                            <option
                                value="routine"
                                <?= $urgency === 'routine'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Routine
                            </option>

                            <option
                                value="soon"
                                <?= $urgency === 'soon'
                                    ? 'selected'
                                    : '' ?>
                            >
                                See a Doctor Soon
                            </option>

                            <option
                                value="urgent"
                                <?= $urgency === 'urgent'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Urgent
                            </option>

                            <option
                                value="emergency"
                                <?= $urgency === 'emergency'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Emergency
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <h2 class="h5 mb-2">
                    Urgency Guidance
                </h2>

                <p class="text-muted small">
                    Use short, disease-specific educational
                    guidance. Do not provide individualized
                    diagnosis or treatment instructions.
                </p>

                <div class="mb-4">

                    <label
                        for="urgency_note_en"
                        class="form-label"
                    >
                        Urgency Note — English
                    </label>

                    <textarea
                        id="urgency_note_en"
                        name="urgency_note_en"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars(
                        $disease['urgency_note_en'] ?? ''
                    ) ?></textarea>

                </div>

                <div>

                    <label
                        for="urgency_note_hi"
                        class="form-label"
                    >
                        Urgency Note — Hindi
                    </label>

                    <textarea
                        id="urgency_note_hi"
                        name="urgency_note_hi"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars(
                        $disease['urgency_note_hi'] ?? ''
                    ) ?></textarea>

                </div>

            </div>

        </div>

        <div class="d-flex justify-content-end gap-2">

            <a
                href="<?= url(
                    'disease/' .
                    $disease['slug']
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
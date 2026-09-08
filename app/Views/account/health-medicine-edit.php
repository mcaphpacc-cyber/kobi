<div class="container py-4">

    <div class="mb-4">

        <div class="text-muted small mb-1">
            Health Record
        </div>

        <h1 class="h3 mb-1">
            <?= e($profile['full_name']) ?>
        </h1>

        <div class="text-muted">
            Edit Medicine
        </div>

    </div>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <?php
            $formData = $_POST ?: $medicine;

            $selectedStatus =
                $formData['status'] ?? 'current';
            ?>


            <form
                method="post"
                action="<?= url(
                    '/account/health-records/' .
                    $profile['id'] .
                    '/medicines/' .
                    $medicine['id'] .
                    '/update'
                ) ?>"
            >

                <?= csrfField() ?>


                <div class="mb-3">

                    <label
                        for="brand_name"
                        class="form-label"
                    >
                        Brand Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="brand_name"
                        name="brand_name"
                        value="<?= e(
                            $formData['brand_name'] ?? ''
                        ) ?>"
                        required
                        maxlength="255"
                    >

                    <div class="form-text">
                        Enter the medicine's brand or trade name.
                    </div>

                </div>


                <div class="mb-3">

                    <label
                        for="generic_name"
                        class="form-label"
                    >
                        Salt / Generic Name
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="generic_name"
                        name="generic_name"
                        value="<?= e(
                            $formData['generic_name'] ?? ''
                        ) ?>"
                        maxlength="500"
                    >

                    <div class="form-text">
                        Enter the salt / generic name if known or verified.
                    </div>

                </div>


                <div class="row g-3">

                    <div class="col-md-6">

                        <label
                            for="strength"
                            class="form-label"
                        >
                            Strength
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="strength"
                            name="strength"
                            value="<?= e(
                                $formData['strength'] ?? ''
                            ) ?>"
                            maxlength="255"
                            placeholder="e.g. 8 mg"
                        >

                    </div>


                    <div class="col-md-6">

                        <label
                            for="dose"
                            class="form-label"
                        >
                            Dose
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="dose"
                            name="dose"
                            value="<?= e(
                                $formData['dose'] ?? ''
                            ) ?>"
                            maxlength="255"
                            placeholder="e.g. 1 capsule"
                        >

                    </div>

                </div>


                <div class="row g-3 mt-0">

                    <div class="col-md-6">

                        <label
                            for="frequency"
                            class="form-label"
                        >
                            Frequency
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="frequency"
                            name="frequency"
                            value="<?= e(
                                $formData['frequency'] ?? ''
                            ) ?>"
                            maxlength="255"
                            placeholder="e.g. Once daily"
                        >

                    </div>


                    <div class="col-md-6">

                        <label
                            for="route"
                            class="form-label"
                        >
                            Route
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="route"
                            name="route"
                            value="<?= e(
                                $formData['route'] ?? ''
                            ) ?>"
                            maxlength="100"
                            placeholder="e.g. Oral"
                        >

                    </div>

                </div>


                <div class="row g-3 mt-0">

                    <div class="col-md-6">

                        <label
                            for="status"
                            class="form-label"
                        >
                            Status
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-select"
                            id="status"
                            name="status"
                            required
                        >

                            <option
                                value="current"
                                <?= $selectedStatus === 'current'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Current
                            </option>

                            <option
                                value="stopped"
                                <?= $selectedStatus === 'stopped'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Stopped
                            </option>

                            <option
                                value="historical"
                                <?= $selectedStatus === 'historical'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Historical
                            </option>

                        </select>

                    </div>


                    <div class="col-md-6">

                        <label
                            for="started_on"
                            class="form-label"
                        >
                            Started On
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="started_on"
                            name="started_on"
                            value="<?= e(
                                $formData['started_on'] ?? ''
                            ) ?>"
                            max="<?= date('Y-m-d') ?>"
                        >

                    </div>

                </div>


                <div
                    class="mb-3 mt-3"
                    id="stoppedOnWrapper"
                >

                    <label
                        for="stopped_on"
                        class="form-label"
                    >
                        Stopped On
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="stopped_on"
                        name="stopped_on"
                        value="<?= e(
                            $formData['stopped_on'] ?? ''
                        ) ?>"
                        max="<?= date('Y-m-d') ?>"
                    >

                    <div class="form-text">
                        Required when the medicine status is Stopped.
                    </div>

                </div>


                <div class="mb-3">

                    <label
                        for="prescribed_by"
                        class="form-label"
                    >
                        Prescribed By
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="prescribed_by"
                        name="prescribed_by"
                        value="<?= e(
                            $formData['prescribed_by'] ?? ''
                        ) ?>"
                        maxlength="255"
                        placeholder="e.g. Dr. Sharma / Apollo Hospital"
                    >

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
                        placeholder="Add any additional information about this medicine..."
                    ><?= e(
                        $formData['notes'] ?? ''
                    ) ?></textarea>

                </div>


                <div class="d-flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-check-lg me-1"></i>
                        Update Medicine
                    </button>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            $profile['id'] .
                            '/medicines'
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
                $profile['id'] .
                '/medicines'
            ) ?>"
            class="btn btn-outline-secondary"
        >
            ← Back to Medicines
        </a>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const status =
        document.getElementById('status');

    const stoppedOnWrapper =
        document.getElementById('stoppedOnWrapper');

    const stoppedOn =
        document.getElementById('stopped_on');


    function updateStoppedDateVisibility()
    {
        if (status.value === 'current')
        {
            stoppedOnWrapper.classList.add('d-none');
            stoppedOn.value = '';
            stoppedOn.required = false;
        }
        else if (status.value === 'stopped')
        {
            stoppedOnWrapper.classList.remove('d-none');
            stoppedOn.required = true;
        }
        else
        {
            stoppedOnWrapper.classList.remove('d-none');
            stoppedOn.required = false;
        }
    }


    status.addEventListener(
        'change',
        updateStoppedDateVisibility
    );


    updateStoppedDateVisibility();

});
</script>
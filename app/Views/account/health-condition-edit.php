<div class="container py-4">

    <div class="mb-4">

        <div class="text-muted small mb-1">
            Health Record
        </div>

        <h1 class="h3 mb-1">
            <?= e($profile['full_name']) ?>
        </h1>

        <div class="text-muted">
            Edit Medical Condition
        </div>

    </div>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <?php
    $conditionName =
        $_POST['condition_name']
        ?? $condition['condition_name']
        ?? '';

    $status =
        $_POST['status']
        ?? $condition['status']
        ?? 'active';

    $diagnosedOn =
        $_POST['diagnosed_on']
        ?? $condition['diagnosed_on']
        ?? '';

    $resolvedOn =
        $_POST['resolved_on']
        ?? $condition['resolved_on']
        ?? '';

    $doctorHospital =
        $_POST['doctor_hospital']
        ?? $condition['doctor_hospital']
        ?? '';

    $notes =
        $_POST['notes']
        ?? $condition['notes']
        ?? '';
    ?>


    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <h2 class="h5 mb-0">
                Medical Condition
            </h2>

        </div>


        <div class="card-body">

            <form
                method="post"
                action="<?= url(
                    '/account/health-records/' .
                    (int) $profile['id'] .
                    '/conditions/' .
                    (int) $condition['id'] .
                    '/update'
                ) ?>"
            >

                <?= csrfField() ?>


                <!-- Condition Name -->

                <div class="mb-3">

                    <label
                        for="condition_name"
                        class="form-label"
                    >
                        Condition Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="condition_name"
                        name="condition_name"
                        value="<?= e($conditionName) ?>"
                        maxlength="255"
                        required
                    >

                </div>


                <!-- Status -->

                <div class="mb-3">

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
                            value="active"
                            <?= $status === 'active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="chronic"
                            <?= $status === 'chronic'
                                ? 'selected'
                                : '' ?>
                        >
                            Chronic
                        </option>

                        <option
                            value="historical"
                            <?= $status === 'historical'
                                ? 'selected'
                                : '' ?>
                        >
                            Historical
                        </option>

                        <option
                            value="resolved"
                            <?= $status === 'resolved'
                                ? 'selected'
                                : '' ?>
                        >
                            Resolved
                        </option>

                    </select>

                </div>


                <!-- Diagnosed On -->

                <div class="mb-3">

                    <label
                        for="diagnosed_on"
                        class="form-label"
                    >
                        Diagnosed On
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="diagnosed_on"
                        name="diagnosed_on"
                        value="<?= e($diagnosedOn) ?>"
                        max="<?= date('Y-m-d') ?>"
                    >

                </div>


                <!-- Resolved On -->

                <div
                    class="mb-3"
                    id="resolved-date-wrapper"
                >

                    <label
                        for="resolved_on"
                        class="form-label"
                    >
                        Resolved On
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="resolved_on"
                        name="resolved_on"
                        value="<?= e($resolvedOn) ?>"
                        max="<?= date('Y-m-d') ?>"
                    >

                    <div class="form-text">
                        Required when the condition status is
                        Resolved.
                    </div>

                </div>


                <!-- Doctor / Hospital -->

                <div class="mb-3">

                    <label
                        for="doctor_hospital"
                        class="form-label"
                    >
                        Doctor / Hospital
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="doctor_hospital"
                        name="doctor_hospital"
                        value="<?= e($doctorHospital) ?>"
                        maxlength="255"
                    >

                </div>


                <!-- Notes -->

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
                        rows="5"
                    ><?= e($notes) ?></textarea>

                </div>


                <!-- Actions -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Update Condition
                    </button>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            (int) $profile['id'] .
                            '/conditions'
                        ) ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const status =
        document.getElementById('status');

    const resolvedWrapper =
        document.getElementById('resolved-date-wrapper');

    const resolvedInput =
        document.getElementById('resolved_on');


    function updateResolvedDate()
    {
        const isResolved =
            status.value === 'resolved';

        resolvedWrapper.classList.toggle(
            'd-none',
            !isResolved
        );

        resolvedInput.required =
            isResolved;
    }


    status.addEventListener(
        'change',
        updateResolvedDate
    );

    updateResolvedDate();

});
</script>
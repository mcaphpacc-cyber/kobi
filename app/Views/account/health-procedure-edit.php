<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?= url(
                '/account/health-records/' .
                $profile['id'] .
                '/procedures'
            ) ?>"
            class="text-decoration-none"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Back to Surgeries &amp; Procedures
        </a>

        <h1 class="h3 mt-3 mb-1">
            Edit Surgery / Procedure
        </h1>

        <p class="text-muted mb-0">
            <?= e($profile['full_name']) ?>
        </p>

    </div>


    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <form
                method="POST"
                action="<?= url(
                    '/account/health-records/' .
                    $profile['id'] .
                    '/procedures/' .
                    $procedure['id'] .
                    '/update'
                ) ?>"
            >

                <?= csrfField() ?>


                <div class="row g-3">

                    <div class="col-md-8">

                        <label
                            for="procedure_name"
                            class="form-label"
                        >
                            Procedure / Surgery Name
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="procedure_name"
                            name="procedure_name"
                            maxlength="255"
                            value="<?= e(
                                $procedure['procedure_name']
                            ) ?>"
                            required
                            autofocus
                        >

                    </div>


                    <div class="col-md-4">

                        <label
                            for="procedure_type"
                            class="form-label"
                        >
                            Type
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-select"
                            id="procedure_type"
                            name="procedure_type"
                            required
                        >

                            <option
                                value="procedure"
                                <?= $procedure['procedure_type'] === 'procedure'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Procedure
                            </option>

                            <option
                                value="surgery"
                                <?= $procedure['procedure_type'] === 'surgery'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Surgery
                            </option>

                            <option
                                value="hospitalization"
                                <?= $procedure['procedure_type'] === 'hospitalization'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Hospitalization
                            </option>

                        </select>

                    </div>


                    <div class="col-md-4">

                        <label
                            for="performed_on"
                            class="form-label"
                        >
                            Performed On
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="performed_on"
                            name="performed_on"
                            value="<?= e(
                                $procedure['performed_on'] ?? ''
                            ) ?>"
                            max="<?= date('Y-m-d') ?>"
                        >

                        <div class="form-text">
                            Leave blank if the exact date is unknown.
                        </div>

                    </div>


                    <div class="col-md-8">

                        <label
                            for="hospital"
                            class="form-label"
                        >
                            Hospital
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="hospital"
                            name="hospital"
                            maxlength="255"
                            value="<?= e(
                                $procedure['hospital'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="col-md-6">

                        <label
                            for="doctor"
                            class="form-label"
                        >
                            Doctor
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="doctor"
                            name="doctor"
                            maxlength="255"
                            value="<?= e(
                                $procedure['doctor'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="col-md-6">

                        <label
                            for="reason"
                            class="form-label"
                        >
                            Reason / Indication
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="reason"
                            name="reason"
                            maxlength="500"
                            value="<?= e(
                                $procedure['reason'] ?? ''
                            ) ?>"
                            placeholder="Why was the procedure performed?"
                        >

                    </div>


                    <div class="col-12">

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
                            placeholder="Additional information about this procedure..."
                        ><?= e(
                            $procedure['notes'] ?? ''
                        ) ?></textarea>

                    </div>

                </div>


                <hr class="my-4">


                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            $profile['id'] .
                            '/procedures'
                        ) ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-check-lg me-1"></i>
                        Update Procedure
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
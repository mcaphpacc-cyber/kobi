<?php
$profile =
    $profile ?? [];

$error =
    $error ?? null;
?>

<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?= url('/account/health-records/' . (int) $profile['id']) ?>"
            class="btn btn-outline-secondary mb-3"
        >
            ← Back to Health Record
        </a>

        <div class="small text-muted mb-2">
            Health Records
            <span class="mx-1">/</span>
            <?= htmlspecialchars(
                $profile['full_name'] ?? ''
            ) ?>
            <span class="mx-1">/</span>
            Medical Documents
        </div>

        <h1 class="h3 mb-1">
            Upload Medical Document
        </h1>

        <p class="text-muted mb-0">
            Add a medical document to this health record.
        </p>

    </div>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="alert alert-info">

                <div class="fw-semibold mb-1">
                    Health Record
                </div>

                <div class="fs-5">
                    <?= htmlspecialchars(
                        $profile['full_name'] ?? ''
                    ) ?>
                </div>

                <div class="small text-muted mt-1">
                    This document will be stored in this health record.
                </div>

            </div>

            <form
                method="post"
                action="<?= url(
                    '/account/health-records/' .
                    $profile['id'] .
                    '/documents'
                ) ?>"
                enctype="multipart/form-data"
            >

                <?= csrfField() ?>

                <div class="mb-3">

                    <label
                        for="document"
                        class="form-label"
                    >
                        Medical Document
                    </label>

                    <input
                        type="file"
                        name="document"
                        id="document"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        required
                    >

                    <div class="form-text">
                        Allowed formats: PDF, JPG, JPEG, PNG, WEBP.
                        Maximum size: 20 MB.
                    </div>

                </div>

                <div class="mb-3">

                    <label
                        for="title"
                        class="form-label"
                    >
                        Document Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        id="title"
                        class="form-control"
                        maxlength="255"
                        required
                        value="<?= htmlspecialchars(
                            $_POST['title'] ?? ''
                        ) ?>"
                    >

                    <div class="form-text">
                        Example: Blood Test Report - January 2026
                    </div>

                </div>

                <div class="mb-3">

                    <label
                        for="document_type"
                        class="form-label"
                    >
                        Document Type
                    </label>

                    <select
                        name="document_type"
                        id="document_type"
                        class="form-select"
                        required
                    >

                        <?php
                        $selectedType =
                            $_POST['document_type']
                            ?? 'other';
                        ?>

                        <option
                            value="lab_report"
                            <?= $selectedType === 'lab_report'
                                ? 'selected'
                                : '' ?>
                        >
                            Lab Report
                        </option>

                        <option
                            value="imaging"
                            <?= $selectedType === 'imaging'
                                ? 'selected'
                                : '' ?>
                        >
                            Imaging
                        </option>

                        <option
                            value="prescription"
                            <?= $selectedType === 'prescription'
                                ? 'selected'
                                : '' ?>
                        >
                            Prescription
                        </option>

                        <option
                            value="discharge_summary"
                            <?= $selectedType === 'discharge_summary'
                                ? 'selected'
                                : '' ?>
                        >
                            Discharge Summary
                        </option>

                        <option
                            value="medical_record"
                            <?= $selectedType === 'medical_record'
                                ? 'selected'
                                : '' ?>
                        >
                            Medical Record
                        </option>

                        <option
                            value="surgery_procedure"
                            <?= $selectedType === 'surgery_procedure'
                                ? 'selected'
                                : '' ?>
                        >
                            Surgery / Procedure
                        </option>

                        <option
                            value="consultation"
                            <?= $selectedType === 'consultation'
                                ? 'selected'
                                : '' ?>
                        >
                            Consultation
                        </option>

                        <option
                            value="other"
                            <?= $selectedType === 'other'
                                ? 'selected'
                                : '' ?>
                        >
                            Other
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label
                        for="document_date"
                        class="form-label"
                    >
                        Document Date
                    </label>

                    <input
                        type="date"
                        name="document_date"
                        id="document_date"
                        class="form-control"
                        max="<?= date('Y-m-d') ?>"
                        value="<?= htmlspecialchars(
                            $_POST['document_date'] ?? ''
                        ) ?>"
                    >

                    <div class="form-text">
                        The date shown on the medical document,
                        if known.
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            for="hospital"
                            class="form-label"
                        >
                            Hospital / Clinic
                        </label>

                        <input
                            type="text"
                            name="hospital"
                            id="hospital"
                            class="form-control"
                            maxlength="255"
                            value="<?= htmlspecialchars(
                                $_POST['hospital'] ?? ''
                            ) ?>"
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label
                            for="doctor"
                            class="form-label"
                        >
                            Doctor
                        </label>

                        <input
                            type="text"
                            name="doctor"
                            id="doctor"
                            class="form-control"
                            maxlength="255"
                            value="<?= htmlspecialchars(
                                $_POST['doctor'] ?? ''
                            ) ?>"
                        >

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
                        name="notes"
                        id="notes"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars(
                        $_POST['notes'] ?? ''
                    ) ?></textarea>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Upload Document
                    </button>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            $profile['id'] .
                            '/documents'
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
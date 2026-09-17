<?php
$profile =
    $profile ?? [];

$document =
    $document ?? [];

$error =
    $error ?? null;

$documentType =
    $document['document_type']
    ?? 'other';
?>

<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?= url(
                '/account/health-records/' .
                (int) $profile['id'] .
                '/documents'
            ) ?>"
            class="btn btn-outline-secondary mb-3"
        >
            ← Back to Medical Documents
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
            Edit Medical Document
        </h1>

        <p class="text-muted mb-0">
            Update the medical information associated with this document.
        </p>

    </div>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="mb-4">

                <div class="small text-muted">
                    Stored File
                </div>

                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        $document['original_filename']
                        ?? ''
                    ) ?>
                </div>

                <div class="small text-muted">
                    The uploaded file itself cannot be changed
                    here. You can update its medical information below.
                </div>

            </div>

            <form
                method="post"
                action="<?= url(
                    '/account/health-records/' .
                    $profile['id'] .
                    '/documents/' .
                    $document['id'] .
                    '/update'
                ) ?>"
            >

                <?= csrfField() ?>

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
                            $_POST['title']
                            ?? $document['title']
                            ?? ''
                        ) ?>"
                    >

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

                        <option
                            value="lab_report"
                            <?= $documentType === 'lab_report'
                                ? 'selected'
                                : '' ?>
                        >
                            Lab Report
                        </option>

                        <option
                            value="imaging"
                            <?= $documentType === 'imaging'
                                ? 'selected'
                                : '' ?>
                        >
                            Imaging
                        </option>

                        <option
                            value="prescription"
                            <?= $documentType === 'prescription'
                                ? 'selected'
                                : '' ?>
                        >
                            Prescription
                        </option>

                        <option
                            value="discharge_summary"
                            <?= $documentType === 'discharge_summary'
                                ? 'selected'
                                : '' ?>
                        >
                            Discharge Summary
                        </option>

                        <option
                            value="medical_record"
                            <?= $documentType === 'medical_record'
                                ? 'selected'
                                : '' ?>
                        >
                            Medical Record
                        </option>

                        <option
                            value="surgery_procedure"
                            <?= $documentType === 'surgery_procedure'
                                ? 'selected'
                                : '' ?>
                        >
                            Surgery / Procedure
                        </option>

                        <option
                            value="consultation"
                            <?= $documentType === 'consultation'
                                ? 'selected'
                                : '' ?>
                        >
                            Consultation
                        </option>

                        <option
                            value="other"
                            <?= $documentType === 'other'
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
                            $_POST['document_date']
                            ?? $document['document_date']
                            ?? ''
                        ) ?>"
                    >

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
                                $_POST['hospital']
                                ?? $document['hospital']
                                ?? ''
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
                                $_POST['doctor']
                                ?? $document['doctor']
                                ?? ''
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
                        $_POST['notes']
                        ?? $document['notes']
                        ?? ''
                    ) ?></textarea>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
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
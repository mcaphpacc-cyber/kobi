<?php
$profile =
    $profile ?? [];

$documents =
    $documents ?? [];

$documentTypeLabels = [
    'lab_report' =>
        'Lab Report',

    'imaging' =>
        'Imaging',

    'prescription' =>
        'Prescription',

    'discharge_summary' =>
        'Discharge Summary',

    'medical_record' =>
        'Medical Record',

    'surgery_procedure' =>
        'Surgery / Procedure',

    'consultation' =>
        'Consultation',

    'other' =>
        'Other'
];
?>

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

            <div class="small text-muted mb-2">
                Health Records
                <span class="mx-1">/</span>
                <?= htmlspecialchars(
                    $profile['full_name'] ?? ''
                ) ?>
                <span class="mx-1">/</span>
                Medical Documents
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>

                    <h1 class="h3 mb-1">
                        Medical Documents
                    </h1>

                    <p class="text-muted mb-0">
                        Manage medical reports, prescriptions,
                        scans and other documents for this health record.
                    </p>

                </div>

                <?php if (
                    in_array(
                        $profile['role'] ?? null,
                        ['owner', 'editor'],
                        true
                    )
                ): ?>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            $profile['id'] .
                            '/documents/create'
                        ) ?>"
                        class="btn btn-primary"
                    >
                        Upload Document
                    </a>

                <?php endif; ?>

            </div>

        </div>

    <?php if (empty($documents)): ?>

        <div class="card shadow-sm">

            <div class="card-body text-center py-5">

                <h2 class="h5">
                    No Medical Documents
                </h2>

                <p class="text-muted mb-4">
                    Medical reports, prescriptions and other
                    health documents will appear here.
                </p>

                <?php if (
                    in_array(
                        $profile['role'] ?? null,
                        ['owner', 'editor'],
                        true
                    )
                ): ?>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            $profile['id'] .
                            '/documents/create'
                        ) ?>"
                        class="btn btn-outline-primary"
                    >
                        Upload First Document
                    </a>

                <?php endif; ?>

            </div>

        </div>

    <?php else: ?>

        <div class="card shadow-sm">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>

                            <th>
                                Document
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Document Date
                            </th>

                            <th>
                                Hospital / Doctor
                            </th>

                            <th class="text-end">
                                Actions
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach (
                        $documents
                        as $document
                    ): ?>

                        <?php
                        $documentType =
                            $documentTypeLabels[
                                $document['document_type']
                            ]
                            ?? 'Other';
                        ?>

                        <tr>

                            <td>

                                <div class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $document['title']
                                    ) ?>
                                </div>

                                <div class="small text-muted">
                                    <?= htmlspecialchars(
                                        $document['original_filename']
                                    ) ?>
                                </div>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $documentType
                                ) ?>
                            </td>

                            <td>

                                <?php if (
                                    !empty(
                                        $document['document_date']
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $document['document_date']
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not specified
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php
                                $hospital =
                                    trim(
                                        (string) (
                                            $document['hospital']
                                            ?? ''
                                        )
                                    );

                                $doctor =
                                    trim(
                                        (string) (
                                            $document['doctor']
                                            ?? ''
                                        )
                                    );
                                ?>

                                <?php if (
                                    $hospital !== ''
                                ): ?>

                                    <div>
                                        <?= htmlspecialchars(
                                            $hospital
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                                <?php if (
                                    $doctor !== ''
                                ): ?>

                                    <div class="small text-muted">
                                        <?= htmlspecialchars(
                                            $doctor
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                                <?php if (
                                    $hospital === ''
                                    &&
                                    $doctor === ''
                                ): ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="text-end">

                                <a
                                    href="<?= url(
                                        '/account/health-records/' .
                                        $profile['id'] .
                                        '/documents/' .
                                        $document['id'] .
                                        '/file'
                                    ) ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    View
                                </a>

                                <?php if (
                                    in_array(
                                        $profile['role'] ?? null,
                                        ['owner', 'editor'],
                                        true
                                    )
                                ): ?>

                                    <a
                                        href="<?= url(
                                            '/account/health-records/' .
                                            $profile['id'] .
                                            '/documents/' .
                                            $document['id'] .
                                            '/edit'
                                        ) ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Edit
                                    </a>

                                <?php endif; ?>

                                <?php if (
                                    ($profile['role'] ?? null)
                                    === 'owner'
                                ): ?>

                                    <form
                                        method="post"
                                        action="<?= url(
                                            '/account/health-records/' .
                                            $profile['id'] .
                                            '/documents/' .
                                            $document['id'] .
                                            '/delete'
                                        ) ?>"
                                        class="d-inline"
                                        onsubmit="return confirm(
                                            'Are you sure you want to delete this medical document?'
                                        );"
                                    >

                                        <?= csrfField() ?>

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</div>
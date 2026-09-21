<?php
$policyTypeLabels = [
    'individual' => 'Individual',
    'family_floater' => 'Family Floater',
    'employer' => 'Employer',
    'government' => 'Government',
    'supplementary' => 'Supplementary',
    'other' => 'Other'
];

$statusLabels = [
    'active' => 'Active',
    'expired' => 'Expired',
    'cancelled' => 'Cancelled',
    'pending' => 'Pending',
    'unknown' => 'Unknown'
];

$statusClasses = [
    'active' => 'success',
    'expired' => 'secondary',
    'cancelled' => 'danger',
    'pending' => 'warning',
    'unknown' => 'secondary'
];

$status =
    $policy['status'] ?? 'unknown';

$statusLabel =
    $statusLabels[$status] ?? ucfirst($status);

$statusClass =
    $statusClasses[$status] ?? 'secondary';

$policyType =
    $policyTypeLabels[
        $policy['policy_type'] ?? ''
    ] ?? 'Other';

$formatDate = static function (
    ?string $date
): string {
    if (!$date) {
        return 'Not specified';
    }

    $timestamp = strtotime($date);

    return $timestamp
        ? date('d M Y', $timestamp)
        : 'Not specified';
};

$sumInsured =
    $policy['sum_insured'] !== null
        ? number_format(
            (float) $policy['sum_insured'],
            2
        )
        : 'Not specified';

$currency =
    $policy['currency_code'] ?? 'INR';

$canManage =
    in_array(
        $profile['role'] ?? null,
        ['owner', 'editor'],
        true
    );
?>

<div class="container py-4">

    <div class="d-flex
                justify-content-between
                align-items-center
                flex-wrap
                gap-2
                mb-4">

        <div>
            <h1 class="h3 mb-1">
                Insurance Policy
            </h1>

            <p class="text-muted mb-0">
                <?= e($profile['full_name']) ?>
            </p>
        </div>

        <div class="d-flex gap-2">

            <?php if ($canManage): ?>

                <a
                    href="<?= url(
                        '/account/health-records/' .
                        (int) $profile['id'] .
                        '/insurance/' .
                        (int) $policy['id'] .
                        '/edit'
                    ) ?>"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil me-1"></i>
                    Edit
                </a>

            <?php endif; ?>

            <a
                href="<?= url(
                    '/account/health-records/' .
                    (int) $profile['id'] .
                    '/insurance'
                ) ?>"
                class="btn btn-outline-secondary"
            >
                Back
            </a>

        </div>

    </div>


    <!-- Policy Overview -->

    <div class="card shadow-sm mb-4">

        <div class="card-header
                    bg-white
                    d-flex
                    justify-content-between
                    align-items-center">

            <h2 class="h5 mb-0">
                Policy Overview
            </h2>

            <span class="badge
                         text-bg-<?= e($statusClass) ?>">

                <?= e($statusLabel) ?>

            </span>

        </div>

        <div class="card-body">

            <h3 class="h4 mb-4">
                <?= e(
                    $policy['policy_name']
                    ?: $policy['provider_name']
                    ?: 'Insurance Policy'
                ) ?>
            </h3>

            <div class="row g-4">

                <div class="col-md-6">

                    <div class="text-muted small">
                        Insurance Provider
                    </div>

                    <div class="fw-semibold">
                        <?= e(
                            $policy['provider_name']
                            ?: 'Not specified'
                        ) ?>
                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Policy Number
                    </div>

                    <div class="fw-semibold">
                        <?= e(
                            $policy['policy_number']
                            ?: 'Not specified'
                        ) ?>
                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Policy Type
                    </div>

                    <div class="fw-semibold">
                        <?= e($policyType) ?>
                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Sum Insured
                    </div>

                    <div class="fw-semibold">
                        <?= e($currency) ?>
                        <?= e($sumInsured) ?>
                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Start Date
                    </div>

                    <div class="fw-semibold">
                        <?= e(
                            $formatDate(
                                $policy['start_date'] ?? null
                            )
                        ) ?>
                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Expiry Date
                    </div>

                    <div class="fw-semibold">
                        <?= e(
                            $formatDate(
                                $policy['expiry_date'] ?? null
                            )
                        ) ?>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Renewal Notes -->

    <?php if (
        !empty($policy['renewal_notes'])
    ): ?>

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-white">

                <h2 class="h5 mb-0">
                    Renewal Notes
                </h2>

            </div>

            <div class="card-body">

                <?= nl2br(
                    e($policy['renewal_notes'])
                ) ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- General Notes -->

    <?php if (
        !empty($policy['general_notes'])
    ): ?>

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-white">

                <h2 class="h5 mb-0">
                    General Notes
                </h2>

            </div>

            <div class="card-body">

                <?= nl2br(
                    e($policy['general_notes'])
                ) ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- Future Sections -->

        <div class="card shadow-sm mb-4">

        <div class="card-header bg-white
                    d-flex
                    justify-content-between
                    align-items-center">

            <h2 class="h5 mb-0">
                Insured Members
            </h2>

            <?php if ($canManage): ?>

                <a
                    href="<?= url(
                        '/account/health-records/' .
                        (int) $profile['id'] .
                        '/insurance/' .
                        (int) $policy['id'] .
                        '/members/create'
                    ) ?>"
                    class="btn btn-sm btn-primary"
                >
                    Add Member
                </a>

            <?php endif; ?>

        </div>


        <div class="card-body">

            <?php if (empty($members)): ?>

                <p class="text-muted mb-0">
                    No insured members have been added yet.
                </p>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-bordered
                                align-middle mb-0">

                        <thead>

                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Member ID</th>
                                <th>Primary Insured</th>
                                <?php if ($canManage): ?>
                                    <th class="text-end">Actions</th>
                                <?php endif; ?>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $members
                                as $member
                            ): ?>

                                <tr>

                                    <td>
                                        <?= e(
                                            $member['member_name']
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            ucfirst(
                                                $member['relationship']
                                                ?? 'other'
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $member['member_id_number']
                                            ?: '—'
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if (
                                            !empty(
                                                $member[
                                                    'is_primary_insured'
                                                ]
                                            )
                                        ): ?>

                                            <span class="badge
                                                        text-bg-success">
                                                Yes
                                            </span>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                No
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <?php if ($canManage): ?>

                                        <td class="text-end">

                                            <a
                                                href="<?= url(
                                                    '/account/health-records/' .
                                                    (int) $profile['id'] .
                                                    '/insurance/' .
                                                    (int) $policy['id'] .
                                                    '/members/' .
                                                    (int) $member['id'] .
                                                    '/edit'
                                                ) ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Edit
                                            </a>

                                            <form
                                            method="POST"
                                            action="<?= url(
                                                '/account/health-records/' .
                                                (int) $profile['id'] .
                                                '/insurance/' .
                                                (int) $policy['id'] .
                                                '/members/' .
                                                (int) $member['id'] .
                                                '/delete'
                                            ) ?>"
                                            class="member-delete-form d-inline"
                                            data-confirm-message="Are you sure you want to remove this insured member?"
                                        >
                                            <?= csrfField() ?>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Delete
                                            </button>
                                        </form>

                                        </td>

                                    <?php endif; ?>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>


            <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">
                    Insurance Contacts
                </h2>

                <?php if ($canManage): ?>
                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            (int) $profile['id'] .
                            '/insurance/' .
                            (int) $policy['id'] .
                            '/contacts/create'
                        ) ?>"
                        class="btn btn-sm btn-primary"
                    >
                        Add Contact
                    </a>
                <?php endif; ?>
            </div>

            <div class="card-body">

                <?php if (empty($contacts)): ?>

                    <p class="text-muted mb-0">
                        No insurance contacts have been added yet.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Organization</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <?php if ($canManage): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($contacts as $contact): ?>
                                    <tr>
                                        <td>
                                            <?= e(
                                                ucfirst(
                                                    (string) ($contact['contact_type'] ?? '')
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= e(
                                                $contact['organization_name'] ?: '—'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= e(
                                                $contact['contact_person'] ?: '—'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= e(
                                                $contact['phone'] ?: '—'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= e(
                                                $contact['email'] ?: '—'
                                            ) ?>
                                        </td>
                                        <?php if ($canManage): ?>

                                        <td>
                                            <div class="d-flex gap-2">

                                                <a
                                                    href="<?= url(
                                                        '/account/health-records/' .
                                                        (int) $profile['id'] .
                                                        '/insurance/' .
                                                        (int) $policy['id'] .
                                                        '/contacts/' .
                                                        (int) $contact['id'] .
                                                        '/edit'
                                                    ) ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                >
                                                    Edit
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="<?= url(
                                                        '/account/health-records/' .
                                                        (int) $profile['id'] .
                                                        '/insurance/' .
                                                        (int) $policy['id'] .
                                                        '/contacts/' .
                                                        (int) $contact['id'] .
                                                        '/delete'
                                                    ) ?>"
                                                    class="contact-delete-form"
                                                    data-confirm-message="Are you sure you want to delete this insurance contact?"
                                                >
                                                    <?= csrfField() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                    >
                                                        Delete
                                                    </button>
                                                </form>

                                            </div>
                                        </td>

                                    <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            </div>
        </div>


    <!-- Linked Documents -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white
                    d-flex
                    justify-content-between
                    align-items-center">

            <h2 class="h5 mb-0">
                Linked Documents
            </h2>
            <?php if ($canManage): ?>
                <a
                    href="<?= e(
                        url(
                            '/account/health-records/'
                            . $profile['id']
                            . '/insurance/'
                            . $policy['id']
                            . '/documents/create'
                        )
                    ) ?>"
                    class="btn btn-sm btn-primary"
                >
                    <i class="bi bi-link-45deg"></i>
                    Link Document
                </a>
            <?php endif; ?>
            

        </div>

        <div class="card-body">

            <?php if (empty($documents)): ?>

                <p class="text-muted mb-0">
                    No documents have been linked to this insurance policy yet.
                </p>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-bordered
                                align-middle mb-0">

                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Document Date</th>
                                <th>Hospital / Doctor</th>
                                <th>Label</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($documents as $document): ?>

                                <?php
                                $hospital =
                                    trim(
                                        (string) (
                                            $document['hospital'] ?? ''
                                        )
                                    );

                                $doctor =
                                    trim(
                                        (string) (
                                            $document['doctor'] ?? ''
                                        )
                                    );
                                ?>

                                <tr>

                                    <td>

                                        <div class="fw-semibold">
                                            <?= e(
                                                $document['title'] ?? 'Untitled Document'
                                            ) ?>
                                        </div>

                                        <div class="small text-muted">
                                            <?= e(
                                                $document['original_filename'] ?? ''
                                            ) ?>
                                        </div>

                                    </td>

                                    <td>
                                        <?= e(
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    (string) (
                                                        $document['document_type']
                                                        ?? 'other'
                                                    )
                                                )
                                            )
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if (
                                            !empty(
                                                $document['document_date']
                                            )
                                        ): ?>

                                            <?= e(
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

                                        <?php if ($hospital !== ''): ?>

                                            <div>
                                                <?= e($hospital) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if ($doctor !== ''): ?>

                                            <div class="small text-muted">
                                                <?= e($doctor) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if (
                                            $hospital === '' &&
                                            $doctor === ''
                                        ): ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= e(
                                            $document['document_label']
                                            ?: '—'
                                        ) ?>
                                    </td>

                                    <td class="text-end">

                                        <a
                                            href="<?= url(
                                                '/account/health-records/' .
                                                (int) $profile['id'] .
                                                '/documents/' .
                                                (int) (
                                                    $document['document_id']
                                                    ?? 0
                                                ) .
                                                '/file'
                                            ) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            View
                                        </a>

                                        <?php if ($canManage): ?>
                                            <a
                                                href="<?= e(
                                                    url(
                                                        '/account/health-records/'
                                                        . $profile['id']
                                                        . '/insurance/'
                                                        . $policy['id']
                                                        . '/documents/'
                                                        . $document['id']
                                                        . '/edit'
                                                    )
                                                ) ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                <i class="bi bi-pencil"></i>
                                                Edit
                                            </a>
                                        <?php endif; ?>

                                        <?php if (($profile['role'] ?? null) === 'owner'): ?>
                                            <form
                                                method="POST"
                                                action="<?= e(
                                                    url(
                                                        '/account/health-records/'
                                                        . $profile['id']
                                                        . '/insurance/'
                                                        . $policy['id']
                                                        . '/documents/'
                                                        . $document['id']
                                                        . '/unlink'
                                                    )
                                                ) ?>"
                                                class="insurance-document-unlink-form d-inline"
                                            >
                                                <?= csrfField() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    <i class="bi bi-link-45deg"></i>
                                                    Unlink
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>
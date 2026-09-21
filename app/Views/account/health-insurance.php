
<div class="container py-4">

    <?php
    $canManage = in_array(
        $profile['role'] ?? null,
        ['owner', 'editor'],
        true
    );
    ?>

    <!-- Header -->

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <div class="text-muted small mb-1">
                Health Record
            </div>

            <h1 class="h3 mb-1">
                <?= e($profile['full_name']) ?>
            </h1>

            <div class="text-muted">
                Health Insurance
            </div>
        </div>

        <?php if ($canManage): ?>

            <a
                href="<?= url(
                    '/account/health-records/' .
                    (int) $profile['id'] .
                    '/insurance/create'
                ) ?>"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Add Insurance Policy
            </a>

        <?php endif; ?>

    </div>


    <!-- Empty State -->

    <?php if (empty($policies)): ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <div class="mb-3">
                    <i class="bi bi-shield-check fs-1 text-muted"></i>
                </div>

                <h2 class="h5">
                    No insurance policies recorded
                </h2>

                <p class="text-muted mb-4">
                    Add health insurance details to keep
                    important policy information organized.
                </p>

                <?php if ($canManage): ?>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            (int) $profile['id'] .
                            '/insurance/create'
                        ) ?>"
                        class="btn btn-primary"
                    >
                        Add Insurance Policy
                    </a>

                <?php endif; ?>

            </div>

        </div>


    <!-- Policy Listing -->

    <?php else: ?>

        <div class="row g-3">

            <?php foreach ($policies as $policy): ?>

                <?php
                $statusClass = match (
                    $policy['status'] ?? 'unknown'
                ) {
                    'active' => 'text-bg-success',
                    'expired' => 'text-bg-warning',
                    'cancelled' => 'text-bg-danger',
                    'pending' => 'text-bg-info',
                    default => 'text-bg-secondary'
                };

                $statusLabel = ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $policy['status'] ?? 'unknown'
                    )
                );

                $policyTypeLabel = ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $policy['policy_type'] ?? 'other'
                    )
                );

                $startDate = !empty($policy['start_date'])
                    ? date(
                        'd M Y',
                        strtotime($policy['start_date'])
                    )
                    : 'Not specified';

                $expiryDate = !empty($policy['expiry_date'])
                    ? date(
                        'd M Y',
                        strtotime($policy['expiry_date'])
                    )
                    : 'Not specified';

                $sumInsured = $policy['sum_insured'] !== null &&
                    $policy['sum_insured'] !== ''
                    ? number_format(
                        (float) $policy['sum_insured'],
                        2
                    )
                    : 'Not specified';

                $currencyCode = $policy['currency_code']
                    ?? 'INR';
                ?>

                <div class="col-12">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <!-- Policy Header -->

                            <div class="d-flex flex-wrap justify-content-between gap-3">

                                <div>

                                    <h2 class="h5 mb-2">

                                        <?= e(
                                            $policy['provider_name']
                                                ?: 'Insurance Provider Not Specified'
                                        ) ?>

                                    </h2>

                                    <?php if (
                                        !empty($policy['policy_name'])
                                    ): ?>

                                        <div class="text-muted mb-2">
                                            <?= e(
                                                $policy['policy_name']
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                    <span class="badge <?= e($statusClass) ?>">
                                        <?= e($statusLabel) ?>
                                    </span>

                                    <a
                                        href="<?= url(
                                            '/account/health-records/' .
                                            (int) $profile['id'] .
                                            '/insurance/' .
                                            (int) $policy['id']
                                        ) ?>"
                                        class="btn btn-outline-primary"
                                    >
                                        View Details
                                    </a>

                                </div>


                                <!-- Actions -->

                                <?php if ($canManage): ?>

                                    <div class="d-flex gap-2">

                                        <a
                                            href="<?= url(
                                                '/account/health-records/' .
                                                (int) $profile['id'] .
                                                '/insurance/' .
                                                (int) $policy['id'] .
                                                '/edit'
                                            ) ?>"
                                            class="btn btn-sm btn-outline-secondary align-self-start h-auto"
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
                                                '/delete'
                                            ) ?>"
                                            class="insurance-delete-form"
                                            data-confirm-message="Are you sure you want to delete this insurance policy? This action cannot be undone."
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

                                <?php endif; ?>

                            </div>


                            <!-- Policy Details -->

                            <div class="row g-3 mt-2">

                                <?php if (
                                    !empty($policy['policy_number'])
                                ): ?>

                                    <div class="col-md-6 col-lg-4">

                                        <div class="text-muted small">
                                            Policy Number
                                        </div>

                                        <div>
                                            <?= e(
                                                $policy['policy_number']
                                            ) ?>
                                        </div>

                                    </div>

                                <?php endif; ?>


                                <div class="col-md-6 col-lg-4">

                                    <div class="text-muted small">
                                        Policy Type
                                    </div>

                                    <div>
                                        <?= e($policyTypeLabel) ?>
                                    </div>

                                </div>


                                <div class="col-md-6 col-lg-4">

                                    <div class="text-muted small">
                                        Sum Insured
                                    </div>

                                    <div>
                                        <?= e(
                                            $currencyCode .
                                            ' ' .
                                            $sumInsured
                                        ) ?>
                                    </div>

                                </div>


                                <div class="col-md-6 col-lg-4">

                                    <div class="text-muted small">
                                        Start Date
                                    </div>

                                    <div>
                                        <?= e($startDate) ?>
                                    </div>

                                </div>


                                <div class="col-md-6 col-lg-4">

                                    <div class="text-muted small">
                                        Expiry Date
                                    </div>

                                    <div>
                                        <?= e($expiryDate) ?>
                                    </div>

                                </div>

                            </div>


                            <!-- Renewal Notes -->

                            <?php if (
                                !empty($policy['renewal_notes'])
                            ): ?>

                                <div class="mt-3 pt-3 border-top">

                                    <div class="text-muted small mb-1">
                                        Renewal Notes
                                    </div>

                                    <div>
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

                                <div class="mt-3 pt-3 border-top">

                                    <div class="text-muted small mb-1">
                                        General Notes
                                    </div>

                                    <div>
                                        <?= nl2br(
                                            e($policy['general_notes'])
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- Back Button -->

    <div class="mt-4">

        <a
            href="<?= url(
                '/account/health-records/' .
                (int) $profile['id']
            ) ?>"
            class="btn btn-outline-secondary"
        >
            ← Back to Health Record
        </a>

    </div>

</div>


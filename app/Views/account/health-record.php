<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?= url('/account/health-records') ?>"
            class="btn btn-outline-secondary mb-3"
        >
            ← Back to Health Records
        </a>

        <h1 class="mb-1">
            <?= htmlspecialchars(
                $profile['full_name']
            ) ?>
        </h1>

        <p class="text-muted mb-0">
            <?= $profile['profile_type'] === 'self'
                ? 'Personal Health Profile'
                : 'Family Health Profile'
            ?>
        </p>

    </div>


    <!-- Profile Information -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h4 class="mb-0">
                Profile Information
            </h4>
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
                    (int) $profile['id'] .
                    '/edit'
                ) ?>"
                class="btn btn-primary"
            >
                Edit Profile
            </a>

        <?php endif; ?>
        </div>


        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-6">

                    <div class="text-muted small">
                        Full Name
                    </div>

                    <div class="fs-5">
                        <?= htmlspecialchars(
                            $profile['full_name']
                        ) ?>
                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Date of Birth
                    </div>

                    <div class="fs-5">

                        <?php if (!empty($profile['date_of_birth'])): ?>

                            <?= htmlspecialchars(
                                $profile['date_of_birth']
                            ) ?>

                        <?php else: ?>

                            <span class="text-muted">
                                Not added
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Gender
                    </div>

                    <div class="fs-5">

                        <?php
                        $gender = $profile['gender'] ?? 'unspecified';

                        $genderLabels = [
                            'male' => 'Male',
                            'female' => 'Female',
                            'other' => 'Other',
                            'unspecified' => 'Not specified'
                        ];
                        ?>

                        <?= htmlspecialchars(
                            $genderLabels[$gender]
                                ?? 'Not specified'
                        ) ?>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Your Access
                    </div>

                    <div class="fs-5">

                        <?= htmlspecialchars(
                            ucfirst(
                                $profile['role']
                                    ?? 'viewer'
                            )
                        ) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Coming Health Sections -->

    <!-- Health Information -->

    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <h4 class="mb-0">
                Health Information
            </h4>

        </div>


        <div class="card-body">

            <div class="row g-3">

                <!-- Medical Conditions -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-heart-pulse fs-2"></i>
                            </div>

                            <h5 class="card-title">
                                Medical Conditions
                            </h5>

                            <p class="card-text text-muted">
                                Record important current, chronic,
                                resolved and historical medical conditions.
                            </p>

                            <a
                                href="<?= url(
                                    '/account/health-records/' .
                                    (int) $profile['id'] .
                                    '/conditions'
                                ) ?>"
                                class="btn btn-outline-primary"
                            >
                                View Conditions
                            </a>

                        </div>

                    </div>

                </div>


                <!-- Medicines -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-capsule fs-2"></i>
                            </div>

                            <h5 class="card-title">
                                Medicines
                            </h5>

                            <p class="card-text text-muted">
                                Current and previous medicines,
                                including brand and generic names.
                            </p>

                            <a
                                href="<?= url(
                                    '/account/health-records/' .
                                    $profile['id'] .
                                    '/medicines'
                                ) ?>"
                                class="btn btn-outline-primary"
                            >
                                View Medicines
                            </a>

                        </div>

                    </div>

                </div>


                <!-- Allergies -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-exclamation-triangle fs-2"></i>
                            </div>

                            <h5 class="card-title">
                                Allergies
                            </h5>

                            <p class="card-text text-muted">
                                Record known allergies and related
                                reactions.
                            </p>

                            <a
                                href="<?= url(
                                    '/account/health-records/' .
                                    $profile['id'] .
                                    '/allergies'
                                ) ?>"
                                class="btn btn-outline-primary"
                            >
                                View Allergies
                            </a>

                        </div>

                    </div>

                </div>


                <!-- Surgeries & Procedures -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-hospital fs-2"></i>
                            </div>

                            <div>
                                <h2 class="h5 mb-1">
                                    Surgeries &amp; Procedures
                                </h2>

                                <p class="text-muted small mb-0">
                                    Past surgeries, procedures and hospitalizations
                                </p>
                            </div>


                                <p class="text-muted mb-3">
                                    Keep important medical procedures and hospitalization
                                    history in one place.
                                </p>


                                <a
                                    href="<?= url(
                                        '/account/health-records/' .
                                        $profile['id'] .
                                        '/procedures'
                                    ) ?>"
                                    class="btn btn-outline-primary"
                                >
                                    View Procedures
                                </a>

                        </div>

                    </div>

                </div>

                <!-- Medical Timeline -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-clock-history fs-2"></i>
                            </div>

                            <h5 class="card-title">
                                Medical Timeline
                            </h5>

                            <p class="card-text text-muted">
                                View important medical events and
                                history in chronological order.
                            </p>

                            <a
                                href="<?= url(
                                    '/account/health-records/' .
                                    $profile['id'] .
                                    '/timeline'
                                ) ?>"
                                class="btn btn-outline-primary"
                            >
                                View Timeline
                            </a>

                        </div>

                    </div>

                </div>

                <!-- Medical Summary -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-file-medical fs-2"></i>
                            </div>

                            <h5 class="card-title">
                                Medical Summary
                            </h5>

                            <p class="card-text text-muted">
                                View a concise medical snapshot
                                of this health profile.
                            </p>

                            <a
                                href="<?= url(
                                    '/account/health-records/' .
                                    $profile['id'] .
                                    '/medical-summary'
                                ) ?>"
                                class="btn btn-outline-primary"
                            >
                                View Summary
                            </a>

                        </div>

                    </div>

                </div>


                <!-- Documents -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-file-earmark-medical fs-2"></i>
                            </div>

                            <h5 class="card-title">
                                Medical Documents
                            </h5>

                            <p class="card-text text-muted">
                                Store medical reports, prescriptions,
                                scans and other documents.
                            </p>

                            <span class="btn btn-outline-secondary disabled">
                                Coming Soon
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Insurance -->

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card h-100 border shadow-sm">

                        <div class="card-body">

                            <div class="mb-3">
                                <i class="bi bi-shield-check fs-2"></i>
                            </div>

                            <h5 class="card-title">
                                Insurance
                            </h5>

                            <p class="card-text text-muted">
                                Keep insurance policy details and
                                important coverage information.
                            </p>

                            <span class="btn btn-outline-secondary disabled">
                                Coming Soon
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
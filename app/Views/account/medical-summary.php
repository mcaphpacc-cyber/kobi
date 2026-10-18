<?php

$summary =
    is_array($summary ?? null)
        ? $summary
        : [];

$profile =
    is_array($summary['profile'] ?? null)
        ? $summary['profile']
        : [];

$conditions =
    is_array($summary['conditions'] ?? null)
        ? $summary['conditions']
        : [];

$currentMedicines =
    is_array($summary['medicines']['current'] ?? null)
        ? $summary['medicines']['current']
        : [];

$allergies =
    is_array($summary['allergies'] ?? null)
        ? $summary['allergies']
        : [];

$procedures =
    is_array($summary['procedures'] ?? null)
        ? $summary['procedures']
        : [];

$profileId =
    (int) ($profile['id'] ?? 0);

$profileName =
    trim(
        (string) (
            $profile['full_name']
            ?? 'Health Profile'
        )
    );

$dob =
    trim(
        (string) (
            $profile['date_of_birth']
            ?? ''
        )
    );

$gender =
    trim(
        (string) (
            $profile['gender']
            ?? ''
        )
    );

$age = null;

if ($dob !== '') {

    try {

        $birthDate =
            new DateTime($dob);

        $today =
            new DateTime('today');

        $age =
            $birthDate->diff($today)->y;

    }
    catch (Throwable $e) {

        $age = null;
    }
}


$formatDate =
    static function (?string $date): string {

        if (
            $date === null ||
            trim($date) === ''
        ) {
            return '';
        }

        $timestamp =
            strtotime($date);

        if ($timestamp === false) {
            return '';
        }

        return date(
            'd M Y',
            $timestamp
        );
    };


$formatLabel =
    static function (?string $value): string {

        $value =
            trim((string) $value);

        if ($value === '') {
            return '';
        }

        return ucwords(
            str_replace(
                '_',
                ' ',
                $value
            )
        );
    };

?>

<div class="medical-summary-page">

    <a class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3"
       href="<?= e(url('/account/health-records/' . $profileId)) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Health Record
    </a>

    <header class="medical-summary-heading">
        <div>
            <p class="medical-summary-label mb-2">KOBI Health Record</p>
            <h1 class="h2 mb-2">Medical Summary</h1>
            <p class="text-muted mb-1">A concise summary of recorded health information</p>
            <p class="small text-muted mb-0">Prepared from KOBI Health Record</p>
        </div>
        <nav class="medical-summary-actions d-flex flex-wrap gap-2" aria-label="Medical Summary actions">
            <a class="btn btn-outline-secondary btn-sm"
               href="<?= e(url('/account/health-records/' . $profileId . '/timeline')) ?>">
                <i class="bi bi-clock-history" aria-hidden="true"></i> Medical Timeline
            </a>
            <?php if (($profile['role'] ?? null) === 'owner'): ?>
                <a class="btn btn-primary btn-sm"
                   href="<?= e(url('/account/health-records/' . $profileId . '/medical-summary/share')) ?>">
                    <i class="bi bi-share" aria-hidden="true"></i> Share Medical Summary
                </a>
                <a class="btn btn-outline-secondary btn-sm"
                   href="<?= e(url('/account/health-records/' . $profileId . '/medical-summary/shares')) ?>">
                    <i class="bi bi-link-45deg" aria-hidden="true"></i> Manage Shared Links
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="medical-summary-sheet">


        <!-- Patient Information -->
        <section class="medical-summary-section medical-summary-patient">

            <h2 class="medical-summary-section-title">
                Patient Information
            </h2>

            <div class="row g-3">

                <div class="col-12 col-md-3">

                    <div class="medical-summary-label">
                        Name
                    </div>

                    <div class="medical-summary-value">
                        <?= e($profileName) ?>
                    </div>

                </div>


                <?php if ($dob !== ''): ?>

                    <div class="col-6 col-md-3">

                        <div class="medical-summary-label">
                            Date of Birth
                        </div>

                        <div class="medical-summary-value">
                            <?= e(
                                $formatDate($dob)
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <?php if ($age !== null): ?>

                    <div class="col-6 col-md-3">

                        <div class="medical-summary-label">
                            Age
                        </div>

                        <div class="medical-summary-value">
                            <?= e((string) $age) ?> years
                        </div>

                    </div>

                <?php endif; ?>


                <?php if ($gender !== ''): ?>

                    <div class="col-6 col-md-3">

                        <div class="medical-summary-label">
                            Gender
                        </div>

                        <div class="medical-summary-value">
                            <?= e(
                                $formatLabel($gender)
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- Active Conditions -->
        <section class="medical-summary-section">

            <h2 class="medical-summary-section-title">
                Active Medical Conditions
            </h2>

            <?php if (
                !empty($conditions['active'])
            ): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               medical-summary-table
                               mb-0"
                    >

                        <thead>

                            <tr>
                                <th>Condition</th>
                                <th>Status</th>
                                <th>Diagnosed</th>
                                <th>Doctor / Hospital</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $conditions['active']
                                as $condition
                            ): ?>

                                <tr>

                                    <td class="fw-semibold">
                                        <?= e(
                                            $condition[
                                                'condition_name'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatLabel(
                                                $condition[
                                                    'status'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatDate(
                                                $condition[
                                                    'diagnosed_on'
                                                ]
                                                ?? null
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $condition[
                                                'doctor_hospital'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="medical-summary-empty">
                    No active medical conditions recorded.
                </p>

            <?php endif; ?>

        </section>


        <!-- Current Medicines -->
        <section class="medical-summary-section">

            <h2 class="medical-summary-section-title">
                Current Medicines
            </h2>

            <?php if (!empty($currentMedicines)): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               medical-summary-table
                               mb-0"
                    >

                        <thead>

                            <tr>
                                <th>Medicine</th>
                                <th>Salt / Generic</th>
                                <th>Strength</th>
                                <th>Dose</th>
                                <th>Frequency</th>
                                <th>Route</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $currentMedicines
                                as $medicine
                            ): ?>

                                <tr>

                                    <td class="fw-semibold">
                                        <?= e(
                                            $medicine[
                                                'brand_name'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $medicine[
                                                'generic_name'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $medicine[
                                                'strength'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $medicine[
                                                'dose'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $medicine[
                                                'frequency'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatLabel(
                                                $medicine[
                                                    'route'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="medical-summary-empty">
                    No current medicines recorded.
                </p>

            <?php endif; ?>

        </section>


        <!-- Allergies -->
        <section class="medical-summary-section">

            <h2 class="medical-summary-section-title">
                Allergies
            </h2>

            <?php if (!empty($allergies)): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               medical-summary-table
                               mb-0"
                    >

                        <thead>

                            <tr>
                                <th>Allergen</th>
                                <th>Category</th>
                                <th>Reaction</th>
                                <th>Severity</th>
                                <th>Identified</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $allergies
                                as $allergy
                            ): ?>

                                <tr>

                                    <td class="fw-semibold">
                                        <?= e(
                                            $allergy[
                                                'allergen'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatLabel(
                                                $allergy[
                                                    'category'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $allergy[
                                                'reaction'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatLabel(
                                                $allergy[
                                                    'severity'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatDate(
                                                $allergy[
                                                    'identified_on'
                                                ]
                                                ?? null
                                            )
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="medical-summary-empty">
                    No allergies recorded.
                </p>

            <?php endif; ?>

        </section>


        <!-- Surgeries & Procedures -->
        <section class="medical-summary-section">

            <h2 class="medical-summary-section-title">
                Surgeries &amp; Procedures
            </h2>

            <?php if (!empty($procedures)): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               medical-summary-table
                               mb-0"
                    >

                        <thead>

                            <tr>
                                <th>Procedure</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Hospital</th>
                                <th>Doctor</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $procedures
                                as $procedure
                            ): ?>

                                <tr>

                                    <td class="fw-semibold">
                                        <?= e(
                                            $procedure[
                                                'procedure_name'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatLabel(
                                                $procedure[
                                                    'procedure_type'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatDate(
                                                $procedure[
                                                    'performed_on'
                                                ]
                                                ?? null
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $procedure[
                                                'hospital'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $procedure[
                                                'doctor'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="medical-summary-empty">
                    No surgeries or procedures recorded.
                </p>

            <?php endif; ?>

        </section>


        <?php if (
            !empty($conditions['historical'])
        ): ?>

            <!-- Historical Conditions -->
            <section class="medical-summary-section">

                <h2 class="medical-summary-section-title">
                    Historical / Resolved Conditions
                </h2>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               medical-summary-table
                               mb-0"
                    >

                        <thead>

                            <tr>
                                <th>Condition</th>
                                <th>Status</th>
                                <th>Diagnosed</th>
                                <th>Resolved</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $conditions['historical']
                                as $condition
                            ): ?>

                                <tr>

                                    <td class="fw-semibold">
                                        <?= e(
                                            $condition[
                                                'condition_name'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatLabel(
                                                $condition[
                                                    'status'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatDate(
                                                $condition[
                                                    'diagnosed_on'
                                                ]
                                                ?? null
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $formatDate(
                                                $condition[
                                                    'resolved_on'
                                                ]
                                                ?? null
                                            )
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php endif; ?>


        <!-- Timeline -->
        <?php if (
            !empty(
                $summary['timeline_available']
            )
        ): ?>

            <section
                class="medical-summary-section
                       medical-summary-navigation"
            >

                <h2 class="medical-summary-section-title">
                    Medical Timeline
                </h2>

                <p class="mb-2">
                    A chronological record of dated
                    medical events is available.
                </p>

                <a
                    href="<?= url(
                        '/account/health-records/' .
                        $profileId .
                        '/timeline'
                    ) ?>"
                    class="text-decoration-none"
                >
                    View Complete Medical Timeline
                    <i class="bi bi-arrow-right"></i>
                </a>

            </section>

        <?php endif; ?>


        <section class="medical-summary-section medical-summary-navigation" aria-labelledby="summary-documents-heading">
            <h2 class="medical-summary-section-title" id="summary-documents-heading">Reports &amp; Documents</h2>
            <p class="text-muted mb-2">View medical reports and documents associated with this health record.</p>
            <a class="text-decoration-none" href="<?= e(url('/account/health-records/' . $profileId . '/documents')) ?>">
                View Reports &amp; Documents <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </section>

        <section class="medical-summary-section" aria-labelledby="summary-insurance-heading">
            <h2 class="medical-summary-section-title" id="summary-insurance-heading">Insurance</h2>
            <?php if (empty($insurancePolicies)): ?>
                <p class="medical-summary-empty">No insurance information has been recorded for this health profile.</p>
            <?php else: ?>
                <?php foreach ($insurancePolicies as $policy): ?>
                    <div class="medical-summary-insurance-policy">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h3 class="h6 mb-1 text-break"><?= e($policy['provider_name']) ?></h3>
                                <?php if (!empty($policy['policy_name'])): ?>
                                    <p class="small text-muted mb-0 text-break"><?= e($policy['policy_name']) ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="badge text-bg-light"><?= e($formatLabel($policy['status'])) ?></span>
                        </div>
                        <dl class="row g-3 mb-0">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <dt class="medical-summary-label">Policy Type</dt>
                                <dd class="medical-summary-value mb-0"><?= e($formatLabel($policy['policy_type'])) ?></dd>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <dt class="medical-summary-label">Sum Insured</dt>
                                <dd class="medical-summary-value mb-0"><?php if ($policy['sum_insured'] !== null && $policy['sum_insured'] !== ''): ?><?= e($policy['currency_code']) ?> <?= e(number_format((float) $policy['sum_insured'], 2)) ?><?php else: ?>Not specified<?php endif; ?></dd>
                            </div>
                            <div class="col-6 col-lg-3">
                                <dt class="medical-summary-label">Effective Date</dt>
                                <dd class="medical-summary-value mb-0"><?= e($formatDate($policy['start_date']) ?: 'Not specified') ?></dd>
                            </div>
                            <div class="col-6 col-lg-3">
                                <dt class="medical-summary-label">Expiry Date</dt>
                                <dd class="medical-summary-value mb-0"><?= e($formatDate($policy['expiry_date']) ?: 'Not specified') ?></dd>
                            </div>
                        </dl>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>


        <!-- Footer -->
        <footer
            class="medical-summary-footer
                   border-top
                   mt-4
                   pt-3"
        >

            <div
                class="d-flex
                       flex-column
                       flex-md-row
                       justify-content-between
                       gap-2
                       small
                       text-muted"
            >

                <div>
                    Generated from the KOBI Health Record.
                </div>

                <div>
                    This summary contains information
                    recorded by the health profile owner.
                </div>

            </div>

        </footer>

    </div>

</div>



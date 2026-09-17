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

<div class="container py-4">

    <!-- Actions -->
    <!-- Actions -->
<div
    class="d-flex
           justify-content-between
           align-items-center
           flex-wrap
           gap-2
           mb-3"
>

    <a
        href="<?= url(
            '/account/health-records/' .
            $profileId
        ) ?>"
        class="text-decoration-none"
    >
        <i class="bi bi-arrow-left"></i>
        Back to Health Record
    </a>


    <div
        class="d-flex
               align-items-center
               flex-wrap
               gap-2"
    >

            <a
                href="<?= url(
                    '/account/health-records/' .
                    $profileId .
                    '/timeline'
                ) ?>"
                class="btn btn-outline-secondary btn-sm"
            >
                <i class="bi bi-clock-history"></i>
                Medical Timeline
            </a>


            <?php if (
                ($profile['role'] ?? null)
                === 'owner'
            ): ?>

                <a
                    href="<?= url(
                        '/account/health-records/' .
                        $profileId .
                        '/medical-summary/share'
                    ) ?>"
                    class="btn btn-primary btn-sm"
                >
                    <i class="bi bi-share"></i>
                    Share Medical Summary
                </a>

                <a
                    href="<?= url(
                        '/account/health-records/' .
                        $profileId .
                        '/medical-summary/shares'
                    ) ?>"
                    class="btn btn-outline-secondary btn-sm"
                >
                    <i class="bi bi-link-45deg"></i>
                    Manage Shared Links
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- A4 Medical Report -->
    <div class="medical-summary-sheet">

        <!-- Header -->
        <header
            class="medical-summary-header
                   border-bottom
                   pb-3
                   mb-4"
        >

            <div
                class="d-flex
                       justify-content-between
                       align-items-start
                       gap-3"
            >

                <div>

                    <div
                        class="small
                               text-uppercase
                               text-muted
                               fw-semibold
                               mb-1"
                    >
                        KOBI
                    </div>

                    <h1 class="h3 mb-1">
                        Medical Summary
                    </h1>

                    <div class="text-muted">
                        A concise summary of recorded
                        health information
                    </div>

                </div>

                <div
                    class="text-end
                           small
                           text-muted"
                >
                    Prepared from KOBI Health Record
                </div>

            </div>

        </header>


        <!-- Patient Information -->
        <section class="medical-summary-section">

            <h2 class="medical-summary-section-title">
                Patient Information
            </h2>

            <div class="row g-3">

                <div class="col-12 col-md-6">

                    <div class="medical-summary-label">
                        Name
                    </div>

                    <div class="medical-summary-value">
                        <?= e($profileName) ?>
                    </div>

                </div>


                <?php if ($dob !== ''): ?>

                    <div class="col-6 col-md-2">

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

                    <div class="col-6 col-md-2">

                        <div class="medical-summary-label">
                            Age
                        </div>

                        <div class="medical-summary-value">
                            <?= e((string) $age) ?> years
                        </div>

                    </div>

                <?php endif; ?>


                <?php if ($gender !== ''): ?>

                    <div class="col-6 col-md-2">

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


        <!-- Future Sections -->
        <section
            class="medical-summary-section
                   medical-summary-future"
        >

            <div class="row g-3">

                <div class="col-12 col-md-6">

                    <div class="medical-summary-future-item">

                        <div class="medical-summary-label">
                            Reports &amp; Documents
                        </div>

                        <div class="text-muted small mb-2">
                            View medical reports and documents
                            associated with this health record.
                        </div>

                        <a
                            href="<?= url(
                                '/account/health-records/' .
                                $profileId .
                                '/documents'
                            ) ?>"
                            class="text-decoration-none"
                        >
                            View Reports &amp; Documents
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>


                <div class="col-12 col-md-6">

                    <div class="medical-summary-future-item">

                        <div
                            class="medical-summary-label"
                        >
                            Insurance
                        </div>

                        <div class="text-muted small">
                            Insurance information will be
                            available here when the
                            insurance module is implemented.
                        </div>

                    </div>

                </div>

            </div>

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


<style>
.medical-summary-sheet
{
    max-width: 900px;
    margin: 0 auto;
    padding: 2.5rem;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.06);
}

.medical-summary-section
{
    margin-bottom: 2rem;
}

.medical-summary-section-title
{
    margin-bottom: 1rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid var(--bs-border-color);
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.medical-summary-label
{
    margin-bottom: 0.2rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.medical-summary-value
{
    font-size: 0.95rem;
}

.medical-summary-table
{
    vertical-align: middle;
}

.medical-summary-table th
{
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}

.medical-summary-table td
{
    font-size: 0.875rem;
}

.medical-summary-empty
{
    margin-bottom: 0;
    color: var(--bs-secondary-color);
    font-size: 0.875rem;
}

.medical-summary-navigation
{
    padding-top: 0.25rem;
}

.medical-summary-future
{
    padding-top: 0.5rem;
}

.medical-summary-future-item
{
    padding: 0.9rem;
    border: 1px solid var(--bs-border-color);
}

.medical-summary-footer
{
    font-size: 0.75rem;
}

@media (max-width: 767.98px)
{
    .medical-summary-sheet
    {
        padding: 1.25rem;
    }

    .medical-summary-header > div
    {
        flex-direction: column;
    }
}

@media print
{
    body
    {
        background: #fff !important;
    }

    .container
    {
        max-width: none !important;
        width: 100% !important;
        padding: 0 !important;
    }

    .medical-summary-sheet
    {
        max-width: none;
        margin: 0;
        padding: 0;
        border: 0;
        box-shadow: none;
    }

    .medical-summary-section
    {
        break-inside: avoid;
    }

    .medical-summary-table
    {
        font-size: 10pt;
    }

    .medical-summary-table th,
    .medical-summary-table td
    {
        border-color: #999 !important;
    }

    .medical-summary-navigation
    {
        display: none;
    }

    .medical-summary-future
    {
        display: none;
    }
}
</style>
<?php

$summary =
    is_array($summary ?? null)
        ? $summary
        : [];

$share =
    is_array($share ?? null)
        ? $share
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

$age =
    isset($profile['age'])
        ? (int) $profile['age']
        : null;


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


$formatDateTime =
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
            'd M Y, h:i A',
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


$snapshotCreatedAt =
    trim(
        (string) (
            $share['snapshot_created_at']
            ?? ''
        )
    );

$expiresAt =
    trim(
        (string) (
            $share['expires_at']
            ?? ''
        )
    );

?>

<div class="container py-4">

    <div class="public-medical-summary-wrapper">

        <!-- Header -->
        <header
            class="public-medical-summary-header
                   border-bottom
                   pb-4
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
                        Shared health information
                    </div>

                </div>

                <div
                    class="text-end
                           small
                           text-muted"
                >
                    Read-only
                </div>

            </div>

        </header>


        <!-- Snapshot Notice -->
        <div
            class="alert
                   alert-info
                   small
                   mb-4"
        >

            <div
                class="fw-semibold
                       mb-1"
            >
                Shared Medical Summary
            </div>

            This is a read-only snapshot of the medical
            information recorded in KOBI when this share
            was created.

            It does not represent live access to the
            person's health record.

        </div>


        <!-- Patient Information -->
        <section class="public-medical-summary-section">

            <h2 class="public-medical-summary-section-title">
                Patient Information
            </h2>

            <div class="row g-3">

                <div class="col-12 col-md-6">

                    <div class="public-medical-summary-label">
                        Name
                    </div>

                    <div class="public-medical-summary-value">
                        <?= e($profileName) ?>
                    </div>

                </div>


                <?php if ($dob !== ''): ?>

                    <div class="col-6 col-md-2">

                        <div class="public-medical-summary-label">
                            Date of Birth
                        </div>

                        <div class="public-medical-summary-value">
                            <?= e(
                                $formatDate($dob)
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <?php if ($age !== null): ?>

                    <div class="col-6 col-md-2">

                        <div class="public-medical-summary-label">
                            Age
                        </div>

                        <div class="public-medical-summary-value">
                            <?= e((string) $age) ?> years
                        </div>

                    </div>

                <?php endif; ?>


                <?php if ($gender !== ''): ?>

                    <div class="col-6 col-md-2">

                        <div class="public-medical-summary-label">
                            Gender
                        </div>

                        <div class="public-medical-summary-value">
                            <?= e(
                                $formatLabel($gender)
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- Active Conditions -->
        <section class="public-medical-summary-section">

            <h2 class="public-medical-summary-section-title">
                Active Medical Conditions
            </h2>

            <?php if (
                !empty($conditions['active'])
            ): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               public-medical-summary-table
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

                <p class="public-medical-summary-empty">
                    No active medical conditions recorded.
                </p>

            <?php endif; ?>

        </section>


        <!-- Current Medicines -->
        <section class="public-medical-summary-section">

            <h2 class="public-medical-summary-section-title">
                Current Medicines
            </h2>

            <?php if (!empty($currentMedicines)): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               public-medical-summary-table
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

                <p class="public-medical-summary-empty">
                    No current medicines recorded.
                </p>

            <?php endif; ?>

        </section>


        <!-- Allergies -->
        <section class="public-medical-summary-section">

            <h2 class="public-medical-summary-section-title">
                Allergies
            </h2>

            <?php if (!empty($allergies)): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               public-medical-summary-table
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

                <p class="public-medical-summary-empty">
                    No allergies recorded.
                </p>

            <?php endif; ?>

        </section>


        <!-- Surgeries & Procedures -->
        <section class="public-medical-summary-section">

            <h2 class="public-medical-summary-section-title">
                Surgeries &amp; Procedures
            </h2>

            <?php if (!empty($procedures)): ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               public-medical-summary-table
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

                <p class="public-medical-summary-empty">
                    No surgeries or procedures recorded.
                </p>

            <?php endif; ?>

        </section>


        <!-- Historical Conditions -->
        <?php if (
            !empty($conditions['historical'])
        ): ?>

            <section class="public-medical-summary-section">

                <h2 class="public-medical-summary-section-title">
                    Historical / Resolved Conditions
                </h2>

                <div class="table-responsive">

                    <table
                        class="table
                               table-sm
                               public-medical-summary-table
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


        <!-- Share Information -->
        <section
            class="public-medical-summary-section
                   public-medical-summary-meta"
        >

            <div class="row g-3">

                <?php if (
                    $snapshotCreatedAt !== ''
                ): ?>

                    <div class="col-12 col-md-6">

                        <div
                            class="public-medical-summary-label"
                        >
                            Snapshot Created
                        </div>

                        <div class="small">
                            <?= e(
                                $formatDateTime(
                                    $snapshotCreatedAt
                                )
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <?php if (
                    $expiresAt !== ''
                ): ?>

                    <div class="col-12 col-md-6">

                        <div
                            class="public-medical-summary-label"
                        >
                            Share Expires
                        </div>

                        <div class="small">
                            <?= e(
                                $formatDateTime(
                                    $expiresAt
                                )
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- Disclaimer -->
        <section
            class="public-medical-summary-disclaimer
                   border-top
                   pt-3
                   mt-4"
        >

            <p class="small text-muted mb-2">
                This information is provided from a
                KOBI health record and is intended to help
                communicate recorded medical information.
            </p>

            <p class="small text-muted mb-0">
                It is not a diagnosis, medical advice,
                treatment recommendation, or substitute
                for consultation with a qualified healthcare
                professional.
            </p>

        </section>


        <!-- Footer -->
        <footer
            class="public-medical-summary-footer
                   mt-4
                   pt-3"
        >

            <div
                class="small
                       text-muted
                       text-center"
            >
                KOBI Medical Summary · Read-only shared view
            </div>

        </footer>

    </div>

</div>


<style>
.public-medical-summary-wrapper
{
    max-width: 900px;
    margin: 0 auto;
    padding: 2.5rem;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.06);
}

.public-medical-summary-section
{
    margin-bottom: 2rem;
}

.public-medical-summary-section-title
{
    margin-bottom: 1rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid var(--bs-border-color);
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.public-medical-summary-label
{
    margin-bottom: 0.2rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.public-medical-summary-value
{
    font-size: 0.95rem;
}

.public-medical-summary-table
{
    vertical-align: middle;
}

.public-medical-summary-table th
{
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}

.public-medical-summary-table td
{
    font-size: 0.875rem;
}

.public-medical-summary-empty
{
    margin-bottom: 0;
    color: var(--bs-secondary-color);
    font-size: 0.875rem;
}

.public-medical-summary-meta
{
    padding-top: 0.5rem;
}

.public-medical-summary-disclaimer
{
    max-width: 800px;
}

.public-medical-summary-footer
{
    border-top: 1px solid var(--bs-border-color);
}

@media (max-width: 767.98px)
{
    .public-medical-summary-wrapper
    {
        padding: 1.25rem;
    }

    .public-medical-summary-header > div
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

    .public-medical-summary-wrapper
    {
        max-width: none;
        margin: 0;
        padding: 0;
        border: 0;
        box-shadow: none;
    }

    .public-medical-summary-section
    {
        break-inside: avoid;
    }

    .public-medical-summary-table
    {
        font-size: 10pt;
    }

    .public-medical-summary-table th,
    .public-medical-summary-table td
    {
        border-color: #999 !important;
    }
}
</style>
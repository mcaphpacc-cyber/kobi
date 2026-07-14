<?php

declare(strict_types=1);

$left = $comparison['left'];
$right = $comparison['right'];

$leftDisease = $left['disease'];
$rightDisease = $right['disease'];

function renderSeverityBadge(?string $severity): string
{
    $severity = strtolower((string) $severity);

    return match ($severity) {

        'critical' =>
            '<span class="badge bg-danger">Critical</span>',

        'severe' =>
            '<span class="badge bg-danger">Severe</span>',

        'moderate' =>
            '<span class="badge bg-warning text-dark">Moderate</span>',

        'mild' =>
            '<span class="badge bg-success">Mild</span>',

        default =>
            '<span class="badge bg-secondary">Unknown</span>'

    };
}

function renderSummaryCard(array $disease, array $knowledge): void
{
?>

<div class="card comparison-summary-card h-100 shadow-sm">

    <div class="card-body">

        <h3 class="mb-2 fw-bold">

            <?= e($disease['disease_en']); ?>

        </h3>
        <?php if (!empty($disease['body_part_name'])) : ?>

            <div class="text-muted small mb-3">

                <i class="bi bi-person me-1"></i>

                <?= e($disease['body_part_name']); ?>

            </div>

        <?php endif; ?>

        <div class="mb-3">

            <?= renderSeverityBadge(
                $disease['severity_level'] ?? null
            ); ?>

        </div>

        <?php if (!empty($disease['icd10_code'])) : ?>

            <div class="small text-muted mb-2">

                <i class="bi bi-upc-scan me-2"></i>

                ICD-10

                <strong>

                    <?= e($disease['icd10_code']); ?>

                </strong>

            </div>

        <?php endif; ?>

        <div class="row g-2 mt-3">

            <div class="col-6">

                <div class="comparison-stat">

                    <div class="comparison-stat-value">

                        <?= count($knowledge['symptoms']); ?>

                    </div>

                    <div class="comparison-stat-label">

                        Symptoms

                    </div>

                </div>

            </div>

            <div class="col-6">

                <div class="comparison-stat">

                    <div class="comparison-stat-value">

                        <?= count($knowledge['causes']); ?>

                    </div>

                    <div class="comparison-stat-label">

                        Causes

                    </div>

                </div>

            </div>

            <div class="col-6">

                <div class="comparison-stat">

                    <div class="comparison-stat-value">

                        <?= count($knowledge['treatments']); ?>

                    </div>

                    <div class="comparison-stat-label">

                        Treatments

                    </div>

                </div>

            </div>

            <div class="col-6">

                <div class="comparison-stat">

                    <div class="comparison-stat-value">

                        <?= count($knowledge['faqs']); ?>

                    </div>

                    <div class="comparison-stat-label">

                        FAQs

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php
}
?>

<?php

function renderComparisonSection(
    string $id,
    string $title,
    string $icon,
    string $leftTitle,
    string $rightTitle,
    string $leftContent,
    string $rightContent
): void {

?>

<section
    id="<?= e($id); ?>"
    class="comparison-section mb-4">

    <div class="card shadow-sm">

        <div class="card-header">

            <h4 class="mb-0">

                <i class="bi <?= e($icon); ?> me-2"></i>

                <?= e($title); ?>

            </h4>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-lg-6 border-end">

                    <h5 class="mb-3">

                        <?= e($leftTitle); ?>

                    </h5>

                    <div class="comparison-content">

                        <?= $leftContent; ?>

                    </div>

                </div>

                <div class="col-lg-6">

                    <h5 class="mb-3">

                        <?= e($rightTitle); ?>

                    </h5>

                    <div class="comparison-content">

                        <?= $rightContent; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<?php

}

?>

<?php

function renderComparisonList(
    string $title,
    string $icon,
    array $comparison,
    string $field
): void {

?>

<section class="comparison-section mb-4">

    <div class="card shadow-sm">

        <div class="card-header">

            <h4 class="mb-0">

                <i class="bi <?= e($icon); ?> me-2"></i>

                <?= e($title); ?>

            </h4>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-lg-4">

                    <h6 class="fw-bold text-success mb-3">

                        <i class="bi bi-check-circle-fill me-2"></i>

                        Shared

                    </h6>

                    <?php if (empty($comparison['shared'])) : ?>

                        <div class="text-muted">

                            None

                        </div>

                    <?php else : ?>

                        <?php foreach ($comparison['shared'] as $item) : ?>

                            <span class="badge bg-success me-1 mb-2">

                                <?= e($item[$field]); ?>

                            </span>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <div class="col-lg-4">

                    <h6 class="fw-bold text-primary mb-3">

                        <i class="bi bi-arrow-left-circle me-2"></i>

                        Only in Left Disease

                    </h6>

                    <?php if (empty($comparison['left_only'])) : ?>

                        <div class="text-muted">

                            None

                        </div>

                    <?php else : ?>

                        <?php foreach ($comparison['left_only'] as $item) : ?>

                            <span class="badge bg-primary me-1 mb-2">

                                <?= e($item[$field]); ?>

                            </span>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <div class="col-lg-4">

                    <h6 class="fw-bold text-warning mb-3">

                        <i class="bi bi-arrow-right-circle me-2"></i>

                        Only in Right Disease

                    </h6>

                    <?php if (empty($comparison['right_only'])) : ?>

                        <div class="text-muted">

                            None

                        </div>

                    <?php else : ?>

                        <?php foreach ($comparison['right_only'] as $item) : ?>

                            <span class="badge bg-warning text-dark me-1 mb-2">

                                <?= e($item[$field]); ?>

                            </span>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</section>

<?php

} ?>

<div class="comparison-page">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <a
            href="<?= url('/diseases'); ?>"
            class="btn btn-outline-secondary">

            <i class="bi bi-arrow-left-circle me-2"></i>

            Back

        </a>

    </div>

    <div class="comparison-header text-center mb-5">

        <h1 class="display-6 fw-bold">

            <i class="bi bi-columns-gap me-2 text-primary"></i>

            Disease Comparison

        </h1>

        <p class="text-muted mt-3 mb-0">

            Compare similarities and differences between two diseases.

        </p>

    </div>

    <div class="row align-items-center g-4 mb-5">

        <div class="col-lg-5">

            <?php renderSummaryCard(
                $leftDisease,
                $left
            ); ?>

        </div>

        <div class="col-lg-2 text-center">

            <div class="comparison-vs">

                VS

            </div>

        </div>

        <div class="col-lg-5">

            <?php renderSummaryCard(
                $rightDisease,
                $right
            ); ?>

        </div>

    </div>

    <?php

        /*
        |--------------------------------------------------------------------------
        | Overview
        |--------------------------------------------------------------------------
        */

        renderComparisonSection(
            'overview',
            'Overview',
            'bi-file-earmark-medical',
            $leftDisease['disease_en'],
            $rightDisease['disease_en'],
            nl2br(
                e(
                    $left['content']['overview_en']
                    ?: 'Overview not available.'
                )
            ),
            nl2br(
                e(
                    $right['content']['overview_en']
                    ?: 'Overview not available.'
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Symptoms
        |--------------------------------------------------------------------------
        */

        renderComparisonList(
            'Symptoms',
            'bi-activity',
            $comparison['symptoms'],
            'symptom_en'
        );

        /*
        |--------------------------------------------------------------------------
        | Causes
        |--------------------------------------------------------------------------
        */

        renderComparisonList(
            'Causes',
            'bi-bug',
            $comparison['causes'],
            'cause_en'
        );

        /*
        |--------------------------------------------------------------------------
        | Diagnosis
        |--------------------------------------------------------------------------
        */

        renderComparisonSection(
            'diagnosis',
            'Diagnosis',
            'bi-heart-pulse',
            $leftDisease['disease_en'],
            $rightDisease['disease_en'],
            nl2br(
                e(
                    $left['content']['diagnosis_en']
                    ?: 'Diagnosis information not available.'
                )
            ),
            nl2br(
                e(
                    $right['content']['diagnosis_en']
                    ?: 'Diagnosis information not available.'
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Prevention
        |--------------------------------------------------------------------------
        */

        renderComparisonSection(
            'prevention',
            'Prevention',
            'bi-shield-check',
            $leftDisease['disease_en'],
            $rightDisease['disease_en'],
            nl2br(
                e(
                    $left['content']['prevention_en']
                    ?: 'Prevention information not available.'
                )
            ),
            nl2br(
                e(
                    $right['content']['prevention_en']
                    ?: 'Prevention information not available.'
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Factors
        |--------------------------------------------------------------------------
        */

        renderComparisonSection(
            'risk-factors',
            'Risk Factors',
            'bi-exclamation-circle',
            $leftDisease['disease_en'],
            $rightDisease['disease_en'],
            nl2br(
                e(
                    $left['content']['risk_factors_en']
                    ?: 'Risk factor information not available.'
                )
            ),
            nl2br(
                e(
                    $right['content']['risk_factors_en']
                    ?: 'Risk factor information not available.'
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Treatment
        |--------------------------------------------------------------------------
        */

        renderComparisonSection(
            'treatment',
            'Treatment',
            'bi-capsule-pill',
            $leftDisease['disease_en'],
            $rightDisease['disease_en'],
            '<div class="text-muted">Treatment comparison will be available soon.</div>',
            '<div class="text-muted">Treatment comparison will be available soon.</div>'
        );

        ?>

</div>
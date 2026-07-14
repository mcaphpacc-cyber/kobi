<?php

$disease = $knowledge['disease'];
$content = $knowledge['content'] ?? [];

?>

<div class="container py-4">

    <a href="javascript:void(0)" onclick="window.location =
    `${window.KOBI.baseUrl}/symptom-checker`;"
       class="btn btn-outline-secondary btn-sm mb-4">

        <i class="bi bi-arrow-left-circle me-2"></i>

        Back to Results

    </a>

    <div class="card disease-hero shadow-sm border-0 mb-4">

        <div class="card-body">

            <h1 class="mb-2">

                <?= e($disease['disease_en']); ?>

            </h1>

            <div class="d-flex flex-wrap gap-2 mt-2">

                <span class="badge bg-warning text-dark">

                    <i class="bi bi-exclamation-triangle-fill me-1"></i>

                    <?= ucfirst($disease['severity_level'] ?? 'Unknown'); ?>

                </span>

                <?php if (!empty($disease['icd10_code'])) : ?>

                    <span class="badge bg-secondary">

                        <i class="bi bi-upc-scan me-1"></i>

                        ICD-10 <?= e($disease['icd10_code']); ?>

                    </span>

                <?php endif; ?>

                <?php if (!empty($disease['body_part_name'])) : ?>

                    <span class="badge bg-info text-dark">

                        <i class="bi bi-person-bounding-box me-1"></i>

                        <?= e($disease['body_part_name']); ?>

                    </span>

                <?php endif; ?>

            </div>
            <?php if (!empty($content['overview_en'])) : ?>

                <p class="mt-3 mb-0 text-muted">

                    <?= e(
                        mb_strlen($content['overview_en']) > 180
                            ? mb_substr($content['overview_en'], 0, 180) . '...'
                            : $content['overview_en']
                    ); ?>

                </p>

            <?php endif; ?>

        </div>

    </div>
    <div class="row g-3 mb-4">

        <div class="col-6 col-lg-3">

            <div class="card text-center h-100">

                <div class="card-body">

                    <i class="bi bi-activity fs-3 text-primary"></i>

                    <div class="small text-muted mt-2">

                        Symptoms

                    </div>

                    <div class="fs-3 fw-bold">

                        <?= count($knowledge['symptoms']); ?>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-6 col-lg-3">

            <div class="card text-center h-100">

                <div class="card-body">

                    <i class="bi bi-bug-fill fs-3 text-danger"></i>

                    <div class="small text-muted mt-2">

                        Causes

                    </div>

                    <div class="fs-3 fw-bold">

                        <?= count($knowledge['causes']); ?>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-6 col-lg-3">

            <div class="card text-center h-100">

                <div class="card-body">

                    <i class="bi bi-capsule-pill fs-3 text-success"></i>

                    <div class="small text-muted mt-2">

                        Treatments

                    </div>

                    <div class="fs-3 fw-bold">

                        <?= count($knowledge['treatments']); ?>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-6 col-lg-3">

            <div class="card text-center h-100">

                <div class="card-body">

                    <i class="bi bi-question-circle fs-3 text-warning"></i>

                    <div class="small text-muted mt-2">

                        FAQs

                    </div>

                    <div class="fs-3 fw-bold">

                        <?= count($knowledge['faqs']); ?>

                    </div>

                </div>

            </div>

        </div>

    </div>
    <div class="card mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap gap-2">

            <a href="#overview" class="btn btn-outline-primary btn-sm">
                Overview
            </a>

            <a href="#symptoms" class="btn btn-outline-primary btn-sm">
                Symptoms
            </a>

            <a href="#causes" class="btn btn-outline-primary btn-sm">
                Causes
            </a>

            <a href="#diagnosis" class="btn btn-outline-primary btn-sm">
                Diagnosis
            </a>

            <a href="#treatment" class="btn btn-outline-primary btn-sm">
                Treatment
            </a>

            <a href="#prevention" class="btn btn-outline-primary btn-sm">
                Prevention
            </a>

            <a href="#faq" class="btn btn-outline-primary btn-sm">
                FAQ
            </a>

        </div>

    </div>

</div>

<?php

function renderKnowledgeSection(
    string $id,
    string $title,
    string $icon,
    string $content
): void
{
?>

<section id="<?= $id; ?>" class="mb-4">

    <div class="card shadow-sm">

        <div class="card-header">

            <h5 class="mb-0">

                <i class="bi <?= $icon; ?> me-2"></i>

                <?= e($title); ?>

            </h5>

        </div>

        <div class="card-body">

            <?= $content; ?>

        </div>

    </div>

</section>

<?php
}
?>

<?php

renderKnowledgeSection(

    'overview',

    'Overview',

    'bi-file-earmark-medical',

    nl2br(

        e(

            $content['overview_en']

            ?: 'Overview will be available soon.'

        )

    )

);

ob_start();

if (!empty($knowledge['symptoms'])) {

    echo '<div class="d-flex flex-wrap gap-2">';

    foreach ($knowledge['symptoms'] as $symptom) {

        ?>

        <span class="badge rounded-pill bg-primary fs-6 px-3 py-2">

            <i class="bi bi-check-circle-fill me-1"></i>

            <?= e($symptom['symptom_en']); ?>

        </span>

        <?php
    }

    echo '</div>';

} else {

    ?>

    <div class="text-muted">

        No symptom information available.

    </div>

    <?php
}

$symptomsHtml = ob_get_clean();

renderKnowledgeSection(

    'symptoms',

    'Symptoms',

    'bi-activity',

    $symptomsHtml

);

ob_start();

?>

<div class="row">

    <div class="col-lg-5">

        <h6 class="fw-semibold mb-3">

            Primary Causes

        </h6>

        <?php if (!empty($knowledge['causes'])) : ?>

            <div class="d-flex flex-column gap-2">

                <?php foreach ($knowledge['causes'] as $cause) : ?>

                    <div class="border rounded p-2">

                        <i class="bi bi-bug-fill text-danger me-2"></i>

                        <?= e($cause['cause_en']); ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else : ?>

            <div class="text-muted">

                Cause information not available.

            </div>

        <?php endif; ?>

    </div>

    <div class="col-lg-7">

        <h6 class="fw-semibold mb-3">

            Risk Factors

        </h6>

        <?php if (!empty($content['risk_factors_en'])) : ?>

            <p class="mb-0">

                <?= nl2br(e($content['risk_factors_en'])); ?>

            </p>

        <?php else : ?>

            <div class="text-muted">

                Risk factor information not available.

            </div>

        <?php endif; ?>

    </div>

</div>

<?php

$causesHtml = ob_get_clean();

renderKnowledgeSection(

    'causes',

    'Causes & Risk Factors',

    'bi-bug',

    $causesHtml

); ?>

<?php

ob_start();

?>

<div class="alert alert-info mb-3">

    <i class="bi bi-info-circle-fill me-2"></i>

    Diagnosis is based on a patient's medical history,
    symptoms, physical examination and, when necessary,
    laboratory or imaging investigations.

</div>

<?php if (!empty($content['diagnosis_en'])) : ?>

    <div class="knowledge-content">

        <?= nl2br(e($content['diagnosis_en'])); ?>

    </div>

<?php else : ?>

    <div class="text-muted">

        Diagnosis information is not available yet.

    </div>

<?php endif; ?>

<?php

$diagnosisHtml = ob_get_clean();

renderKnowledgeSection(

    'diagnosis',

    'Diagnosis',

    'bi-heart-pulse',

    $diagnosisHtml

);

?>

<?php

ob_start();

?>

<div class="alert alert-success mb-3">

    <i class="bi bi-shield-check me-2"></i>

    Prevention focuses on reducing risk factors,
    maintaining good health practices and seeking
    timely medical care when symptoms appear.

</div>

<?php if (!empty($content['prevention_en'])) : ?>

    <div class="knowledge-content">

        <?= nl2br(e($content['prevention_en'])); ?>

    </div>

<?php else : ?>

    <div class="text-muted">

        Prevention information is not available yet.

    </div>

<?php endif; ?>

<?php

$preventionHtml = ob_get_clean();

renderKnowledgeSection(

    'prevention',

    'Prevention',

    'bi-shield-check',

    $preventionHtml

);

?>

<?php

renderKnowledgeSection(
    'treatment',
    'Treatment',
    'bi-capsule-pill',
    '<p>Treatment information will be loaded here.</p>'
);

renderKnowledgeSection(
    'faq',
    'Frequently Asked Questions',
    'bi-question-circle',
    '<p>FAQ section will be available soon.</p>'
);

?>
<?php if (!empty($relatedDiseases)) : ?>

<section id="related-diseases" class="mt-5">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h3 class="mb-1">

                <i class="bi bi-diagram-3 me-2 text-primary"></i>

                Related Diseases

            </h3>

            <div class="text-muted">

                Diseases with similar symptoms or affecting the same body system.

            </div>

        </div>

    </div>

    <div class="row g-4">

        <?php foreach ($relatedDiseases as $related) : ?>

            <div class="col-md-6 col-xl-4">

                <div class="card related-disease-card h-100 shadow-sm">

                    <div class="card-body d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-3">

                            <h5 class="card-title mb-0">

                                <?= e($related['disease_en']); ?>

                            </h5>

                            <?php

                                $score = (int) $related['similarity_score'];

                                if ($score >= 75) {

                                    $badgeClass = 'bg-success';

                                    $badgeText = 'High Match';

                                } elseif ($score >= 40) {

                                    $badgeClass = 'bg-warning text-dark';

                                    $badgeText = 'Medium Match';

                                } else {

                                    $badgeClass = 'bg-primary';

                                    $badgeText = 'Low Match';

                                }

                                ?>

                                <div class="text-end">

                                    <span class="badge <?= $badgeClass; ?>">

                                        <?= e($badgeText); ?>

                                    </span>

                                    <div class="small fw-semibold mt-1">

                                        <?= $score; ?>%

                                    </div>

                                </div>

                        </div>

                        <div class="small text-muted mb-3">

                            <i class="bi bi-link-45deg me-1"></i>

                            <?= (int) $related['shared_symptom_count']; ?>

                            Shared Symptom<?= $related['shared_symptom_count'] == 1 ? '' : 's'; ?>

                        </div>

                        <?php if (!empty($related['shared_symptoms'])) : ?>

                            <div class="mb-3">

                                <?php foreach ($related['shared_symptoms'] as $symptom) : ?>

                                    <span class="badge bg-primary-subtle text-primary border me-1 mb-1">

                                        <?= e($symptom); ?>

                                    </span>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                        <div class="mt-auto">

                            <div class="d-grid gap-2">

                                <a
                                    href="<?= url('/diseases/' . $related['slug']); ?>"
                                    class="btn btn-outline-primary btn-sm">

                                    <i class="bi bi-arrow-right-circle me-2"></i>

                                    View Disease

                                </a>

                                <a
                                    href="<?= url(
                                        '/compare/result?d1='
                                        . urlencode($disease['slug'])
                                        . '&d2='
                                        . urlencode($related['slug'])
                                    ); ?>"
                                    class="btn btn-primary btn-sm">

                                    <i class="bi bi-columns-gap me-2"></i>

                                    Compare

                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</section>

<?php endif; ?>


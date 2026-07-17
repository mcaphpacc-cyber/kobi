<?php

$disease = $knowledge['disease'];
$content = $knowledge['content'] ?? [];

$quickFacts = [];

$quickFacts[] = [

    'icon' => 'bi-exclamation-triangle',

    'title' => 'Clinical Severity',

    'value' => $knowledge['disease']['severity_level'] ?? 'Unknown',

    'badge' => true

];

if (!empty($knowledge['disease']['body_system'])) {

    $quickFacts[] = [

        'icon' => 'bi-diagram-3',

        'title' => 'Body System',

        'value' => $knowledge['disease']['body_system']

    ];

}

if (!empty($knowledge['disease']['icd10'])) {

    $quickFacts[] = [

        'icon' => 'bi-upc-scan',

        'title' => 'ICD-10',

        'value' => $knowledge['disease']['icd10']

    ];

}

if (!empty($knowledge['disease']['category_name'])) {

    $quickFacts[] = [

        'icon' => 'bi-folder',

        'title' => 'Category',

        'value' => $knowledge['disease']['category_name']

    ];

}


$treatmentIcons = [

    1 => 'bi-capsule-pill',

    2 => 'bi-flower1',

    3 => 'bi-heart-pulse',

    4 => 'bi-hand-index-thumb',

    5 => 'bi-person-arms-up'

];

$stats = [

    'diagnosis' => '',

    'prevention' => '',

    'symptoms' => count($knowledge['symptoms'] ?? []),

    'causes' => count($knowledge['causes'] ?? []),

    'treatments' => count($knowledge['treatments'] ?? []),

    'diet' =>

        count($knowledge['diet']['recommended'] ?? []) //+ count($knowledge['diet']['avoid'] ?? [])

];

?>

<div class="container py-4"> <a href="javascript:void(0)" onclick="window.location =
    `${window.KOBI.baseUrl}/symptom-checker`;"
       class="btn btn-outline-secondary btn-sm mb-4"> <i class="bi bi-arrow-left-circle me-2"></i> Back to Results </a>
  <div class="card disease-hero shadow-sm border-0 mb-4">
    <div class="card-body">
      <h1 class="mb-2">
        <?= e($disease['disease_en']); ?>
      </h1>
      <div class="d-flex flex-wrap gap-2 mt-2"> <span class="badge bg-warning text-dark"> <i class="bi bi-exclamation-triangle-fill me-1"></i>
        <?= ucfirst($disease['severity_level'] ?? 'Unknown'); ?>
        </span>
        <?php if (!empty($disease['icd10_code'])) : ?>
        <span class="badge bg-secondary"> <i class="bi bi-upc-scan me-1"></i> ICD-10
        <?= e($disease['icd10_code']); ?>
        </span>
        <?php endif; ?>
        <?php if (!empty($disease['body_part_name'])) : ?>
        <span class="badge bg-info text-dark"> <i class="bi bi-person-bounding-box me-1"></i>
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
    <hr class="my-1">
    <div class="row g-3">
        <?php

          $severityColors = [

              'low' => 'success',

              'mild' => 'info',

              'moderate' => 'warning',

              'high' => 'danger',

              'critical' => 'dark'

          ];

          $class =

          $severityColors[
              $knowledge['disease']['severity_level']
          ] ?? 'secondary';

        ?>
      <?php foreach ($quickFacts as $fact) : ?>

      <div class="col">

          <div class="card h-100">

              <div class="card-body">

                  <div class="small text-muted mb-2">

                      <i class="bi <?= $fact['icon']; ?> me-1"></i>

                      <?= e($fact['title']); ?>

                  </div>

                  <?php if (!empty($fact['badge'])) : ?>

                      <span class="badge bg-<?= $class; ?> text-dark">

                          <?= e($fact['value']); ?>

                      </span>

                  <?php else : ?>

                      <div class="fw-semibold">

                          <?= e($fact['value']); ?>

                      </div>

                  <?php endif; ?>

              </div>

          </div>

      </div>

      <?php endforeach; ?>

      </div>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card text-center h-100">
        <div class="card-body"> <i class="bi bi-activity fs-3 text-primary"></i>
          <div class="small text-muted mt-2"> Symptoms </div>
          <div class="fs-3 fw-bold">
            <?= count($knowledge['symptoms']); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card text-center h-100">
        <div class="card-body"> <i class="bi bi-bug-fill fs-3 text-danger"></i>
          <div class="small text-muted mt-2"> Causes </div>
          <div class="fs-3 fw-bold">
            <?= count($knowledge['causes']); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card text-center h-100">
        <div class="card-body"> <i class="bi bi-capsule-pill fs-3 text-success"></i>
          <div class="small text-muted mt-2"> Treatments </div>
          <div class="fs-3 fw-bold">
            <?= count($knowledge['treatments']); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card text-center h-100">
        <div class="card-body"> <i class="bi bi-question-circle fs-3 text-warning"></i>
          <div class="small text-muted mt-2"> Diet </div>
          <div class="fs-3 fw-bold">
            <?= count($knowledge['diet']['recommended']); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="card mb-4 sticky-top disease-nav">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2"> <a href="#overview" class="btn btn-outline-primary btn-sm"> Overview </a> <a href="#symptoms" class="btn btn-outline-primary btn-sm"> Symptoms </a> <a href="#causes" class="btn btn-outline-primary btn-sm"> Causes </a> <a href="#diagnosis" class="btn btn-outline-primary btn-sm"> Diagnosis </a> <a href="#prevention" class="btn btn-outline-primary btn-sm"> Prevention </a> <a href="#treatment" class="btn btn-outline-primary btn-sm"> Treatment </a> <a href="#diet" class="btn btn-outline-primary btn-sm"> Diets </a> <a href="#faqs" class="btn btn-outline-primary btn-sm"> Faqs </a> </div>
    </div>
  </div>
  <?php

function renderKnowledgeSection(
    string $id,
    string $title,
    string $icon,
    string $content,
    $count = ''
): void
{
  if(empty($count)){
    $countBadge = '';
  }else{
    $countBadge = '<span class="badge bg-primary ms-2">'. $count .'</span>';
  }
?>
  <section id="<?= $id; ?>" class="mb-4">
    <div class="card shadow-sm">
      <div class="card-header">
        <h5 class="mb-0"> <i class="bi <?= $icon; ?> me-2"></i>
          <?= e($title); ?> <?= $countBadge; ?>
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

    ),

    ''

);

ob_start();

if (!empty($knowledge['symptoms'])) {

    echo '<div class="d-flex flex-wrap gap-2">';

    foreach ($knowledge['symptoms'] as $symptom) {

        ?>
  <span class="badge rounded-pill bg-primary fs-6 px-3 py-2"> <i class="bi bi-check-circle-fill me-1"></i>
  <?= e($symptom['symptom_en']); ?>
  </span>
  <?php
    }

    echo '</div>';

} else {

    ?>
  <div class="text-muted"> No symptom information available. </div>
  <?php
}

$symptomsHtml = ob_get_clean();

renderKnowledgeSection(

    'symptoms',

    'Symptoms',

    'bi-activity',

    $symptomsHtml,

    $stats['symptoms']

);

ob_start();

?>
  <div class="row">
    <div class="col-lg-5">
      <h6 class="fw-semibold mb-3"> Primary Causes </h6>
      <?php if (!empty($knowledge['causes'])) : ?>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($knowledge['causes'] as $cause) : ?>
        <div class="border rounded p-2"> <i class="bi bi-bug-fill text-danger me-2"></i>
          <?= e($cause['cause_en']); ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else : ?>
      <div class="text-muted"> Cause information not available. </div>
      <?php endif; ?>
    </div>
    <div class="col-lg-7">
      <h6 class="fw-semibold mb-3"> Risk Factors </h6>
      <?php if (!empty($content['risk_factors_en'])) : ?>
      <p class="mb-0">
        <?= nl2br(e($content['risk_factors_en'])); ?>
      </p>
      <?php else : ?>
      <div class="text-muted"> Risk factor information not available. </div>
      <?php endif; ?>
    </div>
  </div>
  <?php

$causesHtml = ob_get_clean();

renderKnowledgeSection(

    'causes',

    'Causes & Risk Factors',

    'bi-bug',

    $causesHtml,

    $stats['causes']

); ?>
  <?php

ob_start();

?>
  <div class="alert alert-info mb-3"> <i class="bi bi-info-circle-fill me-2"></i> Diagnosis is based on a patient's medical history,
    symptoms, physical examination and, when necessary,
    laboratory or imaging investigations. </div>
  <?php if (!empty($content['diagnosis_en'])) : ?>
  <div class="knowledge-content">
    <?= nl2br(e($content['diagnosis_en'])); ?>
  </div>
  <?php else : ?>
  <div class="text-muted"> Diagnosis information is not available yet. </div>
  <?php endif; ?>
  <?php

$diagnosisHtml = ob_get_clean();

if (!empty($knowledge['diagnosis'])) {

    renderKnowledgeSection(

        'diagnosis',

        'Diagnosis',

        'bi-heart-pulse',

        $diagnosisHtml,

        $stats['diagnosis']

    );

}

?>
  <?php

ob_start();

$preventionTips = buildPreventionTips(
    $knowledge['content']['prevention_en'] ?? ''
);

?>
  <?php if (!empty($preventionTips)) : ?>
  <div class="row">
    <div class="col-lg-5">
      <div class="card border-success h-100">
        <div class="card-header bg-success text-white"> <i class="bi bi-shield-check me-2"></i> Key Prevention Tips </div>
        <div class="card-body">
          <?php foreach ($preventionTips as $tip) : ?>
          <div class="mb-3"> <i class="bi bi-check-circle-fill text-success me-2"></i>
            <?= e($tip); ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header"> Prevention Guidance </div>
        <div class="card-body">
          <?= nl2br(
                    e(
                        $knowledge['content']['prevention_en'] ?? ''
                    )
                ); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="alert alert-success mt-4 mb-0"> <i class="bi bi-lightbulb-fill me-2"></i> <strong>Reminder:</strong> Prevention recommendations should be tailored to your personal health status and risk factors. </div>
  <?php endif; ?>
  <?php

$preventionHtml = ob_get_clean();

if (
    !empty(
        trim(
            $knowledge['content']['prevention_en'] ?? ''
        )
    )
) {

    renderKnowledgeSection(

        'prevention',

        'Prevention',

        'bi-shield-check',

        $preventionHtml,

        $stats['prevention']

    );

}

?>
  <?php

ob_start();

?>
  <div class="accordion" id="treatmentAccordion">
    <?php foreach ($knowledge['treatments'] as $index => $treatment) : ?>
    <?php

$icon =
    $treatmentIcons[
        $treatment['treatment_system_id']
    ] ?? 'bi-plus-circle';

$preview =
    mb_substr(
        strip_tags($treatment['overview_en']),
        0,
        140
    );

if (
    mb_strlen(
        strip_tags($treatment['overview_en'])
    ) > 140
) {

    $preview .= '...';

}

$treatmentClasses = [

    1 => 'treatment-card-allopathy',

    2 => 'treatment-card-ayurveda',

    3 => 'treatment-card-homeopathy',

    4 => 'treatment-card-acupressure',

    5 => 'treatment-card-yoga'

];

$cardClass =
    $treatmentClasses[
        $treatment['treatment_system_id']
    ] ?? '';

$treatmentBadges = [

    1 => [
        'text'  => 'Evidence Based',
        'class' => 'bg-primary'
    ],

    2 => [
        'text'  => 'Traditional',
        'class' => 'bg-success'
    ],

    3 => [
        'text'  => 'Complementary',
        'class' => 'bg-secondary'
    ],

    4 => [
        'text'  => 'Supportive',
        'class' => 'bg-warning text-dark'
    ],

    5 => [
        'text'  => 'Wellness',
        'class' => 'bg-info text-dark'
    ]

];

?>
    <div class="accordion-item mb-3 rounded-3 border <?= $cardClass ?>">
      <h2
class="accordion-header"
id="heading<?= $index; ?>">
        <button
class="accordion-button collapsed"
type="button"
data-bs-toggle="collapse"
data-bs-target="#collapse<?= $index; ?>">
        <div class="w-100">
          <div class="fw-bold mb-2"> <i class="bi <?= $icon; ?> me-2 text-primary"></i>
            <?= e($treatment['title_en']); ?>
            <?php
$badge =
    $treatmentBadges[
        $treatment['treatment_system_id']
    ] ?? null;
?>
            <?php if ($badge) : ?>
            <span class="badge <?= $badge['class']; ?> ms-2">
            <?= $badge['text']; ?>
            </span>
            <?php endif; ?>
          </div>
          <div class="small text-muted">
            <?= e($preview); ?>
          </div>
        </div>
        </button>
      </h2>
      <div
id="collapse<?= $index; ?>"
class="accordion-collapse collapse"
data-bs-parent="#treatmentAccordion">
        <div class="treatment-content accordion-body">
          <?= nl2br(e($treatment['overview_en'])) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="alert alert-info mt-4 mb-0"> <i class="bi bi-info-circle me-2"></i> <strong>Medical Note:</strong> The treatment information provided is intended for educational purposes only.
      Always consult a qualified healthcare professional before starting,
      stopping, or changing any treatment. </div>
  </div>
  <?php

$treatmentHtml =
    ob_get_clean();

if (!empty($knowledge['treatments'])) {

    renderKnowledgeSection(

        'treatment',

        'Treatment & Management',

        'bi-capsule-pill',

        $treatmentHtml,

        $stats['treatments']

    );

}
?>
  <?php

ob_start();

?>
  <div class="row g-4">
    <!-- Recommended -->
    <div class="col-lg-6">
      <div class="card border-success h-100">
        <div class="card-header bg-success text-white"> <i class="bi bi-check-circle-fill me-2"></i> Recommended Foods </div>
        <div class="card-body">
          <?php if (!empty($knowledge['diet']['recommended'])) : ?>
          <?php foreach ($knowledge['diet']['recommended'] as $item) : ?>
          <div class="diet-item mb-3">
            <div class="fw-semibold text-success"> <i class="bi bi-dot"></i>
              <?= e($item['item_en']); ?>
            </div>
            <?php if (!empty($item['notes_en'])) : ?>
            <div class="small text-muted">
              <?= e($item['notes_en']); ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php else : ?>
          <div class="text-muted"> Recommendations will be added soon. </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- Avoid -->
    <div class="col-lg-6">
      <div class="card border-danger h-100">
        <div class="card-header bg-danger text-white"> <i class="bi bi-x-circle-fill me-2"></i> Foods to Avoid </div>
        <div class="card-body">
          <?php if (!empty($knowledge['diet']['avoid'])) : ?>
          <?php foreach ($knowledge['diet']['avoid'] as $item) : ?>
          <div class="diet-item mb-3">
            <div class="fw-semibold text-danger"> <i class="bi bi-dot"></i>
              <?= e($item['item_en']); ?>
            </div>
            <?php if (!empty($item['notes_en'])) : ?>
            <div class="small text-muted">
              <?= e($item['notes_en']); ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php else : ?>
          <div class="text-muted"> Avoidance recommendations will be added soon. </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="alert alert-info mt-4 mb-0"> <i class="bi bi-info-circle-fill me-2"></i> <strong>Medical Nutrition Note:</strong> Diet recommendations are intended for general educational purposes and should be tailored to individual health needs by a qualified healthcare professional. </div>
  <?php

$dietHtml = ob_get_clean();

if (

    !empty($knowledge['diet']['recommended']) ||

    !empty($knowledge['diet']['avoid'])

) {

    renderKnowledgeSection(

        'diet',

        'Diet & Nutrition',

        'bi-apple',

        $dietHtml,

        $stats['diet']

    );

}
?>
  <!-- ===========================================================
     FAQ CENTER
=========================================================== -->
  <?php

if (!empty($knowledge['faqs'])) {?>
  <section  id="faqs">
  <div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white">
      <h3 class="h4 mb-0"> <i class="bi bi-patch-question me-2"></i> Frequently Asked Questions <span class="badge bg-primary ms-2">
        <?= count($knowledge['faqs']); ?>
        </span> </h3>
    </div>
    <div class="card-body">
      <?php if (!empty($knowledge['faqs'])) : ?>
      <div
                class="accordion"
                id="faqAccordion">
        <?php foreach ($knowledge['faqs'] as $index => $faq) : ?>
        <div class="accordion-item faq-item mb-3">
          <h2
                            class="accordion-header"
                            id="faqHeading<?= $index; ?>">
            <button
                                class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faqCollapse<?= $index; ?>"> <i class="bi bi-patch-question-fill text-primary me-2"></i>
            <?= e($faq['question_en']); ?>
            </button>
          </h2>
          <div
                            id="faqCollapse<?= $index; ?>"
                            class="accordion-collapse collapse"
                            data-bs-parent="#faqAccordion">
            <div class="accordion-body">
              <div class="faq-answer">
                <?= nl2br(e($faq['answer_en'])); ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else : ?>
      <div class="alert alert-secondary mb-0"> <i class="bi bi-hourglass-split me-2"></i> Frequently asked questions for this disease
        are currently being prepared. </div>
      <?php endif; ?>
      <div class="alert alert-light border mt-4 mb-0"> <i class="bi bi-lightbulb me-2 text-warning"></i> <strong>Helpful Tip:</strong> These answers provide general medical information.
        Consult your healthcare provider for advice tailored to your condition. </div>
    </div>
  </div>
</div>
<?php } ?>
<?php if (!empty($relatedDiseases)) : ?>
<section id="related-diseases" class="mt-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1"> <i class="bi bi-diagram-3 me-2 text-primary"></i> Related Diseases </h3>
      <div class="text-muted"> Diseases with similar symptoms or affecting the same body system. </div>
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
            <div class="text-end"> <span class="badge <?= $badgeClass; ?>">
              <?= e($badgeText); ?>
              </span>
              <div class="small fw-semibold mt-1">
                <?= $score; ?>
                % </div>
            </div>
          </div>
          <div class="small text-muted mb-3"> <i class="bi bi-link-45deg me-1"></i>
            <?= (int) $related['shared_symptom_count']; ?>
            Shared Symptom
            <?= $related['shared_symptom_count'] == 1 ? '' : 's'; ?>
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
            <div class="d-grid gap-2"> <a
                                    href="<?= url('/disease/' . $related['slug']); ?>"
                                    class="btn btn-outline-primary btn-sm"> <i class="bi bi-arrow-right-circle me-2"></i> View Disease </a> <a
                                    href="<?= url(
                                        '/compare/result?d1='
                                        . urlencode($disease['slug'])
                                        . '&d2='
                                        . urlencode($related['slug'])
                                    ); ?>"
                                    class="btn btn-primary btn-sm"> <i class="bi bi-columns-gap me-2"></i> Compare </a> </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<script>
const sections =
    document.querySelectorAll('section[id]');

const navLinks =
    document.querySelectorAll('.disease-nav a');

window.addEventListener(
    'scroll',
    () => {

        let current = '';

        sections.forEach(section => {

            const top =
                section.offsetTop - 120;

            if (window.scrollY >= top) {

                current =
                    section.id;

            }

        });

        navLinks.forEach(link => {

            link.classList.remove(
                'btn-primary'
            );

            link.classList.add(
                'btn-outline-primary'
            );

            if (
                link.getAttribute('href') ===
                '#' + current
            ) {

                link.classList.remove(
                    'btn-outline-primary'
                );

                link.classList.add(
                    'btn-primary'
                );

            }

        });

    }
);

document
.querySelectorAll(
'.accordion-collapse'
)
.forEach(item => {

    item.addEventListener(

        'shown.bs.collapse',

        function () {

            this.scrollIntoView({

                behavior: 'smooth',

                block: 'nearest'

            });

        }

    );

});
</script>

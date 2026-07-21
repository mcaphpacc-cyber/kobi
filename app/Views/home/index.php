<?php

/** @var array $dashboard */

$statistics = $dashboard['statistics'] ?? [];
$bodySystems = $dashboard['bodySystems'] ?? [];
$featuredDiseases = $dashboard['featuredDiseases'] ?? [];

?>

<div class="py-5 text-center">

    <h1 class="display-3 fw-bold text-primary">
        KOBI
    </h1>

    <p class="lead text-muted">
        Knowledge of Body Intelligence
    </p>

    <p class="lead text-secondary mb-2">
        Understand diseases, symptoms, causes, treatments and prevention
        from a single trusted platform.
    </p>

    <p class="text-muted">
        Search intelligently or explore by body system.
    </p>

</div>

<div class="row justify-content-center mb-5">

    <div class="col-lg-8">

        <div class="position-relative">

            <div class="input-group input-group-lg shadow-sm">

                <span class="input-group-text bg-white">

                    <i class="bi bi-search"></i>

                </span>

                <input
                    id="homeSearch"
                    type="text"
                    class="form-control border-start-0"
                    placeholder="Search diseases, symptoms, treatments..."
                    autocomplete="off"
                >

                <button
                    class="btn btn-primary px-4"
                    type="button">

                    Search

                </button>

            </div>

            <div
                id="homeSearchResults"
                class="list-group search-dropdown">
            </div>

        </div>

    </div>

</div>

<div class="row justify-content-center mb-5 g-3">

    <div class="col-md-4">

        <a
            href="<?= url('/symptom-checker') ?>"
            class="btn btn-primary w-100 py-3">

            <i class="bi bi-heart-pulse me-2"></i>

            Symptom Checker

        </a>

    </div>

    <div class="col-md-4">

        <a
            href="<?= url('/diseases') ?>"
            class="btn btn-outline-primary w-100 py-3">

            <i class="bi bi-journal-medical me-2"></i>

            Browse Diseases

        </a>

    </div>

    <div class="col-md-4">

        <a
            href="<?= url('/compare') ?>"
            class="btn btn-outline-primary w-100 py-3">

            <i class="bi bi-shuffle me-2"></i>

            Compare Diseases

        </a>

    </div>

</div>

<div class="row g-4 mb-5">

    <div class="col-lg-3 col-md-6">

        <div class="card shadow-sm h-100">

            <div class="card-body text-center">

                <i class="bi bi-virus display-5 text-primary mb-3"></i>

                <h2 class="fw-bold">

                    <?= (int) ($statistics['diseases'] ?? 0) ?>

                </h2>

                <p class="mb-0 text-muted">

                    Diseases

                </p>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card shadow-sm h-100">

            <div class="card-body text-center">

                <i class="bi bi-diagram-3 display-5 text-success mb-3"></i>

                <h2 class="fw-bold">

                    <?= (int) ($statistics['bodyParts'] ?? 0) ?>

                </h2>

                <p class="mb-0 text-muted">

                    Body Systems

                </p>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card shadow-sm h-100">

            <div class="card-body text-center">

                <i class="bi bi-activity display-5 text-danger mb-3"></i>

                <h2 class="fw-bold">

                    <?= (int) ($statistics['symptoms'] ?? 0) ?>

                </h2>

                <p class="mb-0 text-muted">

                    Symptoms

                </p>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card shadow-sm h-100">

            <div class="card-body text-center">

                <i class="bi bi-exclamation-circle display-5 text-warning mb-3"></i>

                <h2 class="fw-bold">

                    <?= (int) ($statistics['causes'] ?? 0) ?>

                </h2>

                <p class="mb-0 text-muted">

                    Causes

                </p>

            </div>

        </div>

    </div>

</div>

<h2 class="mb-4">
    Browse by Body System
</h2>

<div class="row g-4">

<?php foreach ($dashboard['bodySystems'] as $bodySystem): ?>

    <div class="col-xl-3 col-lg-4 col-md-6">

        <a
            href="<?= url('/diseases?body=' . $bodySystem['slug']) ?>"
            class="card body-system-card h-100 text-decoration-none">

            <div class="card-body text-center">

                <div class="body-system-icon">

                    <i class="bi <?= bodySystemIcon($bodySystem['name']) ?>"></i>

                </div>

                <h5 class="mt-3">

                    <?= e($bodySystem['name']) ?>

                </h5>

                <p class="text-muted mb-3">

                    <?= $bodySystem['disease_count'] ?>

                    Diseases

                </p>

                <span class="btn btn-outline-primary btn-sm">

                    Explore →

                </span>

            </div>

        </a>

    </div>

<?php endforeach; ?>

</div>

<h2 class="mb-4">
    Featured Diseases
</h2>

<div class="row g-3">

<?php foreach ($dashboard['featuredDiseases'] as $disease): ?>

<div class="col-md-3">

<div class="card shadow-sm">

<div class="card-body">

<a
href="<?= url('/disease/' . $disease['slug']) ?>">

<?= e($disease['name']) ?>

</a>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<script src="<?= asset('js/home.js') ?>"></script>
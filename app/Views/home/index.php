<div class="py-5 text-center">

    <h1 class="display-3 fw-bold text-primary">
        KOBI
    </h1>

    <p class="lead text-muted">
        Knowledge of Body Intelligence
    </p>

    <p class="text-secondary">
        Your trusted medical knowledge platform.
    </p>

</div>

<div class="row justify-content-center mb-5">

    <div class="col-lg-8">

        <form action="#" method="get">

            <div class="input-group input-group-lg">

                <input
                    type="text"
                    class="form-control"
                    placeholder="Search diseases, symptoms, causes...">

                <button
                    class="btn btn-primary"
                    type="submit">

                    <i class="bi bi-search"></i>

                    Search

                </button>

            </div>

        </form>

    </div>

</div>

<div class="row g-4 mb-5">

    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h2><?= $dashboard['statistics']['diseases'] ?></h2>
                <p class="mb-0">Diseases</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h2><?= $dashboard['statistics']['bodyParts'] ?></h2>
                <p class="mb-0">Body Parts</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h2><?= $dashboard['statistics']['symptoms'] ?></h2>
                <p class="mb-0">Symptoms</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h2><?= $dashboard['statistics']['causes'] ?></h2>
                <p class="mb-0">Causes</p>
            </div>
        </div>
    </div>

</div>

<h2 class="mb-4">
    Browse by Body Part
</h2>

<div class="row g-3 mb-5">

<?php foreach ($dashboard['bodyParts'] as $bodyPart): ?>

<div class="col-md-3">

<div class="card stat-card shadow-sm">

<div class="card-body text-center">

<h5>

<?= e($bodyPart['name']) ?>

</h5>

</div>

</div>

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
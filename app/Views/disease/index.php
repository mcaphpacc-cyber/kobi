<div class="bg-primary bg-gradient text-white rounded-4 p-5 mb-4">

    <div class="text-center">

        <h1 class="display-5 fw-bold">

            Disease Knowledge Base

        </h1>

        <p class="lead mb-0">

            Explore diseases organized by body system,
            symptoms and medical knowledge.

        </p>

    </div>

</div>

<div class="row g-3 mb-5">

    <div class="col-md-3">
        ...
        🩺 Diseases
        <?= number_format($statistics['diseases']) ?>
    </div>

    <div class="col-md-3">
        ...
        ❤️ Body Systems
        <?= number_format($statistics['bodySystems']) ?>
    </div>

    <div class="col-md-3">
        ...
        🩹 Symptoms
        <?= number_format($statistics['symptoms']) ?>
    </div>

    <div class="col-md-3">
        ...
        🧬 Causes
        <?= number_format($statistics['causes']) ?>
    </div>

</div>
<div class="row g-3 align-items-center mb-4">

    <div class="col-md-8">
        <input
            type="text"
            id="diseaseSearch"
            class="form-control"
            placeholder="Search diseases, symptoms, body systems..."
        >
    </div>

    <div class="col-md-4">
        <select
            id="sortDiseases"
            class="form-select"
        >
            <option value="az">Sort: A → Z</option>
            <option value="za">Sort: Z → A</option>
            <option value="mostSymptoms">Most Symptoms</option>
            <option value="leastSymptoms">Least Symptoms</option>
        </select>
    </div>
    <select
        id="genderFilter"
        class="form-select"
    >
        <option value="all">All Genders</option>
        <option value="both">Both</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
    </select>

</div>
<div class="small text-muted mt-2">

    Showing

    <strong id="visibleDiseaseCount">
        <?= count($diseases) ?>
    </strong>

    of

    <strong>
        <?= count($diseases) ?>
    </strong>

    diseases

</div>
<div class="body-filter-bar mb-4">
    <div class="mb-4">

        <div class="d-flex flex-wrap gap-2 align-items-center">

            <!-- All Diseases -->

            <a
                href="<?= url('/diseases') ?>"
                class="btn btn-sm <?= empty($bodySystem)
                    ? 'btn-primary'
                    : 'btn-outline-primary' ?>"
            >

                <i class="bi bi-grid-3x3-gap"></i>

                All

                <span class="badge bg-light text-dark ms-1">

                    <?= number_format($statistics['diseases']) ?>

                </span>

            </a>

            <?php foreach ($bodySystems as $item): ?>

                <?php

                $active =
                    !empty($bodySystem)
                    &&
                    $bodySystem['slug'] === $item['slug'];

                ?>

                <a
                    href="<?= url('/diseases?body=' . $item['slug']) ?>"
                    class="btn btn-sm <?= $active
                        ? 'btn-primary'
                        : 'btn-outline-primary' ?>"
                >

                    <i class="bi <?= bodySystemIcon($item['name']) ?>"></i>

                    <?= e($item['name']) ?>

                    <span class="badge <?= $active
                        ? 'bg-light text-dark'
                        : 'bg-primary' ?> ms-1">

                        <?= $item['disease_count'] ?>

                    </span>

                </a>

            <?php endforeach; ?>

        </div>

    </div>
</div>
<?php if (!empty($bodySystem)): ?>

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body d-flex justify-content-between align-items-center">

        <div>

            <h4 class="mb-1">

                <i class="bi <?= bodySystemIcon($bodySystem['name']) ?> text-primary"></i>

                <?= e($bodySystem['name']) ?>

            </h4>

            <p class="text-muted mb-0">

                <?= count($diseases) ?>

                disease<?= count($diseases) !== 1 ? 's' : '' ?>

                available in this body system.

            </p>

        </div>

        <a
            href="<?= url('/diseases') ?>"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-grid"></i>

            View All Diseases

        </a>

    </div>

</div>

<?php endif; ?>

<div id="compareToolbar"
     class="alert alert-primary d-none d-flex justify-content-between align-items-center mb-3">

    <div>

        <strong>
            Compare
        </strong>

        <div id="compareList" class="small mt-2 d-inline-flex"></div>

        <span id="compareCounter">
            0 Selected
        </span>

    </div>

    <button
        id="compareNow"
        class="btn btn-primary btn-sm"
        disabled>

        Compare Now

    </button>

</div>

<table id="diseaseTable" class="table table-striped table-hover">

<thead class="table-dark">

<tr>

<th>Disease</th>

<th>Body System</th>

<th>Common Symptoms</th>

<th>Gender</th>

<th class="text-center" style="width:130px;">
    Compare
</th>

</tr>

</thead>

<tbody>
    <?php if (empty($diseases)): ?>

<div class="card border-0 shadow-sm">

    <div class="card-body text-center py-5">

        <i class="bi bi-search fs-1 text-muted"></i>

        <h4 class="mt-3">

            No diseases found

        </h4>

        <p class="text-muted">

            There are currently no diseases
            available for this body system.

        </p>

        <a
            href="<?= url('/diseases') ?>"
            class="btn btn-primary"
        >

            View All Diseases

        </a>

    </div>

</div>

<?php return; ?>

<?php endif; ?>
    <?php if (empty($diseases)): ?>

<div class="alert alert-warning">

    <i class="bi bi-info-circle"></i>

    No diseases found for this body system.

</div>

<?php return; ?>

<?php endif; ?>

<?php foreach ($diseases as $disease): ?>

<tr
    class="disease-row"
    data-name="<?= e(strtolower($disease['name'])) ?>"
    data-body-system="<?= e(strtolower($disease['body_system'])) ?>"
    data-symptoms="<?= e(strtolower($disease['symptoms'])) ?>"
    data-gender="<?= e(strtolower($disease['gender'])) ?>"
    data-symptom-count="<?= (int) $disease['symptom_count'] ?>"
    data-url="<?= url('/disease/' . $disease['slug']) ?>"
>

    <td>
        <a
            href="<?= url('/disease/' . $disease['slug']) ?>"
            class="fw-semibold text-decoration-none"
        >
            <?= e($disease['name']) ?>
        </a>
    </td>

    <td>
        <?= e($disease['body_system']) ?>
    </td>

    <td>
        <?php
        $symptoms = array_slice(
            array_filter(array_map('trim', explode(',', $disease['symptoms']))),
            0,
            3
        );
        ?>
        <div class="d-flex flex-wrap gap-2">
                <?php foreach ($symptoms as $symptom): ?>
                
                    <span
                        class="badge rounded-pill bg-light text-dark border symptom-tag"
                        data-symptom="<?= strtolower(trim($symptom)) ?>"
                    >
                        <?= htmlspecialchars($symptom) ?>
                    </span>

                <?php endforeach; ?>
        </div>
    </td>

    <td>

        <?php
        $genderClass = match ($disease['gender']) {
            'male' => 'bg-primary',
            'female' => 'bg-danger',
            default => 'bg-success'
        };
        ?>

        <span class="badge <?= $genderClass ?>">
            <?= ucfirst($disease['gender']) ?>
        </span>

    </td>

    <td class="text-center">

        <button
            type="button"
            class="btn btn-outline-primary btn-sm compare-btn"
            data-slug="<?= e($disease['slug']) ?>"
            data-name="<?= e($disease['name']) ?>"
        >

            <i class="bi bi-plus-lg"></i>

            Compare

        </button>

    </td>

</tr>

<?php endforeach; ?>

</tbody>

</table>
<nav class="mt-4">
    <ul
        id="catalogPagination"
        class="pagination justify-content-center">
    </ul>
</nav>
<?php

declare(strict_types=1);

$selectedDisease = $leftDisease['disease'] ?? null;

?>

<div class="container py-4">

    <div class="text-center mb-5">

        <h1 class="display-6 fw-bold">

            <i class="bi bi-columns-gap text-primary me-2"></i>

            Compare Diseases

        </h1>

        <p class="text-muted">

            Select two diseases to compare symptoms, causes, diagnosis, prevention and treatment.

        </p>

    </div>

    <div class="card shadow-sm">

        <div class="card-body p-4">

            <div class="row g-4">

                <!-- Disease A -->

                <div class="col-lg-6">

                    <label class="form-label fw-semibold">

                        Disease A

                    </label>

                    <div id="leftSelector">

                        <input
                            id="leftDiseaseSearch"
                            type="text"
                            class="form-control"
                            placeholder="Search disease..."
                            autocomplete="off"
                            value="<?= e($selectedDisease['disease_en'] ?? '') ?>">

                        <input
                            id="leftDiseaseSlug"
                            type="hidden"
                            value="<?= e($selectedDisease['slug'] ?? '') ?>">

                        <div
                            id="leftResults"
                            class="list-group mt-2"></div>

                    </div>

                    <div
                        id="leftSelected"
                        class="<?= empty($selectedDisease) ? 'd-none' : '' ?>">

                        <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">

                            <div>

                                <i class="bi bi-check-circle-fill me-2"></i>

                                <strong id="leftSelectedName">

                                    <?= e($selectedDisease['disease_en'] ?? '') ?>

                                </strong>

                            </div>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success"
                                onclick="clearLeftDisease()">

                                Change

                            </button>

                        </div>

                    </div>

                </div>

                <!-- Disease B -->

                <div class="col-lg-6">

                    <label class="form-label fw-semibold">

                        Disease B

                    </label>

                    <div id="rightSelector">

                        <input
                            id="rightDiseaseSearch"
                            type="text"
                            class="form-control"
                            placeholder="Search disease..."
                            autocomplete="off"
                            value="">

                        <input
                            id="rightDiseaseSlug"
                            type="hidden"
                            value="">

                        <div
                            id="rightResults"
                            class="list-group mt-2"></div>

                    </div>

                    <div
                        id="rightSelected"
                        class="d-none">

                        <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">

                            <div>

                                <i class="bi bi-check-circle-fill me-2"></i>

                                <strong id="rightSelectedName">


                                </strong>

                            </div>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success"
                                onclick="clearRightDisease()">

                                Change

                            </button>

                        </div>

                    </div>

                </div>

                <div
                    id="compareValidation"
                    class="small text-danger mt-2 d-none">

                    Please select two different diseases.

                </div>

            </div>

            <hr class="my-4">

            <div class="text-center">

                <button
                    id="compareButton"
                    class="btn btn-primary btn-lg"
                    disabled>

                    <i class="bi bi-columns-gap me-2"></i>

                    Compare Diseases

                </button>

            </div>

        </div>

    </div>

</div>

<script>

const searchEndpoint =
    window.KOBI.apiBase + '/diseases/search';

const leftSearch =
    document.getElementById('leftDiseaseSearch');

const rightSearch =
    document.getElementById('rightDiseaseSearch');

const leftSlug =
    document.getElementById('leftDiseaseSlug');

const rightSlug =
    document.getElementById('rightDiseaseSlug');

const leftResults =
    document.getElementById('leftResults');

const rightResults =
    document.getElementById('rightResults');

const compareButton =
    document.getElementById('compareButton');

let activeIndex = -1;

let activeContainer = null;

function updateCompareButton()
{
    const validation =
    document.getElementById('compareValidation');

    const sameDisease =
        leftSlug.value !== '' &&
        leftSlug.value === rightSlug.value;

    validation.classList.toggle(
        'd-none',
        !sameDisease
    );

    compareButton.disabled =
        !leftSlug.value ||
        !rightSlug.value ||
        sameDisease;
}

async function searchDiseases(
    keyword,
    container,
    input,
    hidden,
    side
)
{
    container.innerHTML = '';
    container.innerHTML = `
        <div class="list-group-item text-muted small">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Searching...
        </div>
    `;

    if (keyword.length < 2) {

        updateCompareButton();

        return;

    }

    const response =
        await fetch(
            searchEndpoint +
            '?q=' +
            encodeURIComponent(keyword)
        );

    const diseases =
        await response.json();

    container.innerHTML = '';

        if (diseases.length === 0) {

            container.innerHTML = `
                <div class="list-group-item text-muted">
                    <i class="bi bi-search me-2"></i>
                    No diseases found.
                </div>
            `;

            return;
        }

        diseases.forEach(disease => {

            const item = document.createElement('button');

            item.type = 'button';

            item.className =
                'list-group-item list-group-item-action';

            item.textContent =
                disease.name;

            item.onclick = () => {

                input.value = disease.name;

                hidden.value = disease.slug;

                document.getElementById(side + 'SelectedName').textContent =
                    disease.name;

                document.getElementById(side + 'Selector')
                    .classList.add('d-none');

                document.getElementById(side + 'Selected')
                    .classList.remove('d-none');

                container.innerHTML = '';

                updateCompareButton();

            };

            container.appendChild(item);

        });
}

function bindKeyboardNavigation(
    input,
    container
)
{
    input.addEventListener(
        'keydown',
        e => {

            const items =
                container.querySelectorAll(
                    '.list-group-item-action'
                );

            if (!items.length) {
                return;
            }

            switch (e.key) {

                case 'ArrowDown':

                    e.preventDefault();

                    activeIndex =
                        Math.min(
                            activeIndex + 1,
                            items.length - 1
                        );

                    break;

                case 'ArrowUp':

                    e.preventDefault();

                    activeIndex =
                        Math.max(
                            activeIndex - 1,
                            0
                        );

                    break;

                case 'Enter':

                    if (activeIndex >= 0) {

                        e.preventDefault();

                        items[activeIndex].click();

                    }

                    return;

                case 'Escape':

                    container.innerHTML = '';

                    activeIndex = -1;

                    return;

                default:

                    return;

            }

            items.forEach(
                item =>
                    item.classList.remove('active')
            );

            items[activeIndex]
                .classList.add('active');

            items[activeIndex]
                .scrollIntoView({
                    block: 'nearest'
                });

        }
    );
}

leftSearch.addEventListener(
    'input',
    e => {

        searchDiseases(
            e.target.value,
            leftResults,
            leftSearch,
            leftSlug,
            "left"
        );

    }
);

rightSearch.addEventListener(
    'input',
    e => {

        searchDiseases(
            e.target.value,
            rightResults,
            rightSearch,
            rightSlug,
            "right"
        );

    }
);

compareButton.addEventListener(
    'click',
    () => {

        window.location =
            window.KOBI.baseUrl +
            'compare/result?d1=' +
            encodeURIComponent(leftSlug.value) +
            '&d2=' +
            encodeURIComponent(rightSlug.value);

    }
);

bindKeyboardNavigation(
    leftSearch,
    leftResults
);

bindKeyboardNavigation(
    rightSearch,
    rightResults
);

updateCompareButton();

function clearLeftDisease()
{
    leftSlug.value = '';

    leftSearch.value = '';

    leftResults.innerHTML = '';

    document
        .getElementById('leftSelected')
        .classList.add('d-none');

    document
        .getElementById('leftSelector')
        .classList.remove('d-none');

    leftSearch.focus();

    updateCompareButton();
}

function clearRightDisease()
{
    rightSlug.value = '';

    rightSearch.value = '';

    rightResults.innerHTML = '';

    document
        .getElementById('rightSelected')
        .classList.add('d-none');

    document
        .getElementById('rightSelector')
        .classList.remove('d-none');

    rightSearch.focus();

    updateCompareButton();
}

</script>
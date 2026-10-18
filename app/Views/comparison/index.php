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
<script src="<?= asset('js/kobi-config.js') ?>"></script>
<script src="<?= asset('js/compare.js') ?>?v=<?= filemtime(dirname(__DIR__, 3) . '/public/assets/js/compare.js') ?>"></script>

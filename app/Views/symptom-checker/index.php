<div class="container py-4">

    <!-- Page Header -->
    <div class="text-center mb-4">

        <span class="badge bg-primary mb-2">
            Beta
        </span>

        <h1 class="mb-2">
            Symptom Checker
        </h1>

        <p class="text-muted">
            Select one or more symptoms to explore possible medical conditions.
        </p>

    </div>

    <!-- Medical Disclaimer -->
    <div class="alert alert-warning mb-4">

        <h5 class="mb-3">
            Medical Disclaimer
        </h5>

        <p class="mb-0">
            KOBI Symptom Checker is an educational tool.
            It does not provide a medical diagnosis.
            Always consult a qualified healthcare professional.
        </p>

    </div>

    <div class="row">

        <!-- LEFT COLUMN -->
        <div class="col-lg-8">

            <!-- Search -->
            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h3 class="mb-3">
                        Search Symptoms
                    </h3>

                    <input
                        id="symptomSearch"
                        type="text"
                        class="form-control"
                        placeholder="Search symptoms...">

                    <!-- Suggestions -->
                    <div
                        id="searchResults"
                        class="list-group mt-2">
                    </div>

                </div>

            </div>

            <!-- Popular Symptoms -->
            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h3 class="mb-3">
                        Popular Symptoms
                    </h3>

                    <div
                        id="popularSymptoms"
                        class="d-flex flex-wrap gap-2">

                        <?php foreach ($popularSymptoms as $symptom): ?>

                            <button
                                type="button"
                                class="btn btn-outline-primary symptom-chip"
                                data-id="<?= $symptom['id'] ?>"
                                data-name="<?= htmlspecialchars($symptom['symptom_en']) ?>">

                                <?= htmlspecialchars($symptom['symptom_en']) ?>

                            </button>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>
            <div
                id="loadingState"
                class="alert alert-primary d-none text-center my-4">

                <div class="spinner-border text-primary mb-3" role="status">

                    <span class="visually-hidden">
                        Loading...
                    </span>

                </div>

                <h5 class="mb-2">

                    Analyzing symptoms...

                </h5>

                <div class="text-muted">

                    Finding possible conditions...

                </div>

            </div>
            <!-- Results Summary -->
            <div
                id="resultsSummary"
                class="alert alert-info d-none mt-4">

                <h5 class="mb-3">

                    Results Summary

                </h5>

                <div class="row">

                    <div class="col-md-3">

                        <strong>Selected Symptoms</strong>

                        <div id="summarySelected">
                            0
                        </div>

                    </div>

                    <div class="col-md-3">

                        <strong>Matching Diseases</strong>

                        <div id="summaryMatches">
                            0
                        </div>

                    </div>

                    <div class="col-md-3">

                        <strong>Showing</strong>

                        <div id="summaryShowing">
                            Top 10
                        </div>

                    </div>

                    <div class="col-md-3">

                        <strong>Sorted By</strong>

                        <div>
                            Overall Match
                        </div>

                    </div>

                </div>

            </div>
            
            <div
                id="emptyState"
                class="alert alert-secondary d-none text-center my-4">

                <h5 id="emptyTitle"></h5>

                <p
                    id="emptyMessage"
                    class="mb-0 text-muted">

                </p>

            </div>
            <!-- Disease Results -->
            <div id="matchResults"></div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-4">

            <div class="sticky-top" style="top:20px;">

                <!-- Selected Symptoms -->
                <div class="card shadow-sm mb-3">

                    <div class="card-body">

                        <h3 class="mb-3">
                            Selected Symptoms
                        </h3>

                        <div id="selectedSymptoms"></div>

                        <hr>

                        <div class="d-flex justify-content-between">

                            <strong>Selected</strong>

                            <span
                                id="symptomCounter"
                                class="badge bg-primary">

                                0

                            </span>

                        </div>

                        <button
                            id="findConditions"
                            class="btn btn-primary w-100 mt-3"
                            disabled>

                            Find Possible Conditions

                        </button>

                    </div>

                </div>

                <!-- Tips -->
                <div class="card shadow-sm mb-3">

                    <div class="card-body">

                        <h5>

                            Tip

                        </h5>

                        <p class="text-muted mb-0">

                            Add more symptoms to improve educational matching.

                        </p>

                    </div>

                </div>

                <!-- Future Emergency Placeholder -->
                <div
                    id="emergencyWarning"
                    class="alert alert-danger d-none">

                </div>

            </div>

        </div>

    </div>

</div>
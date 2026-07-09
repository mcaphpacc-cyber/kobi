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

                    <h2 class="h3 mb-3">

                        Search Symptoms

                    </h2>

                    <div class="search-wrapper">

                        <div
                            id="tagInput"
                            class="tag-input">

                            <div
                                id="selectedTags"
                                class="tag-list">
                            </div>

                            <input
                                id="symptomSearch"
                                type="text"
                                autocomplete="off"
                                placeholder="Search symptoms..."
                                class="tag-search">

                        </div>
                        <div class="search-area">
                            <div
                                id="searchResults"
                                class="search-results">
                            </div>
                        </div>

                    </div>
                    <div
                        id="recentSearches"
                        class="mt-3 d-none">

                    </div>

                <div
                    class="d-flex justify-content-between align-items-center mt-3">

                    <small
                        id="selectedCounter"
                        class="text-muted">

                        0 symptoms selected

                    </small>

                    <button

                        id="clearSymptoms"

                        class="btn btn-sm btn-outline-danger d-none">

                        <i class="bi bi-trash"></i>

                        Clear All

                    </button>

                </div>
                <div class="mt-3">

                    <button
                        id="findConditions"
                        class="btn btn-primary w-100">

                        <i class="bi bi-search me-2"></i>

                        Find Possible Conditions

                    </button>

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
        <!-- Popular Symptoms -->
         <div class="col-lg-4">
            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h3 class="h3 mb-0">

                            🔥 Popular Symptoms

                        </h3>

                        <span
                            class="badge bg-primary"
                            id="popularCount">

                            <?= 'Top ' . count($popularSymptoms) ?>

                        </span>

                    </div>

                    <?php if (!empty($popularSymptoms)): ?>

                        <div class="d-flex flex-wrap gap-2">

                            <?php foreach ($popularSymptoms as $symptom): ?>

                                <button
                                    type="button"
                                    class="btn btn-outline-primary popular-symptom"
                                    data-id="<?= $symptom['id'] ?>"
                                    data-name="<?= htmlspecialchars($symptom['symptom_en']) ?>">

                                    <?= htmlspecialchars($symptom['symptom_en']) ?>

                                </button>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="text-muted">

                            No popular symptoms available.

                        </div>

                    <?php endif; ?>

                </div>

            </div>
        </div>
    </div>

</div>
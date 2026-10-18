<h1 class="mb-3">Medicines</h1>
<p class="lead">Search substance / generic-name registry entries.</p>
<div class="alert alert-info" id="substanceContext">
    These are legacy registry names, not clinically reviewed or KOBI-approved Medicine products.
    This lookup provides names only, not prescribing guidance or medical recommendations.
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form id="substanceSearchForm" role="search" action="<?= e(url('/medicines')) ?>" method="get">
            <label for="substanceQuery" class="form-label">Substance or generic name</label>
            <div class="row g-2">
                <div class="col-sm">
                    <input type="search" id="substanceQuery" name="q" class="form-control"
                        autocomplete="off" aria-describedby="substanceSearchHelp substanceContext" value="<?= e($query ?? '') ?>">
                </div>
                <div class="col-sm-auto">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
            <p id="substanceSearchHelp" class="form-text mb-0">
                Search by the beginning of a name. Up to 10 matching registry names are shown; refine your search for more specific results.
            </p>
        </form>
    </div>
</div>

<section class="mt-4" aria-labelledby="substanceResultsHeading">
    <h2 id="substanceResultsHeading" class="h4">Matching registry names</h2>
    <p id="substanceSearchStatus" role="status" aria-live="polite" aria-atomic="true">
        Enter a substance name and select Search.
    </p>
    <ul id="substanceResults" class="list-group" aria-busy="false"></ul>
</section>

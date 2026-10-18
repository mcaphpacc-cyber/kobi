<h1 class="mb-3">Search KOBI</h1>
<p>Find public information and registry names. Results are for navigation, not diagnosis or treatment recommendations.</p>
<form role="search" action="<?= e(url('/search')) ?>" method="get" class="card card-body mb-4">
    <label for="publicQuery" class="form-label">Disease or substance name</label>
    <div class="row g-2">
        <div class="col-sm">
            <input id="publicQuery" name="q" type="search" class="form-control" value="<?= e($query) ?>"
                maxlength="100" placeholder="For example, Migraine or Paracetamol"
                aria-describedby="publicSearchHelp<?= $error ? ' publicSearchError' : '' ?>"<?= $error ? ' aria-invalid="true"' : '' ?>>
        </div>
        <div class="col-sm-auto"><button class="btn btn-primary w-100" type="submit">Search</button></div>
    </div>
    <p id="publicSearchHelp" class="form-text mb-0">Enter 2–100 characters. Up to 10 matches per group are shown.</p>
</form>
<?php if ($error): ?>
    <div id="publicSearchError" role="alert" class="alert alert-danger"><?= e($error) ?></div>
<?php elseif (!$result['searched']): ?>
    <p>Enter a disease or substance name to search public KOBI knowledge.</p>
<?php else: ?>
    <h2 class="h4 text-break">Results for “<?= e($query) ?>”</h2>
    <?php if (!$result['diseases'] && !$result['substances']): ?>
        <p>No matching public knowledge found. Try a shorter name or a different name.</p>
    <?php endif; ?>
    <?php if ($result['diseases']): ?>
        <section class="my-4" aria-labelledby="diseaseSearchHeading">
            <h2 id="diseaseSearchHeading" class="h4">Diseases</h2>
            <ul class="list-group">
                <?php foreach ($result['diseases'] as $entry): ?>
                    <li class="list-group-item text-break">
                        <a class="d-block py-2" href="<?= e($entry['url']) ?>"><?= e($entry['label']) ?><?php if ($entry['context']): ?> <span class="small">— <?= e($entry['context']) ?></span><?php endif; ?></a>
                        <small class="text-muted">Disease</small>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($result['diseases_more']): ?><p class="mt-2">More disease matches exist; refine your search.</p><?php endif; ?>
            <a href="<?= e(url('/diseases')) ?>" class="d-inline-block py-2">Browse Disease Catalog</a>
        </section>
    <?php endif; ?>
    <?php if ($result['substances']): ?>
        <section class="my-4" aria-labelledby="substanceSearchHeading">
            <h2 id="substanceSearchHeading" class="h4">Medicines / Substances — registry names</h2>
            <p>Legacy registry names only, not clinically reviewed or KOBI-approved Medicine products. No prescribing guidance or medical recommendations.</p>
            <ul class="list-group">
                <?php foreach ($result['substances'] as $entry): ?>
                    <li class="list-group-item text-break">
                        <a class="d-block py-2" href="<?= e($entry['url']) ?>"><?= e($entry['label']) ?><span class="visually-hidden"> — open registry lookup</span></a>
                        <small class="text-muted">Substance · Legacy registry name</small>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($result['substances_more']): ?><p class="mt-2">More registry-name matches exist; refine your search.</p><?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
<nav aria-label="Other discovery tools" class="d-flex flex-wrap gap-3 mt-4">
    <a class="py-2" href="<?= e(url('/symptom-checker')) ?>">Symptom Checker</a>
    <a class="py-2" href="<?= e(url('/discovery')) ?>">Discovery</a>
</nav>

<div class="container py-4">
    <h1 class="h3">Create treatment draft</h1>
    <p>The draft starts unpublished and unassessed. Enter a title in English or Hindi. Review and publication are separate later steps.</p>
    <form method="post" action="<?= e(url('admin/treatments/create')) ?>" class="card card-body">
        <?= csrfField() ?>
        <div class="row mb-3">
            <div class="col-md-6"><label for="create-disease" class="form-label">Existing disease ID</label><input class="form-control" id="create-disease" type="number" name="disease_id" min="1" step="1" required></div>
            <div class="col-md-6"><label for="create-system" class="form-label">Treatment system</label><select class="form-select" id="create-system" name="treatment_system_id" required><option value="">Select a permitted active system</option><?php foreach ($systems as $system): ?><option value="<?= (int)$system['id'] ?>"><?= e($system['name_en']) ?></option><?php endforeach; ?></select></div>
        </div>
        <fieldset><legend class="h5">Initial protocol content</legend><div class="row"><?php $fieldNames=['title_en','title_hi','overview_en','overview_hi']; $fieldPrefix='protocol'; $fieldValues=[]; require __DIR__.'/_fields.php'; ?></div></fieldset>
        <fieldset class="mb-3"><legend class="h5">Optional first item</legend>
            <div class="form-check mb-3"><input class="form-check-input" id="include-first-item" type="checkbox" name="include_first_item" value="1"><label class="form-check-label" for="include-first-item">Create this first item together with the draft</label></div>
            <p class="text-muted">Leave these fields blank if no item is needed yet. An initial item stays excluded and unassessed. Later additions create a new revision.</p>
            <div class="row"><?php $fieldNames=['item_type','title_en','title_hi','instructions_en','instructions_hi','sort_order']; $fieldPrefix='first_item'; $fieldValues=[]; require __DIR__.'/_fields.php'; ?></div>
        </fieldset>
        <div><button class="btn btn-primary">Create unpublished draft</button> <a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments')) ?>">Cancel</a></div>
    </form>
</div>

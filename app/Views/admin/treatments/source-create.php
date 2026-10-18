<?php use App\Services\TreatmentGovernanceRules; ?>
<div class="container py-4">
    <h1 class="h3">Create an immutable source version</h1>
    <p>New sources start unverified. A replacement preserves its predecessor and does not move existing references. Check all copied bibliographic fields before saving.</p>
    <form method="post" action="<?= e(url('admin/treatment-sources/'.$systemId.'/create')) ?>" class="card card-body">
        <?= csrfField() ?>
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label" for="source-type">Source type</label><select class="form-select" name="source_type" id="source-type" required><option value="">Select source type</option><?php foreach (TreatmentGovernanceRules::SOURCE_TYPES as $option): ?><option value="<?= e($option) ?>" <?= ($source['source_type']??null)===$option ? 'selected' : '' ?>><?= e(str_replace('_',' ',$option)) ?></option><?php endforeach; ?></select></div>
        <?php foreach (['title'=>500,'author_or_organization'=>255,'publication'=>255,'url'=>2048,'identifier'=>255,'publication_date'=>null,'edition_or_version'=>100,'accessed_on'=>null,'replaces_source_id'=>null] as $field=>$limit): ?>
            <div class="col-md-6 mb-3"><label for="source-<?= e($field) ?>" class="form-label"><?= e(ucfirst(str_replace('_',' ',$field))) ?></label><input class="form-control" id="source-<?= e($field) ?>" name="<?= e($field) ?>" type="<?= in_array($field,['publication_date','accessed_on'],true) ? 'date' : ($field==='url' ? 'url' : ($field==='replaces_source_id' ? 'number' : 'text')) ?>" <?= $field==='title' ? 'required' : '' ?> <?= $field==='replaces_source_id' ? 'min="1"' : '' ?> <?= $limit===null ? '' : 'maxlength="'.$limit.'"' ?> value="<?= e((string)($source[$field]??'')) ?>"></div>
        <?php endforeach; ?></div>
        <label class="form-label" for="source-citation">Citation text</label><textarea class="form-control mb-3" rows="5" name="citation_text" id="source-citation" required><?= e($source['citation_text']??'') ?></textarea>
        <p class="small text-muted">Optional URLs must use http or https. KOBI does not fetch or automatically verify them.</p>
        <div><button class="btn btn-primary">Create unverified source</button> <a class="btn btn-outline-secondary" href="<?= e(url('admin/treatment-sources/'.$systemId)) ?>">Cancel</a></div>
    </form>
</div>

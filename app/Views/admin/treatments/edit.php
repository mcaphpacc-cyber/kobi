<?php
use App\Services\TreatmentGovernanceRules;
$published=$protocol['publication_status']==='published';
$sourceMap=array_column($sources,null,'id');
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h3">Treatment protocol #<?= (int)$protocol['id'] ?></h1><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments')) ?>">Editorial list</a></div>
    <p><?= e($identity['disease_en']??'Disease') ?> — <?= e($identity['system_name']??'Treatment system') ?></p>
    <p><a href="<?= e(url('admin/treatments/'.$protocol['id'].'/operations')) ?>">Operations and history</a></p>
    <p>Review: <strong><?= e($protocol['review_state']) ?></strong> · Publication: <strong><?= e($protocol['publication_status']) ?></strong> · Revision: <?= (int)$protocol['content_revision'] ?> · Published languages: <?= e($protocol['published_locales'] ?: 'None') ?></p>
    <?php if (($_GET['created']??'')==='1'): ?><div class="alert alert-success" role="status">Draft created. Review and publication are still required.</div><?php endif; ?>
    <?php if (!$published): ?><p><a class="btn btn-outline-primary" href="<?= e(url('admin/treatments/'.$protocol['id'].'/items/create')) ?>">Add treatment item</a></p><?php endif; ?>
    <?php if (($_GET['saved']??'')==='1'): ?><div class="alert alert-success" role="status">Save completed. Content has not been automatically approved or published.</div><?php endif; ?>
    <?php if (($_GET['submitted']??'')==='1'): ?><div class="alert alert-success" role="status">Submitted for later review. Content has not been approved or published.</div><?php endif; ?>
    <?php if ($published): ?><div class="alert alert-warning">Published content is read-only. It must first be withdrawn through the approved publication workflow.</div><?php endif; ?>
    <?php if ($protocol['publication_status']==='withdrawn'): ?><div class="alert alert-info">Make a substantive governed edit before submitting again. The edit creates a fresh unpublished draft revision; withdrawal history remains retained.</div><?php endif; ?>
    <div class="alert alert-info">Existing items remain excluded until you explicitly include them. Save content before managing references or submitting for review. Each action reloads the revision.</div>
    <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/save')) ?>">
        <?= csrfField() ?>
        <input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>">
        <fieldset <?= $published ? 'disabled' : '' ?>><legend class="h5">Protocol content</legend>
            <div class="row"><?php $fieldNames=array_diff(TreatmentGovernanceRules::PROTOCOL_CONTENT,['disease_id','treatment_system_id']); $fieldPrefix='protocol'; $fieldValues=$protocol; require __DIR__.'/_fields.php'; ?></div>
            <h2 class="h5">Existing items</h2>
            <?php foreach ($items as $item): ?><details class="card card-body mb-3"><summary>Item #<?= (int)$item['id'] ?> — <?= e($item['title_en']??'Untitled') ?> (<?= (int)$item['is_included']===1 ? 'Included' : 'Excluded' ?>)</summary><div class="row mt-3"><?php $fieldNames=TreatmentGovernanceRules::ITEM_CONTENT; $fieldPrefix='items['.$item['id'].']'; $fieldValues=$item; require __DIR__.'/_fields.php'; ?></div></details><?php endforeach; ?>
            <input type="hidden" name="form_complete" value="1">
            <?php if (!$published): ?><button class="btn btn-primary mb-4">Save draft content</button><?php endif; ?>
        </fieldset>
    </form>
    <h2 class="h5">Explicit sources</h2>
    <p><a href="<?= e(url('admin/treatment-sources/'.$protocol['treatment_system_id'])) ?>">Browse source catalog for this editorial scope</a></p>
    <?php $referenceItemId=null; $referenceRows=$protocol_sources; require __DIR__.'/_references.php'; ?>
    <?php foreach ($items as $item): $referenceItemId=(int)$item['id']; $referenceRows=array_values(array_filter($item_sources,fn($r)=>(int)$r['item_id']===$referenceItemId)); require __DIR__.'/_references.php'; endforeach; ?>
    <?php if ($protocol['publication_status']==='unpublished' && in_array($protocol['review_state'],['draft','changes_required'],true)): ?>
        <form class="card card-body" method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/submit')) ?>">
            <?= csrfField() ?><input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>">
            <h2 class="h5">Submit saved content for review</h2><p>Selected languages need complete content, classifications, limitations and explicit verified protocol/item references. Submission preserves the revision and records a snapshot.</p>
            <div class="form-check"><input class="form-check-input" id="submit-en" type="checkbox" name="locales[]" value="en"><label class="form-check-label" for="submit-en">English</label></div><div class="form-check mb-3"><input class="form-check-input" id="submit-hi" type="checkbox" name="locales[]" value="hi"><label class="form-check-label" for="submit-hi">Hindi</label></div>
            <div><button class="btn btn-outline-primary">Submit for review</button></div>
        </form>
    <?php endif; ?>
</div>

<?php $parentLabel=$referenceItemId===null ? 'Protocol overview' : 'Item #'.$referenceItemId; ?>
<div class="card card-body mb-3">
    <h3 class="h6"><?= e($parentLabel) ?> references</h3>
    <?php foreach ($referenceRows as $ref): $sourceRow=$sourceMap[(int)$ref['source_id']]??[]; ?>
        <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/references')) ?>" class="border rounded p-3 mb-2">
            <?= csrfField() ?>
            <input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>">
            <input type="hidden" name="source_id" value="<?= (int)$ref['source_id'] ?>"><input type="hidden" name="item_id" value="<?= $referenceItemId===null ? '' : (int)$referenceItemId ?>">
            <p><a href="<?= e(url('admin/treatment-sources/'.$protocol['treatment_system_id'].'/'.$ref['source_id'])) ?>">#<?= (int)$ref['source_id'] ?> <?= e($sourceRow['title']??'Source') ?></a> — <?= e($sourceRow['status']??'Unavailable') ?></p>
            <label class="form-label" for="claim-<?= e((string)($referenceItemId??'protocol')) ?>-<?= (int)$ref['id'] ?>">Claim scope</label><textarea id="claim-<?= e((string)($referenceItemId??'protocol')) ?>-<?= (int)$ref['id'] ?>" name="claim_scope" class="form-control mb-2" <?= $published ? 'disabled' : '' ?>><?= e($ref['claim_scope']) ?></textarea>
            <label class="form-label" for="locator-<?= e((string)($referenceItemId??'protocol')) ?>-<?= (int)$ref['id'] ?>">Locator</label><input id="locator-<?= e((string)($referenceItemId??'protocol')) ?>-<?= (int)$ref['id'] ?>" name="locator" maxlength="500" class="form-control mb-2" value="<?= e($ref['locator']??'') ?>" <?= $published ? 'disabled' : '' ?>>
            <?php if (!$published): ?><button class="btn btn-outline-primary btn-sm" name="operation" value="update">Update claim</button> <button class="btn btn-outline-danger btn-sm" name="operation" value="remove">Remove relationship</button><?php endif; ?>
        </form>
    <?php endforeach; ?>
    <?php if (!$referenceRows): ?><p class="text-muted">No explicit references. Sources are not inherited from other claims.</p><?php endif; ?>
    <?php if (!$published): ?>
        <details><summary>Attach a source to <?= e($parentLabel) ?></summary>
            <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/references')) ?>" class="mt-3">
                <?= csrfField() ?>
                <input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>"><input type="hidden" name="operation" value="attach"><input type="hidden" name="item_id" value="<?= $referenceItemId===null ? '' : (int)$referenceItemId ?>">
                <label class="form-label" for="source-<?= e((string)($referenceItemId??'protocol')) ?>">Source ID</label><input id="source-<?= e((string)($referenceItemId??'protocol')) ?>" class="form-control mb-2" type="number" name="source_id" min="1" required>
                <label class="form-label" for="new-claim-<?= e((string)($referenceItemId??'protocol')) ?>">Claim scope</label><textarea id="new-claim-<?= e((string)($referenceItemId??'protocol')) ?>" class="form-control mb-2" name="claim_scope" required></textarea>
                <label class="form-label" for="new-locator-<?= e((string)($referenceItemId??'protocol')) ?>">Locator (optional)</label><input id="new-locator-<?= e((string)($referenceItemId??'protocol')) ?>" name="locator" maxlength="500" class="form-control mb-2">
                <p class="small text-muted">Use a verified source for submission. Unverified sources may be collected in drafts; withdrawn sources cannot be attached.</p><button class="btn btn-outline-primary">Attach source</button>
            </form>
        </details>
    <?php endif; ?>
</div>

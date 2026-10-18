<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h3">Protocol #<?= (int)$protocol['id'] ?> operations and history</h1><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/operations')) ?>">Operations list</a></div>
    <p><?= e($identity['disease_en']??'Disease') ?> — <?= e($identity['system_name']??'Treatment system') ?></p>
    <p>Review: <strong><?= e($protocol['review_state']) ?></strong> · Publication: <strong><?= e($protocol['publication_status']) ?></strong> · Revision: <?= (int)$protocol['content_revision'] ?> · Published languages: <?= e($protocol['published_locales'] ?: 'None') ?></p>
    <?php if (($_GET['withdrawn']??'')==='1'): ?><div class="alert alert-success" role="status">Withdrawal recorded. Review and publication must follow the governed lifecycle.</div><?php endif; ?>
    <?php if (($_GET['reset']??'')==='1'): ?><div class="alert alert-success" role="status">Review reset recorded. Previous review events remain in history.</div><?php endif; ?>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <?php if ($canEdit): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/'.$protocol['id'].'/edit')) ?>">Editorial detail</a><?php endif; ?>
        <?php if ($canReview): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/'.$protocol['id'].'/review')) ?>">Review detail</a><?php endif; ?>
    </div>
    <?php if ($protocol['publication_status']==='withdrawn'): ?><div class="alert alert-info">Withdrawn content cannot be directly republished. An editor must make a substantive governed edit creating a new unpublished revision, then submit it for fresh independent review.</div><?php endif; ?>
    <?php if ($canWithdraw): ?>
        <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/withdraw')) ?>" class="card card-body mb-4">
            <?= csrfField() ?><input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>">
            <h2 class="h5">Withdraw published protocol</h2><p>Confirm withdrawal by recording a reason. This removes published status, clears approval and languages, and returns review to draft at the same content revision. Existing content and events remain in history.</p>
            <label for="withdraw-reason" class="form-label">Withdrawal reason</label><textarea id="withdraw-reason" name="reason" class="form-control mb-3" rows="4" required></textarea><div><button class="btn btn-danger">Confirm withdrawal</button></div>
        </form>
    <?php endif; ?>
    <?php if ($canReset): ?>
        <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/reset-review')) ?>" class="card card-body mb-4">
            <?= csrfField() ?><input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>">
            <h2 class="h5">Reset review</h2><p><?= $protocol['review_state']==='approved' ? 'Confirm removal of the current approval. This records a new pending-review submission at the same revision using the prior approval languages. Fresh independent approval is required.' : 'Confirm cancellation of the pending review. This returns review to draft at the same revision; an editor must submit it again.' ?> Historical reviews and snapshots remain unchanged.</p>
            <label for="reset-reason" class="form-label">Review reset reason</label><textarea id="reset-reason" name="reason" class="form-control mb-3" rows="4" required></textarea><div><button class="btn btn-outline-warning">Confirm review reset</button></div>
        </form>
    <?php endif; ?>
    <?php $historyBase='admin/treatments/'.$protocol['id'].'/operations'; require __DIR__.'/_history.php'; ?>
</div>

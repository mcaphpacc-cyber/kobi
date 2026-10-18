<?php $liveSources=array_column($sources,null,'id'); ?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h3">Review protocol #<?= (int)$protocol['id'] ?></h1><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/review')) ?>">Review queue</a></div>
    <p><?= e($identity['disease_en']??'Disease') ?> — <?= e($identity['system_name']??'Treatment system') ?></p>
    <p><strong>Review: <?= e($protocol['review_state']) ?> · Publication: <?= e($protocol['publication_status']) ?></strong> · Current revision: <?= (int)$protocol['content_revision'] ?></p>
    <div class="alert alert-info">Approval records that a KOBI governance revision passed review. It does not publish content or claim that a treatment is clinically proven. Public treatment display remains held in this phase.</div>
    <p><strong>Your recorded qualification scope:</strong> <?= e($qualification) ?>. Review only content within that scope.</p>
    <?php if ($submission): ?><p>Submitted event #<?= (int)$submission['id'] ?> · Revision <?= (int)$submission['content_revision'] ?> · <?= e($submission['occurred_at']) ?> UTC · Languages <?= e($submission['locales']) ?></p><?php endif; ?>
    <p>Canonical current content / submitted snapshot: <strong><?= $assessment['matches'] ? 'Match' : 'No confirmed match' ?></strong>. Current source status is validated separately and again during approval.</p>
    <?php if ($protocol['review_state']==='pending_review' && $protocol['publication_status']==='unpublished' && $assessment['issues']): ?><div class="alert alert-warning" role="alert"><strong>Approval prerequisites need attention:</strong><ul class="mb-0"><?php foreach ($assessment['issues'] as $issue): ?><li><?= e($issue) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <h2 class="h4">Current governed content</h2>
    <p><strong>Risk eligibility for public publication:</strong> <?= e($protocol['risk_eligibility']) ?>. This decision is separate from evidence classification and does not mean safe, effective, risk-free or recommended.</p>
    <?php if ($protocol['publication_status']==='unpublished'): ?>
        <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/risk-eligibility')) ?>" class="card card-body mb-3">
            <?= csrfField() ?><input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>">
            <h3 class="h5">Record risk eligibility decision</h3>
            <p>A changed decision creates a fresh revision and clears approval. The editor must submit it again before approval. No decision publishes content.</p>
            <label for="risk-decision" class="form-label">Risk eligibility under current KOBI public-risk policy</label>
            <select id="risk-decision" name="risk_eligibility" class="form-select mb-3" required><option value="">Choose explicitly</option><option value="eligible">Eligible under current KOBI public-risk policy</option><option value="not_eligible">Not eligible under current policy</option><option value="not_assessed">Not assessed</option></select>
            <label for="risk-rationale" class="form-label">Internal rationale</label><textarea id="risk-rationale" name="rationale" class="form-control mb-3" required></textarea>
            <div class="form-check mb-3"><input id="risk-independence" name="independence_confirmed" value="1" type="checkbox" class="form-check-input" required><label for="risk-independence" class="form-check-label">I confirm independent assessment within my recorded qualification scope.</label></div>
            <button class="btn btn-outline-primary">Record decision — require fresh submission if changed</button>
        </form>
    <?php endif; ?>
    <?php $reviewContent=['protocol'=>$protocol,'items'=>$items,'sources'=>$sources,'protocol_sources'=>$protocol_sources,'item_sources'=>$item_sources]; require __DIR__.'/_content.php'; ?>
    <?php if ($assessment['submittedContent']!==null): ?><details class="mb-4"><summary>Inspect the exact submitted governed snapshot (not raw JSON)</summary><div class="mt-3"><?php $reviewContent=$assessment['submittedContent']; require __DIR__.'/_content.php'; ?></div></details><?php endif; ?>
    <?php if ($protocol['review_state']==='pending_review' && $protocol['publication_status']==='unpublished' && $submission): ?>
        <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/approve')) ?>" class="card card-body mb-3">
            <?= csrfField() ?><input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="pending_review"><input type="hidden" name="submission_id" value="<?= (int)$submission['id'] ?>">
            <fieldset <?= !$assessment['canApprove'] ? 'disabled' : '' ?>><legend class="h5">Approve the saved submitted revision</legend>
                <p>Assess the recorded evidence classification explicitly. If it is incorrect, request changes; approval never rewrites classifications or verifies sources.</p>
                <label for="approval-risk" class="form-label">Confirm the saved risk decision for this revision</label><select id="approval-risk" name="risk_eligibility" class="form-select mb-3" required><option value="">Choose explicitly</option><option value="eligible">Eligible under current KOBI public-risk policy</option></select>
                <p class="small text-muted">Eligibility is separate from effectiveness and recommendation. A different decision must first be recorded above and submitted as a fresh revision.</p>
                <?php foreach ($assessment['locales'] as $locale): ?><div class="form-check"><input type="checkbox" class="form-check-input" id="assessed-<?= e($locale) ?>" name="locales[]" value="<?= e($locale) ?>"><label class="form-check-label" for="assessed-<?= e($locale) ?>">I assessed <?= $locale==='en' ? 'English' : 'Hindi' ?> content</label></div><?php endforeach; ?>
                <label class="form-label mt-3" for="approval-rationale">Reviewer rationale</label><textarea class="form-control mb-3" id="approval-rationale" name="rationale" rows="4" required></textarea>
                <label class="form-label" for="review-due">Review due date and time (UTC)</label><input type="datetime-local" class="form-control mb-2" id="review-due" name="review_due_at" required><p class="small text-muted">Choose a future UTC timestamp appropriate to this content. No universal maximum review interval has been approved.</p>
                <div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="independence" name="independence_confirmed" value="1" required><label class="form-check-label" for="independence">I confirm independent review, including independence from unknown legacy authorship. Recorded material authors cannot approve their own work.</label></div>
                <button class="btn btn-primary">Approve revision — keep unpublished</button>
            </fieldset>
        </form>
        <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/changes-required')) ?>" class="card card-body mb-4">
            <?= csrfField() ?><input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>"><input type="hidden" name="expected_state" value="pending_review"><input type="hidden" name="submission_id" value="<?= (int)$submission['id'] ?>">
            <h2 class="h5">Request changes</h2><label class="form-label" for="changes-rationale">Required corrections and rationale</label><textarea id="changes-rationale" class="form-control mb-3" name="rationale" rows="4" required></textarea><p>Editors make the actual corrections, which create a new revision requiring fresh submission and review.</p><div><button class="btn btn-outline-warning">Record changes required</button></div>
        </form>
    <?php endif; ?>
    <p><a href="<?= e(url('admin/treatments/'.$protocol['id'].'/operations')) ?>">Operations and history</a></p>
    <?php $historyBase='admin/treatments/'.$protocol['id'].'/review'; require dirname(__DIR__).'/treatments/_history.php'; ?>
</div>

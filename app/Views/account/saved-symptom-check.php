<?php $snapshot = $check['snapshot']; ?>
<div class="container py-4">
    <h1 class="h2">KOBI symptom-check result — historical</h1>
    <?php if (isset($_GET['saved'])): ?><p class="alert alert-success" role="status">Symptom check saved.</p><?php endif; ?>
    <p>Checked on <?= e($check['checked_at']) ?> UTC · Saved on <?= e($check['saved_at']) ?> UTC</p>
    <p class="alert alert-info"><?= e($snapshot['disclaimer']) ?> Results are ranked by symptom overlap and are not a diagnosis.</p>
    <p class="text-muted">These are the symptoms and matching results from that check. They have not been refreshed against current information.</p>
    <a class="btn btn-outline-primary mb-3" href="<?= e(url('/account/saved-symptom-checks')) ?>">Back to saved checks</a>
    <h2 class="h5">Selected symptoms</h2>
    <ul><?php foreach ($snapshot['selected'] as $symptom): ?><li class="text-break"><?= e($symptom['label']) ?></li><?php endforeach; ?></ul>
    <p>Showing <?= (int) $snapshot['summary']['displayed_matches'] ?> of <?= (int) $snapshot['summary']['total_matches'] ?> matching conditions from that check.</p>
    <?php if ($snapshot['results'] === []): ?><p>No matching results were returned for this check.</p><?php endif; ?>
    <?php foreach ($snapshot['results'] as $index => $item): ?>
        <section class="card mb-3 shadow-sm"><div class="card-body text-break">
            <h2 class="h5"><?= $index + 1 ?>. <?= e($item['label']) ?></h2>
            <p><strong>Symptom Match: <?= (int) $item['display_percent'] ?>% match</strong> · <?= e($item['match_level']) ?></p>
            <p>Selected Symptom Match: <?= e((string) $item['user_match_score']) ?>% · Disease Symptom Coverage: <?= e((string) $item['coverage']) ?>%</p>
            <h3 class="h6">Symptom Match Reason</h3>
            <ul><?php foreach ($item['reason'] as $reason): ?><li><?= e($reason) ?></li><?php endforeach; ?></ul>
            <h3 class="h6">Why this matched</h3>
            <ul><?php foreach ($item['explanation'] as $reason): ?><li><?= e($reason) ?></li><?php endforeach; ?></ul>
        </div></section>
    <?php endforeach; ?>
    <form method="POST" action="<?= e(url('/account/saved-symptom-checks/' . (int) $check['id'] . '/delete')) ?>">
        <?= csrfField() ?>
        <label class="form-check"><input class="form-check-input" name="confirm_delete" type="checkbox" value="1" required> <span class="form-check-label">Permanently delete this saved check</span></label>
        <button class="btn btn-outline-danger mt-2" type="submit">Delete saved check</button>
    </form>
</div>

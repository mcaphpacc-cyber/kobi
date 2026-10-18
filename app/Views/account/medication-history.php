<div class="container py-4">
<?php require __DIR__.'/medication-schedule-nav.php'; ?>
<h1 class="h3">Medication History</h1><p class="text-muted text-break"><?= e($profile['full_name']) ?> · Only explicit Taken/Skipped actions are recorded. Same-day corrections show the latest state.</p>
<?php if (!$listing['rows']): ?><div class="alert alert-info">No recorded medication history.</div><?php endif; ?>
<?php foreach ($listing['rows'] as $entry): ?>
<div class="card mb-3"><div class="card-body text-break">
    <h2 class="h5"><?= e($entry['medicine_label']) ?></h2>
    <p class="mb-1"><?= e($entry['scheduled_on']) ?> at <?= e($entry['local_time']) ?> · <?= e($entry['timezone']) ?> · <strong><?= e(ucfirst($entry['state'])) ?></strong></p>
    <div class="small text-muted">Last recorded <?= e((new DateTimeImmutable($entry['recorded_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($entry['timezone']))->format('Y-m-d H:i')) ?> · <?= $entry['recorded_by']===null?'Former account':'Account '.(int)$entry['recorded_by'] ?></div>
</div></div>
<?php endforeach; ?>
<nav class="d-flex flex-wrap gap-2" aria-label="History pages">
<?php if ($listing['page']>1): ?><a class="btn btn-outline-secondary" href="<?= e(url($medicineBase.'/history?page='.($listing['page']-1))) ?>">Previous</a><?php endif; ?>
<?php if ($listing['has_next']): ?><a class="btn btn-outline-secondary" href="<?= e(url($medicineBase.'/history?page='.($listing['page']+1))) ?>">Next</a><?php endif; ?>
</nav></div>

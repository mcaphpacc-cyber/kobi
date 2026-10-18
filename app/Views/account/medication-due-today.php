<div class="container py-4">
<?php require __DIR__.'/medication-schedule-nav.php'; ?>
<h1 class="h3">Due Today</h1><p class="text-muted text-break"><?= e($profile['full_name']) ?> · Daily entries use each medication's displayed timezone. Later times today are included. Unmarked does not mean missed.</p>
<?php if (isset($_GET['recorded'])): ?><div class="alert alert-success" role="status">Your action was recorded.</div><?php endif; ?>
<?php if (!$occurrences): ?><div class="alert alert-info">No medication due today. Add a schedule to a current medication if you want daily entries.</div><?php endif; ?>
<?php foreach ($occurrences as $entry): ?>
<div class="card mb-3"><div class="card-body">
    <h2 class="h5 text-break"><?= e($entry['brand_name']) ?></h2>
    <p class="text-break"><strong><?= e($entry['local_time']) ?></strong> · <?= e($entry['scheduled_on']) ?> · <?= e($entry['timezone']) ?><br>State: <strong><?= e($entry['state']?ucfirst($entry['state']):'Unmarked') ?></strong></p>
    <div class="d-flex flex-wrap gap-2">
    <?php if ($canEdit): ?><form class="d-flex flex-wrap gap-2" method="post" action="<?= e(url($medicineBase.'/'.$entry['health_medicine_id'].'/schedule/'.$entry['id'].'/mark')) ?>">
        <?= csrfField() ?><input type="hidden" name="scheduled_on" value="<?= e($entry['scheduled_on']) ?>">
        <button class="btn btn-outline-success" name="state" value="taken">Taken</button><button class="btn btn-outline-secondary" name="state" value="skipped">Skipped</button>
    </form><?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= e(url($medicineBase.'/'.$entry['health_medicine_id'].'/schedule')) ?>">Schedule</a></div>
</div></div>
<?php endforeach; ?>
</div>

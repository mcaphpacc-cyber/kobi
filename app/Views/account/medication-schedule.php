<div class="container py-4">
<?php require __DIR__.'/medication-schedule-nav.php'; ?>
<h1 class="h3 text-break">Schedule: <?= e($medicine['brand_name']) ?></h1>
<p class="text-muted">Daily times you record for <?= e($profile['full_name']) ?>. This feature does not recommend when or how much medication to take.</p>
<?php if ($error): ?><div class="alert alert-danger text-break" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success" role="status">Schedule saved.</div><?php endif; ?>
<?php if (isset($_GET['removed'])): ?><div class="alert alert-success" role="status">Schedule removed from Due Today. Recorded history is retained.</div><?php endif; ?>
<?php if (!$slots): ?><p class="alert alert-info">No schedule. This medication can still be used without scheduled times.</p><?php endif; ?>
<?php foreach ($slots as $entry): ?>
<div class="card mb-3"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <div class="text-break"><strong><?= e($entry['local_time']) ?></strong> daily · <?= e($entry['timezone']) ?><br>
            <?= e($entry['starts_on']) ?><?= $entry['ends_on']?' through '.e($entry['ends_on']):' onward' ?> · <?= (int)$entry['is_active']===1?'Active':'Inactive / removed' ?></div>
        <?php if ($canEdit): ?><div class="d-flex flex-wrap gap-2 align-items-start">
            <?php if ($medicine['status']==='current'): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url($medicineBase.'/'.$medicine['id'].'/schedule/'.$entry['id'].'/edit')) ?>"><?= (int)$entry['is_active']===1?'Edit':'Set up replacement' ?></a><?php endif; ?>
            <?php if ((int)$entry['is_active']===1): ?>
            <form method="post" action="<?= e(url($medicineBase.'/'.$medicine['id'].'/schedule/'.$entry['id'].'/remove')) ?>">
                <?= csrfField() ?><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="confirm_remove" value="1" required> <span class="form-check-label">Confirm removal</span></label>
                <button class="btn btn-sm btn-outline-danger">Remove / deactivate</button>
            </form><?php endif; ?>
        </div><?php endif; ?>
    </div>
</div></div>
<?php endforeach; ?>
<?php if ($canEdit && $medicine['status']==='current'):
    $zone=$slot['timezone']??date_default_timezone_get();
    $today=(new DateTimeImmutable('now',new DateTimeZone($zone)))->format('Y-m-d');
    $values=['local_time'=>$slot['local_time']??'','timezone'=>$zone,'starts_on'=>max($today,$slot['starts_on']??$today),'ends_on'=>$slot['ends_on']??'','is_active'=>'1'];
    foreach ($form as $key=>$value) { if (is_string($value)) { $values[$key]=$value; } }
?>
<div class="card"><div class="card-body">
<h2 class="h5"><?= $slot?'Replace this scheduled time':'Add a daily time' ?></h2>
<?php if ($slot): ?><p class="text-muted">Saving retires the old slot and preserves its recorded history. The replacement starts today or later.</p><?php endif; ?>
<form method="post" action="<?= e(url($medicineBase.'/'.$medicine['id'].'/schedule'.($slot?'/'.$slot['id'].'/update':''))) ?>">
<?= csrfField() ?>
<div class="row g-3">
    <div class="col-12 col-md-4"><label class="form-label" for="local_time">Daily time</label><input class="form-control" type="time" id="local_time" name="local_time" value="<?= e($values['local_time']) ?>" required></div>
    <div class="col-12 col-md-8"><label class="form-label" for="timezone">Timezone</label><input class="form-control" id="timezone" name="timezone" list="schedule-timezones" maxlength="64" value="<?= e($values['timezone']) ?>" required aria-describedby="timezone-help"><div class="form-text" id="timezone-help">Confirm the timezone for these daily times. All active times for this medication use the same timezone.</div><datalist id="schedule-timezones"><?php foreach (DateTimeZone::listIdentifiers() as $name): ?><option value="<?= e($name) ?>"></option><?php endforeach; ?></datalist></div>
    <div class="col-12 col-md-4"><label class="form-label" for="starts_on">Starts on</label><input class="form-control" type="date" id="starts_on" name="starts_on" value="<?= e($values['starts_on']) ?>" required></div>
    <div class="col-12 col-md-4"><label class="form-label" for="ends_on">Ends on (optional, inclusive)</label><input class="form-control" type="date" id="ends_on" name="ends_on" value="<?= e($values['ends_on']) ?>"></div>
    <div class="col-12 col-md-4"><label class="form-label" for="is_active">Schedule status</label><select class="form-select" id="is_active" name="is_active"><option value="1" <?= $values['is_active']==='1'?'selected':'' ?>>Active</option><option value="0" <?= $values['is_active']==='0'?'selected':'' ?>>Inactive</option></select></div>
</div>
<div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary">Save schedule</button><a class="btn btn-outline-secondary" href="<?= e(url($medicineBase.'/'.$medicine['id'].'/schedule')) ?>">Cancel</a></div>
</form></div></div>
<?php elseif ($medicine['status']!=='current'): ?><p class="alert alert-info">Stopped or historical medicines are excluded from Due Today. Returning a medicine to current does not restore old schedules.</p><?php endif; ?>
</div>

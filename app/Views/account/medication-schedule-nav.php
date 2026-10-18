<?php
$medicineBase='/account/health-records/'.(int)$profile['id'].'/medicines';
$canEdit=in_array($profile['role']??null,['owner','editor'],true);
?>
<nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Personal Medication navigation">
    <a class="btn btn-outline-secondary" href="<?= e(url('/account')) ?>">Account</a>
    <a class="btn btn-outline-secondary" href="<?= e(url($medicineBase)) ?>">Personal Medications</a>
    <a class="btn btn-outline-primary" href="<?= e(url($medicineBase.'/due-today')) ?>">Due Today</a>
    <a class="btn btn-outline-secondary" href="<?= e(url($medicineBase.'/history')) ?>">History</a>
</nav>

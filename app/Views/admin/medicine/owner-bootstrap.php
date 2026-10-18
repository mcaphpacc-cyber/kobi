<div class="container py-4">
    <h1 class="h3">Medicine owner setup</h1>
    <?php if($success): ?>
        <div class="alert alert-success" role="status">Medicine Administrator authority was established.</div>
    <?php endif; ?>
    <p class="fw-semibold">Account 1 — KOBI Owner — Medicine Administrator — No Expiry</p>
    <p>This one-time action establishes only the designated owner's Medicine Administrator authority. It does not grant other roles or activate Medicine access.</p>
    <?php if($status['initialized']): ?>
        <div class="alert alert-info" role="status">Medicine authority has already been established. Owner setup is closed.</div>
    <?php elseif(!$status['schema_ready']): ?>
        <div class="alert alert-warning" role="status">Owner setup is not ready. Controlled setup must be completed before this action is available.</div>
    <?php else: ?>
        <form method="post" action="<?= htmlspecialchars(url('/admin/medicine/owner-bootstrap'),ENT_QUOTES,'UTF-8') ?>">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-primary" name="confirm" value="establish_owner_administrator">Confirm owner administrator setup</button>
        </form>
    <?php endif; ?>
</div>

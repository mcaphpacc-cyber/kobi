<div class="mb-4">
    <div class="small text-muted mb-2">
        KOBI
        <span class="mx-1">/</span>
        Health Profile Invitation
    </div>

    <h1 class="h3 mb-1">
        Health Profile Invitation
    </h1>
</div>

<div class="alert alert-danger">
    <?= htmlspecialchars(
        $message
        ?? 'This invitation is not available.'
    ) ?>
</div>

<a
    href="<?= url('/') ?>"
    class="btn btn-outline-secondary"
>
    Return to KOBI
</a>
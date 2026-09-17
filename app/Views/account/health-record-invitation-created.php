<?php
$profileId =
    (int) ($profile['id'] ?? 0);

$invitationUrl =
    url(
        '/shared/health-profile-invitation/' .
        $token
    );
?>

<div class="mb-4">
    <a
        href="<?= url(
            '/account/health-records/' .
            $profileId .
            '/access'
        ) ?>"
        class="btn btn-outline-secondary mb-3"
    >
        ← Back to Manage Access
    </a>

    <div class="small text-muted mb-2">
        Health Records
        <span class="mx-1">/</span>
        <?= htmlspecialchars(
            $profile['full_name'] ?? ''
        ) ?>
        <span class="mx-1">/</span>
        Manage Access
    </div>

    <h1 class="h3 mb-1">
        Invitation Created
    </h1>

    <p class="text-muted mb-0">
        The invitation has been created successfully.
    </p>
</div>

<div class="alert alert-success">
    <div class="fw-semibold mb-1">
        Invitation Ready
    </div>

    <div>
        Share the invitation link with the invited person.
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">
            Invitation Link
        </h2>

        <div class="mb-3">
            <label
                for="invitation-url"
                class="form-label"
            >
                Secure invitation URL
            </label>

            <input
                type="text"
                id="invitation-url"
                class="form-control"
                value="<?= htmlspecialchars(
                    $invitationUrl
                ) ?>"
                readonly
            >
        </div>

        <div class="small text-muted mb-3">
            This link contains a secure, single-use invitation
            token. Share it only with the intended recipient.
        </div>

        <button
            type="button"
            class="btn btn-primary"
            onclick="
                navigator.clipboard.writeText(
                    document.getElementById(
                        'invitation-url'
                    ).value
                );
            "
        >
            Copy Invitation Link
        </button>

        <a
            href="<?= url(
                '/account/health-records/' .
                $profileId .
                '/access'
            ) ?>"
            class="btn btn-outline-secondary ms-2"
        >
            Manage Access
        </a>
    </div>
</div>
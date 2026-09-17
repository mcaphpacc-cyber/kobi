<div class="mb-4">
    <div class="small text-muted mb-2">
        KOBI
        <span class="mx-1">/</span>
        Health Profile Invitation
    </div>

    <h1 class="h3 mb-1">
        Health Profile Invitation
    </h1>

    <p class="text-muted mb-0">
        You have been invited to access a KOBI health record.
    </p>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <div class="mb-4">
            <div class="small text-muted">
                Health Record
            </div>

            <div class="fs-4 fw-semibold">
                <?= htmlspecialchars(
                    $invitation['profile_name'] ?? ''
                ) ?>
            </div>
        </div>

        <div class="row g-3 mb-4">

            <div class="col-md-6">
                <div class="border rounded p-3">
                    <div class="small text-muted mb-1">
                        Your Email
                    </div>

                    <div class="fw-semibold">
                        <?= htmlspecialchars(
                            $invitation['email'] ?? ''
                        ) ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="border rounded p-3">
                    <div class="small text-muted mb-1">
                        Access Level
                    </div>

                    <div class="fw-semibold">
                        <?= htmlspecialchars(
                            ucfirst(
                                $invitation['role'] ?? 'viewer'
                            )
                        ) ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="alert alert-info">
            By accepting this invitation, this KOBI account
            will be granted access to the health record according
            to the assigned access level.
        </div>

        <form
            method="post"
            action="<?= url(
                '/shared/health-profile-invitation/' .
                urlencode($token) .
                '/accept'
            ) ?>"
        >
            <?= csrfField() ?>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Accept Invitation
            </button>

            <a
                href="<?= url('/') ?>"
                class="btn btn-outline-secondary ms-2"
            >
                Cancel
            </a>
        </form>

    </div>
</div>
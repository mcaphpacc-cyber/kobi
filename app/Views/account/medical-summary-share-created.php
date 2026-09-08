<?php

$profile =
    is_array($profile ?? null)
        ? $profile
        : [];

$share =
    is_array($share ?? null)
        ? $share
        : [];

$profileId =
    (int) ($profile['id'] ?? 0);

$profileName =
    trim(
        (string) (
            $profile['full_name']
            ?? 'Health Profile'
        )
    );

$shareUrl =
    trim(
        (string) (
            $shareUrl
            ?? ''
        )
    );

$expiresAt =
    trim(
        (string) (
            $share['expires_at']
            ?? ''
        )
    );

$formattedExpiry = '';

if ($expiresAt !== '') {

    $timestamp =
        strtotime($expiresAt);

    if ($timestamp !== false) {

        $formattedExpiry =
            date(
                'd M Y, h:i A',
                $timestamp
            );
    }
}

?>

<div class="container py-4">

    <!-- Header -->
    <div
        class="d-flex
               justify-content-between
               align-items-center
               flex-wrap
               gap-2
               mb-4"
    >

        <div>

            <div
                class="small
                       text-uppercase
                       text-muted
                       fw-semibold
                       mb-1"
            >
                KOBI Health Record
            </div>

            <h1 class="h3 mb-1">
                Medical Summary Share Created
            </h1>

            <div class="text-muted">
                Your secure, read-only share link is ready.
            </div>

        </div>

        <a
            href="<?= url(
                '/account/health-records/' .
                $profileId .
                '/medical-summary'
            ) ?>"
            class="btn btn-outline-secondary btn-sm"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Medical Summary
        </a>

    </div>


    <!-- Success -->
    <div class="row justify-content-center">

        <div class="col-12 col-md-9 col-lg-7">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <div
                        class="text-center
                               mb-4"
                    >

                        <div
                            class="medical-share-success-icon
                                   mb-3"
                        >
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <h2 class="h5 mb-2">
                            Share Link Created
                        </h2>

                        <p
                            class="text-muted
                                   mb-0"
                        >
                            A read-only Medical Summary
                            snapshot has been created for
                            <strong>
                                <?= e($profileName) ?>
                            </strong>.
                        </p>

                    </div>


                    <!-- Expiry -->
                    <?php if ($formattedExpiry !== ''): ?>

                        <div
                            class="medical-share-info
                                   mb-4"
                        >

                            <div
                                class="medical-share-label"
                            >
                                Expires
                            </div>

                            <div class="fw-semibold">
                                <?= e(
                                    $formattedExpiry
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- Share URL -->
                    <div class="mb-4">

                        <label
                            for="medical-summary-share-url"
                            class="form-label fw-semibold"
                        >
                            Share Link
                        </label>

                        <div class="input-group">

                            <input
                                type="text"
                                id="medical-summary-share-url"
                                class="form-control"
                                value="<?= e($shareUrl) ?>"
                                readonly
                            >

                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                id="copy-medical-summary-share"
                            >
                                <i class="bi bi-copy"></i>
                                Copy
                            </button>

                        </div>

                        <div
                            class="form-text"
                            id="medical-summary-copy-status"
                        >
                            Copy and save this link now.
                            KOBI cannot display this link
                            again after you leave this page.
                        </div>

                    </div>


                    <!-- Important -->
                    <div
                        class="alert
                               alert-warning
                               small
                               mb-4"
                    >

                        <div
                            class="fw-semibold
                                   mb-1"
                        >
                            Keep this link private
                        </div>

                        Anyone who has this link may be able
                        to view the shared Medical Summary
                        until it expires or is revoked.

                    </div>


                    <!-- Snapshot -->
                    <div
                        class="alert
                               alert-info
                               small
                               mb-4"
                    >

                        <div
                            class="fw-semibold
                                   mb-1"
                        >
                            This is a snapshot
                        </div>

                        The shared summary contains the
                        medical information recorded when
                        this link was created.

                        Later changes to the health record
                        will not change this shared summary.

                    </div>


                    <!-- Actions -->
                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center
                               flex-wrap
                               gap-2"
                    >

                        <a
                            href="<?= url(
                                '/account/health-records/' .
                                $profileId .
                                '/medical-summary/shares'
                            ) ?>"
                            class="btn btn-outline-secondary"
                        >
                            <i class="bi bi-link-45deg"></i>
                            Manage Shares
                        </a>

                        <a
                            href="<?= url(
                                '/account/health-records/' .
                                $profileId .
                                '/medical-summary'
                            ) ?>"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-file-medical"></i>
                            Medical Summary
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function ()
    {
        const copyButton =
            document.getElementById(
                'copy-medical-summary-share'
            );

        const shareInput =
            document.getElementById(
                'medical-summary-share-url'
            );

        const copyStatus =
            document.getElementById(
                'medical-summary-copy-status'
            );

        if (
            !copyButton ||
            !shareInput ||
            !copyStatus
        ) {
            return;
        }

        copyButton.addEventListener(
            'click',
            async function ()
            {
                try
                {
                    await navigator.clipboard.writeText(
                        shareInput.value
                    );

                    copyStatus.textContent =
                        'Share link copied to your clipboard.';

                    copyButton.innerHTML =
                        '<i class="bi bi-check-lg"></i> Copied';

                    setTimeout(
                        function ()
                        {
                            copyButton.innerHTML =
                                '<i class="bi bi-copy"></i> Copy';
                        },
                        2000
                    );
                }
                catch (error)
                {
                    shareInput.focus();
                    shareInput.select();

                    copyStatus.textContent =
                        'Copy failed. Please copy the link manually.';
                }
            }
        );
    }
);
</script>


<style>
.medical-share-success-icon
{
    width: 3rem;
    height: 3rem;
    margin-left: auto;
    margin-right: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--bs-success-bg-subtle);
    color: var(--bs-success-text-emphasis);
    font-size: 1.5rem;
}

.medical-share-label
{
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.medical-share-info
{
    padding: 0.9rem;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
}
</style>
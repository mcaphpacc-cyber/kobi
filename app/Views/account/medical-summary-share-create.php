<?php

$profile =
    is_array($profile ?? null)
        ? $profile
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

$error =
    trim(
        (string) (
            $error
            ?? ''
        )
    );

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
                Share Medical Summary
            </h1>

            <div class="text-muted">
                Create a secure, read-only link to this
                medical summary.
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


    <?php if ($error !== ''): ?>

        <div
            class="alert alert-danger"
            role="alert"
        >
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!-- Share Form -->
    <div class="row justify-content-center">

        <div class="col-12 col-md-8 col-lg-6">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <!-- Profile -->
                    <div class="mb-4">

                        <div class="medical-share-label">
                            Health Profile
                        </div>

                        <div class="fw-semibold">
                            <?= e($profileName) ?>
                        </div>

                    </div>


                    <!-- Scope -->
                    <div class="mb-4">

                        <div class="medical-share-label">
                            What will be shared
                        </div>

                        <div class="small text-muted">

                            Medical Summary only

                        </div>

                        <div
                            class="small
                                   text-muted
                                   mt-1"
                        >
                            Reports, documents and insurance
                            information are not included.

                        </div>

                    </div>


                    <!-- Expiry -->
                    <form
                        method="post"
                        action="<?= url(
                            '/account/health-records/' .
                            $profileId .
                            '/medical-summary/share'
                        ) ?>"
                    >

                        <?= csrfField() ?>


                        <div class="mb-4">

                            <label
                                for="expires_in_days"
                                class="form-label fw-semibold"
                            >
                                Link Expiry
                            </label>

                            <select
                                id="expires_in_days"
                                name="expires_in_days"
                                class="form-select"
                                required
                            >

                                <option value="1">
                                    1 day
                                </option>

                                <option
                                    value="7"
                                    selected
                                >
                                    7 days
                                </option>

                                <option value="14">
                                    14 days
                                </option>

                                <option value="30">
                                    30 days
                                </option>

                            </select>

                            <div
                                class="form-text"
                            >
                                The link will stop working
                                automatically after this period.
                            </div>

                        </div>


                        <!-- Snapshot Notice -->
                        <div
                            class="alert
                                   alert-info
                                   small"
                        >

                            <div
                                class="fw-semibold
                                       mb-1"
                            >
                                Important
                            </div>

                            This shared summary is a snapshot
                            of the medical information recorded
                            when the link is created.

                            Changes made to the health record
                            later will not update an existing
                            shared link.

                        </div>


                        <!-- Security Notice -->
                        <div
                            class="alert
                                   alert-warning
                                   small"
                        >

                            <div
                                class="fw-semibold
                                       mb-1"
                            >
                                Keep the link private
                            </div>

                            Anyone who has the share link may
                            be able to view the shared medical
                            summary until the link expires or
                            is revoked.

                        </div>


                        <div
                            class="d-flex
                                   justify-content-end
                                   gap-2"
                        >

                            <a
                                href="<?= url(
                                    '/account/health-records/' .
                                    $profileId .
                                    '/medical-summary'
                                ) ?>"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-share"></i>
                                Create Share Link
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<style>
.medical-share-label
{
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
</style>
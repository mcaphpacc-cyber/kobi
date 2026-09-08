<?php

$profile =
    is_array($profile ?? null)
        ? $profile
        : [];

$shares =
    is_array($shares ?? null)
        ? $shares
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


$formatDateTime =
    static function (?string $date): string {

        if (
            $date === null ||
            trim($date) === ''
        ) {
            return '';
        }

        $timestamp =
            strtotime($date);

        if ($timestamp === false) {
            return '';
        }

        return date(
            'd M Y, h:i A',
            $timestamp
        );
    };

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
                Medical Summary Shares
            </h1>

            <div class="text-muted">
                Manage shared Medical Summary links for
                <?= e($profileName) ?>.
            </div>

        </div>


        <div
            class="d-flex
                   flex-wrap
                   gap-2"
        >

            <a
                href="<?= url(
                    '/account/health-records/' .
                    $profileId .
                    '/medical-summary'
                ) ?>"
                class="btn btn-outline-secondary btn-sm"
            >
                <i class="bi bi-arrow-left"></i>
                Medical Summary
            </a>

            <a
                href="<?= url(
                    '/account/health-records/' .
                    $profileId .
                    '/medical-summary/share'
                ) ?>"
                class="btn btn-primary btn-sm"
            >
                <i class="bi bi-plus-lg"></i>
                Create New Share
            </a>

        </div>

    </div>


    <!-- Security Notice -->
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
            About Medical Summary shares
        </div>

        Shared Medical Summaries are read-only snapshots.
        Each link automatically expires according to its
        expiry date and can be revoked at any time.

        For security, KOBI does not store the original
        share link and cannot display it again after it
        has been created.

    </div>


    <?php if (empty($shares)): ?>

        <!-- Empty State -->
        <div
            class="card
                   shadow-sm"
        >

            <div
                class="card-body
                       text-center
                       py-5"
            >

                <div
                    class="text-muted
                           mb-3"
                >
                    <i
                        class="bi
                               bi-link-45deg"
                        style="font-size: 2.5rem;"
                    ></i>
                </div>

                <h2 class="h5">
                    No Medical Summary shares
                </h2>

                <p class="text-muted mb-4">
                    You have not created any share links
                    for this health profile yet.
                </p>

                <a
                    href="<?= url(
                        '/account/health-records/' .
                        $profileId .
                        '/medical-summary/share'
                    ) ?>"
                    class="btn btn-primary"
                >
                    <i class="bi bi-share"></i>
                    Create Share Link
                </a>

            </div>

        </div>

    <?php else: ?>

        <!-- Shares -->
        <div class="card shadow-sm">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table
                        class="table
                               table-hover
                               align-middle
                               mb-0"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Created
                                </th>

                                <th>
                                    Expires
                                </th>

                                <th>
                                    Status
                                </th>

                                <th class="text-end">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $shares as $share
                            ): ?>

                                <?php

                                $isRevoked =
                                    ($share['status'] ?? '')
                                    === 'revoked';

                                $isExpired =
                                    !$isRevoked &&
                                    (
                                        ($share['is_expired']
                                        ?? false)
                                        === true
                                    );

                                $isActive =
                                    (
                                        $share['is_active']
                                        ?? false
                                    ) === true;

                                ?>

                                <tr>

                                    <!-- Created -->
                                    <td>

                                        <div class="small">
                                            <?= e(
                                                $formatDateTime(
                                                    $share[
                                                        'created_at'
                                                    ]
                                                    ?? null
                                                )
                                            ) ?>
                                        </div>

                                    </td>


                                    <!-- Expires -->
                                    <td>

                                        <div class="small">
                                            <?= e(
                                                $formatDateTime(
                                                    $share[
                                                        'expires_at'
                                                    ]
                                                    ?? null
                                                )
                                            ) ?>
                                        </div>

                                    </td>


                                    <!-- Status -->
                                    <td>

                                        <?php if (
                                            $isRevoked
                                        ): ?>

                                            <span
                                                class="badge
                                                       text-bg-secondary"
                                            >
                                                Revoked
                                            </span>

                                        <?php elseif (
                                            $isExpired
                                        ): ?>

                                            <span
                                                class="badge
                                                       text-bg-warning"
                                            >
                                                Expired
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="badge
                                                       text-bg-success"
                                            >
                                                Active
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Action -->
                                    <td class="text-end">

                                        <?php if (
                                            $isActive
                                        ): ?>

                                            <form
                                                method="post"
                                                action="<?= url(
                                                    '/account/health-records/' .
                                                    $profileId .
                                                    '/medical-summary/shares/' .
                                                    (int) (
                                                        $share['id']
                                                        ?? 0
                                                    ) .
                                                    '/revoke'
                                                ) ?>"
                                                class="d-inline"
                                                onsubmit="return confirm('Revoke this Medical Summary share? The link will stop working immediately.');"
                                            >

                                                <?= csrfField() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-danger btn-sm"
                                                >
                                                    <i class="bi bi-x-circle"></i>
                                                    Revoke
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span
                                                class="text-muted
                                                       small"
                                            >
                                                No action
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>
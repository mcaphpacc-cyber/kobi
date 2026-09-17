<?php
$profileId = (int) ($profile['id'] ?? 0);
?>

<div class="mb-4">
    <a
        href="<?= url(
            '/account/health-records/' .
            $profileId
        ) ?>"
        class="btn btn-outline-secondary mb-3"
    >
        ← Back to Health Record
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
        Manage Access
    </h1>

    <p class="text-muted mb-0">
        Manage who can access this health record.
    </p>
</div>
<?php if (!empty($error)): ?>

    <div class="alert alert-danger mb-4">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>
<div class="alert alert-info mb-4">
    <div class="fw-semibold mb-1">
        Health Record
    </div>

    <div class="fs-5">
        <?= htmlspecialchars(
            $profile['full_name'] ?? ''
        ) ?>
    </div>

    <div class="small text-muted mt-1">
        Access is shared at the health-profile level.
        Members can access the medical information allowed
        by their role.
    </div>
</div>

<!-- Invite Someone -->

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-1">
            Invite Someone
        </h2>

        <p class="text-muted mb-4">
            Invite another KOBI account to access this health
            record.
        </p>

        <form
            method="post"
            action="<?= url(
                '/account/health-records/' .
                $profileId .
                '/access/invite'
            ) ?>"
        >
            <?= csrfField() ?>

            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label
                        for="email"
                        class="form-label"
                    >
                        Email Address
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $formEmail ?? ''
                        ) ?>"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label
                        for="role"
                        class="form-label"
                    >
                        Access Level
                    </label>

                    <select
                        class="form-select"
                        id="role"
                        name="role"
                        required
                    >
                        <option
                            value="editor"
                            <?= ($formRole ?? 'viewer') === 'editor'
                                ? 'selected'
                                : '' ?>
                        >
                            Editor
                        </option>

                        <option
                            value="viewer"
                            <?= ($formRole ?? 'viewer') === 'viewer'
                                ? 'selected'
                                : '' ?>
                        >
                            Viewer
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label
                        for="expires_in_days"
                        class="form-label"
                    >
                        Invitation Expires
                    </label>

                    <select
                        class="form-select"
                        id="expires_in_days"
                        name="expires_in_days"
                    >
                        <option
                            value="7"
                            <?= ($formExpiry ?? '7') === '7'
                                ? 'selected'
                                : '' ?>
                        >
                            7 days
                        </option>

                        <option
                            value="3"
                            <?= ($formExpiry ?? '7') === '3'
                                ? 'selected'
                                : '' ?>
                        >
                            3 days
                        </option>

                        <option
                            value="14"
                            <?= ($formExpiry ?? '7') === '14'
                                ? 'selected'
                                : '' ?>
                        >
                            14 days
                        </option>

                        <option
                            value="30"
                            <?= ($formExpiry ?? '7') === '30'
                                ? 'selected'
                                : '' ?>
                        >
                            30 days
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Invitation
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Active Members -->

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-1">
            People with Access
        </h2>

        <p class="text-muted mb-4">
            People who currently have access to this health
            record.
        </p>

        <?php if (empty($activeMembers)): ?>

            <div class="text-muted">
                No active members found.
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>
                                Person
                            </th>

                            <th>
                                Access
                            </th>

                            <th>
                                Added
                            </th>

                            <th class="text-end">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $activeMembers
                            as $member
                        ): ?>

                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars(
                                            $member['name'] ?? ''
                                        ) ?>
                                    </div>

                                    <div class="small text-muted">
                                        <?= htmlspecialchars(
                                            $member['email'] ?? ''
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <?php
                                    $role =
                                        $member['role'] ?? 'viewer';
                                    ?>

                                    <?php if ($role === 'owner'): ?>

                                        <span class="fw-semibold">
                                            Owner
                                        </span>

                                    <?php else: ?>

                                        <form
                                            method="post"
                                            action="<?= url(
                                                '/account/health-records/' .
                                                $profileId .
                                                '/access/members/' .
                                                (int) $member['user_id'] .
                                                '/role'
                                            ) ?>"
                                            class="d-flex gap-2"
                                        >
                                            <?= csrfField() ?>

                                            <select
                                                name="role"
                                                class="form-select form-select-sm"
                                                style="max-width: 130px;"
                                            >
                                                <option
                                                    value="editor"
                                                    <?= $role === 'editor'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Editor
                                                </option>

                                                <option
                                                    value="viewer"
                                                    <?= $role === 'viewer'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Viewer
                                                </option>
                                            </select>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Save
                                            </button>
                                        </form>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="small">
                                        <?= htmlspecialchars(
                                            $member['created_at'] ?? ''
                                        ) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <?php if ($role === 'owner'): ?>

                                        <span
                                            class="badge text-bg-success"
                                        >
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <form
                                            method="post"
                                            action="<?= url(
                                                '/account/health-records/' .
                                                $profileId .
                                                '/access/members/' .
                                                (int) $member['user_id'] .
                                                '/revoke'
                                            ) ?>"
                                            class="d-inline"
                                            onsubmit="
                                                return confirm(
                                                    'Revoke this person\\'s access to this health record?'
                                                );
                                            "
                                        >
                                            <?= csrfField() ?>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Revoke Access
                                            </button>
                                        </form>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>


<!-- Pending Invitations -->

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-1">
            Pending Invitations
        </h2>

        <p class="text-muted mb-4">
            Invitations that have not yet been accepted.
        </p>

        <?php if (
            empty($pendingInvitations)
        ): ?>

            <div class="text-muted">
                No pending invitations.
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>
                                Email
                            </th>

                            <th>
                                Access
                            </th>

                            <th>
                                Expires
                            </th>

                            <th class="text-end">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $pendingInvitations
                            as $invitation
                        ): ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $invitation['email']
                                        ?? ''
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $invitation['role']
                                            ?? 'viewer'
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $invitation['expires_at']
                                        ?? ''
                                    ) ?>
                                </td>

                                <td class="text-end">
                                    <?php if (
                                        $invitation[
                                            'is_expired'
                                        ] ?? false
                                    ): ?>

                                        <span
                                            class="badge text-bg-secondary"
                                        >
                                            Expired
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge text-bg-warning"
                                        >
                                            Pending
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>


<!-- Revoked Members -->

<?php if (!empty($revokedMembers)): ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-1">
                Revoked Access
            </h2>

            <p class="text-muted mb-4">
                People who previously had access but no longer
                have access to this health record.
            </p>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>
                                Person
                            </th>

                            <th>
                                Previous Access
                            </th>

                            <th>
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $revokedMembers
                            as $member
                        ): ?>

                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars(
                                            $member['name'] ?? ''
                                        ) ?>
                                    </div>

                                    <div class="small text-muted">
                                        <?= htmlspecialchars(
                                            $member['email'] ?? ''
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $member['role']
                                            ?? 'viewer'
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <form
                                        method="post"
                                        action="<?= url(
                                            '/account/health-records/' .
                                            $profileId .
                                            '/access/members/' .
                                            (int) $member['user_id'] .
                                            '/reactivate'
                                        ) ?>"
                                        class="d-flex gap-2 justify-content-end"
                                    >
                                        <?= csrfField() ?>

                                        <select
                                            name="role"
                                            class="form-select form-select-sm"
                                            style="max-width: 120px;"
                                        >
                                            <option
                                                value="editor"
                                                <?= (
                                                    ($member['role'] ?? '')
                                                    === 'editor'
                                                )
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Editor
                                            </option>

                                            <option
                                                value="viewer"
                                                <?= (
                                                    ($member['role'] ?? '')
                                                    === 'viewer'
                                                )
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Viewer
                                            </option>
                                        </select>

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-success"
                                        >
                                            Restore Access
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>
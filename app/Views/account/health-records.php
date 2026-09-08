<div class="container py-4">

    <div class="mb-4">
        <h1 class="mb-1">
            My Health Records
        </h1>

        <p class="text-muted mb-0">
            Manage your personal and family health records.
        </p>
    </div>

    <div class="d-flex justify-content-end mb-4">

        <a
            href="<?= url('/account/health-records/create') ?>"
            class="btn btn-primary"
        >
            + Add Family Member
        </a>

    </div>

    <?php if (empty($profiles)): ?>

        <div class="alert alert-info">
            No health profiles are available yet.
        </div>

    <?php else: ?>

        <div class="row g-4">

            <?php foreach ($profiles as $profile): ?>

                <div class="col-12 col-md-6">

                    <div class="card h-100 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-start">

                                <div>

                                    <h4 class="mb-1">
                                        <?= htmlspecialchars(
                                            $profile['full_name']
                                        ) ?>
                                    </h4>

                                    <div class="text-muted">
                                        <?php
                                        echo $profile['profile_type'] === 'self'
                                            ? 'My Health Profile'
                                            : 'Family Health Profile';
                                        ?>
                                    </div>

                                </div>

                                <span class="badge text-bg-secondary">
                                    <?= htmlspecialchars(
                                        ucfirst($profile['role'])
                                    ) ?>
                                </span>

                            </div>

                            <hr>

                            <a
                                href="<?= url(
                                    '/account/health-records/' .
                                    (int) $profile['id']
                                ) ?>"
                                class="btn btn-primary"
                            >
                                Open Health Record
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>
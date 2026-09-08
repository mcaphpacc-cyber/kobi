<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <div class="text-muted small mb-1">
                Health Record
            </div>

            <h1 class="h3 mb-1">
                <?= e($profile['full_name']) ?>
            </h1>

            <div class="text-muted">
                Medical Conditions
            </div>
        </div>

        <?php if (
            in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ): ?>

            <a
                href="<?= url(
                    '/account/health-records/' .
                    $profile['id'] .
                    '/conditions/create'
                ) ?>"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Add Medical Condition
            </a>

        <?php endif; ?>

    </div>


    <?php if (empty($conditions)): ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <div class="mb-3">
                    <i class="bi bi-heart-pulse fs-1 text-muted"></i>
                </div>

                <h2 class="h5">
                    No medical conditions recorded
                </h2>

                <p class="text-muted mb-4">
                    Add important medical conditions and history
                    for this health profile.
                </p>

                <?php if (
                    in_array(
                        $profile['role'] ?? null,
                        ['owner', 'editor'],
                        true
                    )
                ): ?>

                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            $profile['id'] .
                            '/conditions/create'
                        ) ?>"
                        class="btn btn-primary"
                    >
                        Add Medical Condition
                    </a>

                <?php endif; ?>

            </div>

        </div>

    <?php else: ?>

        <div class="row g-3">

            <?php foreach ($conditions as $condition): ?>

                <div class="col-12">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex flex-wrap justify-content-between gap-3">

                                <div>

                                    <h2 class="h5 mb-2">
                                        <?= e(
                                            $condition['condition_name']
                                        ) ?>
                                    </h2>

                                    <?php
                                    $statusClass = match (
                                        $condition['status']
                                    ) {
                                        'active' => 'text-bg-danger',
                                        'chronic' => 'text-bg-warning',
                                        'resolved' => 'text-bg-success',
                                        'historical' => 'text-bg-secondary',
                                        default => 'text-bg-secondary'
                                    };
                                    ?>

                                    <span class="badge <?= $statusClass ?>">
                                        <?= e(
                                            ucfirst(
                                                $condition['status']
                                            )
                                        ) ?>
                                    </span>

                                </div>


                                <?php if (
                                    in_array(
                                        $profile['role'] ?? null,
                                        ['owner', 'editor'],
                                        true
                                    )
                                ): ?>

                                    <div class="d-flex gap-2">

                                        <a
                                            href="<?= url(
                                                '/account/health-records/' .
                                                $profile['id'] .
                                                '/conditions/' .
                                                $condition['id'] .
                                                '/edit'
                                            ) ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="post"
                                            action="<?= url(
                                                '/account/health-records/' .
                                                $profile['id'] .
                                                '/conditions/' .
                                                $condition['id'] .
                                                '/delete'
                                            ) ?>"
                                            onsubmit="return confirm(
                                                'Are you sure you want to delete this medical condition?'
                                            );"
                                        >

                                            <?= csrfField() ?>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="row g-3 mt-2">

                                <?php if (
                                    !empty($condition['diagnosed_on'])
                                ): ?>

                                    <div class="col-md-4">

                                        <div class="text-muted small">
                                            Diagnosed On
                                        </div>

                                        <div>
                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $condition['diagnosed_on']
                                                    )
                                                )
                                            ) ?>
                                        </div>

                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    !empty($condition['resolved_on'])
                                ): ?>

                                    <div class="col-md-4">

                                        <div class="text-muted small">
                                            Resolved On
                                        </div>

                                        <div>
                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $condition['resolved_on']
                                                    )
                                                )
                                            ) ?>
                                        </div>

                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    !empty($condition['doctor_hospital'])
                                ): ?>

                                    <div class="col-md-4">

                                        <div class="text-muted small">
                                            Doctor / Hospital
                                        </div>

                                        <div>
                                            <?= e(
                                                $condition['doctor_hospital']
                                            ) ?>
                                        </div>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <?php if (!empty($condition['notes'])): ?>

                                <div class="mt-3 pt-3 border-top">

                                    <div class="text-muted small mb-1">
                                        Notes
                                    </div>

                                    <div>
                                        <?= nl2br(
                                            e($condition['notes'])
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <div class="mt-4">

        <a
            href="<?= url(
                '/account/health-records/' .
                $profile['id']
            ) ?>"
            class="btn btn-outline-secondary"
        >
            ← Back to Health Record
        </a>

    </div>

</div>
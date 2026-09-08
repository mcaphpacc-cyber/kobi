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
                Current Medicines
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
                    '/medicines/create'
                ) ?>"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Add Medicine
            </a>

        <?php endif; ?>

    </div>


    <?php if (empty($medicines)): ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <div class="mb-3">
                    <i class="bi bi-capsule fs-1 text-muted"></i>
                </div>

                <h2 class="h5">
                    No medicines recorded
                </h2>

                <p class="text-muted mb-4">
                    Add current medicines and medication history
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
                            '/medicines/create'
                        ) ?>"
                        class="btn btn-primary"
                    >
                        Add Medicine
                    </a>

                <?php endif; ?>

            </div>

        </div>

    <?php else: ?>

        <?php
        $currentMedicines = [];
        $stoppedMedicines = [];
        $historicalMedicines = [];

        foreach ($medicines as $medicine) {

            switch ($medicine['status']) {
                case 'current':
                    $currentMedicines[] = $medicine;
                    break;

                case 'stopped':
                    $stoppedMedicines[] = $medicine;
                    break;

                case 'historical':
                    $historicalMedicines[] = $medicine;
                    break;
            }
        }
        ?>


        <?php if (!empty($currentMedicines)): ?>

            <div class="mb-4">

                <h2 class="h5 mb-3">
                    Current Medicines
                </h2>

                <div class="row g-3">

                    <?php foreach ($currentMedicines as $medicine): ?>

                        <div class="col-12">

                            <div class="card border-0 shadow-sm">

                                <div class="card-body">

                                    <div class="d-flex flex-wrap justify-content-between gap-3">

                                        <div>

                                            <h3 class="h5 mb-2">
                                                <?= e(
                                                    $medicine['brand_name']
                                                ) ?>
                                            </h3>

                                            <?php if (
                                                !empty(
                                                    $medicine['generic_name']
                                                )
                                            ): ?>

                                                <div class="text-muted">
                                                    <?= e(
                                                        $medicine['generic_name']
                                                    ) ?>
                                                </div>

                                            <?php endif; ?>

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
                                                        '/medicines/' .
                                                        $medicine['id'] .
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
                                                        '/medicines/' .
                                                        $medicine['id'] .
                                                        '/delete'
                                                    ) ?>"
                                                    class="d-inline"
                                                    onsubmit="return confirm(
                                                        'Are you sure you want to delete this medicine record?'
                                                    );"
                                                >

                                                    <?= csrfField() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                    >
                                                        <i class="bi bi-trash me-1"></i>
                                                        Delete
                                                    </button>

                                                </form>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <div class="row g-3 mt-2">

                                        <?php if (
                                            !empty($medicine['strength'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Strength
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['strength']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['dose'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Dose
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['dose']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['frequency'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Frequency
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['frequency']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['route'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Route
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['route']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['started_on'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Started On
                                                </div>

                                                <div>
                                                    <?= e(
                                                        date(
                                                            'd M Y',
                                                            strtotime(
                                                                $medicine['started_on']
                                                            )
                                                        )
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['prescribed_by'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Prescribed By
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['prescribed_by']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <?php if (!empty($medicine['notes'])): ?>

                                        <div class="mt-3 pt-3 border-top">

                                            <div class="text-muted small mb-1">
                                                Notes
                                            </div>

                                            <div>
                                                <?= nl2br(
                                                    e($medicine['notes'])
                                                ) ?>
                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>


        <?php if (!empty($stoppedMedicines)): ?>

            <div class="mb-4">

                <h2 class="h5 mb-3">
                    Stopped Medicines
                </h2>

                <div class="row g-3">

                    <?php foreach ($stoppedMedicines as $medicine): ?>

                        <div class="col-12">

                            <div class="card border-0 shadow-sm">

                                <div class="card-body">

                                    <div class="d-flex flex-wrap justify-content-between gap-3">

                                        <div>

                                            <h3 class="h5 mb-2">
                                                <?= e(
                                                    $medicine['brand_name']
                                                ) ?>
                                            </h3>

                                            <?php if (
                                                !empty(
                                                    $medicine['generic_name']
                                                )
                                            ): ?>

                                                <div class="text-muted">
                                                    <?= e(
                                                        $medicine['generic_name']
                                                    ) ?>
                                                </div>

                                            <?php endif; ?>

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
                                                        '/medicines/' .
                                                        $medicine['id'] .
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
                                                        '/medicines/' .
                                                        $medicine['id'] .
                                                        '/delete'
                                                    ) ?>"
                                                    class="d-inline"
                                                    onsubmit="return confirm(
                                                        'Are you sure you want to delete this medicine record?'
                                                    );"
                                                >

                                                    <?= csrfField() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                    >
                                                        <i class="bi bi-trash me-1"></i>
                                                        Delete
                                                    </button>

                                                </form>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <div class="row g-3 mt-2">

                                        <?php if (
                                            !empty($medicine['strength'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Strength
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['strength']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['dose'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Dose
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['dose']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['frequency'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Frequency
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['frequency']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['route'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Route
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['route']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['started_on'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Started On
                                                </div>

                                                <div>
                                                    <?= e(
                                                        date(
                                                            'd M Y',
                                                            strtotime(
                                                                $medicine['started_on']
                                                            )
                                                        )
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['stopped_on'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Stopped On
                                                </div>

                                                <div>
                                                    <?= e(
                                                        date(
                                                            'd M Y',
                                                            strtotime(
                                                                $medicine['stopped_on']
                                                            )
                                                        )
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['prescribed_by'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Prescribed By
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['prescribed_by']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <?php if (!empty($medicine['notes'])): ?>

                                        <div class="mt-3 pt-3 border-top">

                                            <div class="text-muted small mb-1">
                                                Notes
                                            </div>

                                            <div>
                                                <?= nl2br(
                                                    e($medicine['notes'])
                                                ) ?>
                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>


        <?php if (!empty($historicalMedicines)): ?>

            <div class="mb-4">

                <h2 class="h5 mb-3">
                    Historical Medicines
                </h2>

                <div class="row g-3">

                    <?php foreach ($historicalMedicines as $medicine): ?>

                        <div class="col-12">

                            <div class="card border-0 shadow-sm">

                                <div class="card-body">

                                    <div class="d-flex flex-wrap justify-content-between gap-3">

                                        <div>

                                            <h3 class="h5 mb-2">
                                                <?= e(
                                                    $medicine['brand_name']
                                                ) ?>
                                            </h3>

                                            <?php if (
                                                !empty(
                                                    $medicine['generic_name']
                                                )
                                            ): ?>

                                                <div class="text-muted">
                                                    <?= e(
                                                        $medicine['generic_name']
                                                    ) ?>
                                                </div>

                                            <?php endif; ?>

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
                                                        '/medicines/' .
                                                        $medicine['id'] .
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
                                                        '/medicines/' .
                                                        $medicine['id'] .
                                                        '/delete'
                                                    ) ?>"
                                                    class="d-inline"
                                                    onsubmit="return confirm(
                                                        'Are you sure you want to delete this medicine record?'
                                                    );"
                                                >

                                                    <?= csrfField() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                    >
                                                        <i class="bi bi-trash me-1"></i>
                                                        Delete
                                                    </button>

                                                </form>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <div class="row g-3 mt-2">

                                        <?php if (
                                            !empty($medicine['strength'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Strength
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['strength']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['dose'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Dose
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['dose']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['frequency'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Frequency
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['frequency']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['route'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Route
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['route']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['started_on'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Started On
                                                </div>

                                                <div>
                                                    <?= e(
                                                        date(
                                                            'd M Y',
                                                            strtotime(
                                                                $medicine['started_on']
                                                            )
                                                        )
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['stopped_on'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Stopped On
                                                </div>

                                                <div>
                                                    <?= e(
                                                        date(
                                                            'd M Y',
                                                            strtotime(
                                                                $medicine['stopped_on']
                                                            )
                                                        )
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($medicine['prescribed_by'])
                                        ): ?>

                                            <div class="col-md-4">

                                                <div class="text-muted small">
                                                    Prescribed By
                                                </div>

                                                <div>
                                                    <?= e(
                                                        $medicine['prescribed_by']
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <?php if (!empty($medicine['notes'])): ?>

                                        <div class="mt-3 pt-3 border-top">

                                            <div class="text-muted small mb-1">
                                                Notes
                                            </div>

                                            <div>
                                                <?= nl2br(
                                                    e($medicine['notes'])
                                                ) ?>
                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>

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
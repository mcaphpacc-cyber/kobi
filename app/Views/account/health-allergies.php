<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">

        <div>

            <div class="text-muted small mb-1">
                Health Record
            </div>

            <h1 class="h3 mb-1">
                Allergies
            </h1>

            <div class="text-muted">
                <?= e($profile['full_name']) ?>
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
                    '/allergies/create'
                ) ?>"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Add Allergy
            </a>

        <?php endif; ?>

    </div>


    <?php if (empty($allergies)): ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <div class="mb-3">
                    <i
                        class="bi bi-exclamation-triangle"
                        style="font-size: 2.5rem;"
                    ></i>
                </div>

                <h2 class="h5">
                    No allergies recorded
                </h2>

                <p class="text-muted mb-4">
                    Add known allergies and related reactions
                    to this health record.
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
                            '/allergies/create'
                        ) ?>"
                        class="btn btn-outline-primary"
                    >
                        Add Allergy
                    </a>

                <?php endif; ?>

            </div>

        </div>

    <?php else: ?>


        <div class="row g-4">

            <?php foreach ($allergies as $allergy): ?>

                <div class="col-12">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

                                <div>

                                    <h2 class="h5 mb-1">
                                        <?= e(
                                            $allergy['allergen']
                                        ) ?>
                                    </h2>


                                    <div class="text-muted small">

                                        <?= e(
                                            ucfirst(
                                                $allergy['category']
                                            )
                                        ) ?>

                                    </div>

                                </div>


                                <?php if (
                                    !empty(
                                        $allergy['severity']
                                    )
                                ): ?>

                                    <?php
                                    $severityLabel =
                                        ucwords(
                                            str_replace(
                                                '-',
                                                ' ',
                                                $allergy['severity']
                                            )
                                        );
                                    ?>

                                    <span class="badge text-bg-secondary">
                                        <?= e(
                                            $severityLabel
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <?php if (
                                !empty(
                                    $allergy['reaction']
                                )
                            ): ?>

                                <div class="mt-3">

                                    <div class="text-muted small mb-1">
                                        Reaction
                                    </div>

                                    <div>
                                        <?= e(
                                            $allergy['reaction']
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $allergy['identified_on']
                                )
                            ): ?>

                                <div class="mt-3">

                                    <div class="text-muted small mb-1">
                                        Identified On
                                    </div>

                                    <div>
                                        <?= e(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $allergy['identified_on']
                                                )
                                            )
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $allergy['notes']
                                )
                            ): ?>

                                <div class="mt-3">

                                    <div class="text-muted small mb-1">
                                        Notes
                                    </div>

                                    <div>
                                        <?= nl2br(
                                            e(
                                                $allergy['notes']
                                            )
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                in_array(
                                    $profile['role'] ?? null,
                                    ['owner', 'editor'],
                                    true
                                )
                            ): ?>

                                <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">

                                    <a
                                        href="<?= url(
                                            '/account/health-records/' .
                                            $profile['id'] .
                                            '/allergies/' .
                                            $allergy['id'] .
                                            '/edit'
                                        ) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="bi bi-pencil me-1"></i>
                                        Edit
                                    </a>


                                    <form
                                        method="post"
                                        action="<?= url(
                                            '/account/health-records/' .
                                            $profile['id'] .
                                            '/allergies/' .
                                            $allergy['id'] .
                                            '/delete'
                                        ) ?>"
                                        class="d-inline"
                                        onsubmit="return confirm(
                                            'Are you sure you want to delete this allergy record?'
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
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="h3 mb-1">
                Surgeries &amp; Procedures
            </h1>

            <p class="text-muted mb-0">
                <?= e($profile['full_name']) ?>
            </p>
        </div>

        <div class="d-flex gap-2">

            <a
                href="<?= url(
                    '/account/health-records/' .
                    $profile['id']
                ) ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Health Record
            </a>

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
                        '/procedures/create'
                    ) ?>"
                    class="btn btn-primary"
                >
                    <i class="bi bi-plus-lg me-1"></i>
                    Add Procedure
                </a>

            <?php endif; ?>

        </div>

    </div>


    <?php if (empty($procedures)): ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <div class="mb-3">
                    <i class="bi bi-hospital fs-1 text-muted"></i>
                </div>

                <h2 class="h5">
                    No surgeries or procedures recorded
                </h2>

                <p class="text-muted mb-4">
                    Keep a record of surgeries, medical procedures,
                    and hospitalizations for this health profile.
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
                            '/procedures/create'
                        ) ?>"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-plus-lg me-1"></i>
                        Add Procedure
                    </a>

                <?php endif; ?>

            </div>

        </div>

    <?php else: ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>Procedure</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Hospital</th>
                                <th>Doctor</th>
                                <th class="text-end">
                                    Actions
                                </th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($procedures as $procedure): ?>

                                <tr>

                                    <td>
                                        <div class="fw-semibold">
                                            <?= e(
                                                $procedure['procedure_name']
                                            ) ?>
                                        </div>

                                        <?php if (!empty($procedure['reason'])): ?>

                                            <div class="small text-muted">
                                                <?= e(
                                                    $procedure['reason']
                                                ) ?>
                                            </div>

                                        <?php endif; ?>
                                    </td>


                                    <td>

                                        <?php
                                        $typeLabels = [
                                            'surgery' => 'Surgery',
                                            'procedure' => 'Procedure',
                                            'hospitalization' =>
                                                'Hospitalization'
                                        ];

                                        $typeLabel =
                                            $typeLabels[
                                                $procedure['procedure_type']
                                            ]
                                            ?? ucfirst(
                                                $procedure['procedure_type']
                                            );
                                        ?>

                                        <span class="badge text-bg-light">
                                            <?= e($typeLabel) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $procedure['performed_on']
                                            )
                                        ): ?>

                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $procedure[
                                                            'performed_on'
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Not specified
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $procedure['hospital']
                                            )
                                        ): ?>

                                            <?= e(
                                                $procedure['hospital']
                                            ) ?>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $procedure['doctor']
                                            )
                                        ): ?>

                                            <?= e(
                                                $procedure['doctor']
                                            ) ?>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td class="text-end">

                                        <div
                                            class="d-inline-flex gap-1"
                                        >

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
                                                        '/procedures/' .
                                                        $procedure['id'] .
                                                        '/edit'
                                                    ) ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit"
                                                >
                                                    <i
                                                        class="bi bi-pencil"
                                                    ></i>
                                                </a>

                                            <?php endif; ?>


                                            <?php if (
                                                in_array(
                                                    $profile['role'] ?? null,
                                                    ['owner', 'editor'],
                                                    true
                                                )
                                            ): ?>

                                                <form
                                                    method="POST"
                                                    action="<?= url(
                                                        '/account/health-records/' .
                                                        $profile['id'] .
                                                        '/procedures/' .
                                                        $procedure['id'] .
                                                        '/delete'
                                                    ) ?>"
                                                    class="d-inline"
                                                    onsubmit="return confirm(
                                                        'Are you sure you want to delete this record?'
                                                    );"
                                                >

                                                    <?= csrfField() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete"
                                                    >
                                                        <i
                                                            class="bi bi-trash"
                                                        ></i>
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        </div>

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
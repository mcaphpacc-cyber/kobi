<?php

$savedDiseases = $savedDiseases ?? [];

?>

<div class="container py-5">

    <div class="mb-4">

        <h1 class="mb-2">
            Saved Diseases
        </h1>

        <p class="text-muted mb-0">
            Diseases you have saved for quick access.
        </p>

    </div>

    <?php if (empty($savedDiseases)) : ?>

        <div class="card shadow-sm border-0">

            <div class="card-body text-center py-5">

                <div class="mb-3">

                    <i class="bi bi-bookmark fs-1 text-muted"></i>

                </div>

                <h4 class="mb-2">
                    No saved diseases yet
                </h4>

                <p class="text-muted mb-4">
                    Save diseases while exploring KOBI
                    and they will appear here.
                </p>

                <a
                    href="<?= url('/diseases'); ?>"
                    class="btn btn-primary"
                >
                    <i class="bi bi-search me-2"></i>
                    Explore Diseases
                </a>

            </div>

        </div>

    <?php else : ?>

        <div class="card shadow-sm border-0">

            <div class="card-header bg-white">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">

                        <i class="bi bi-bookmark-fill me-2"></i>

                        <?= count($savedDiseases); ?>

                        Saved
                        <?= count($savedDiseases) === 1
                            ? 'Disease'
                            : 'Diseases'; ?>

                    </h5>

                </div>

            </div>

            <div class="list-group list-group-flush">

                <?php foreach ($savedDiseases as $disease) : ?>

                    <div class="list-group-item py-3">

                        <div
                            class="d-flex justify-content-between align-items-center gap-3"
                        >

                            <div>

                                <h5 class="mb-1">

                                    <a
                                        href="<?= url(
                                            '/disease/'
                                            . $disease['slug']
                                        ); ?>"
                                        class="text-decoration-none"
                                    >
                                        <?= e(
                                            $disease['disease_en']
                                        ); ?>
                                    </a>

                                </h5>

                                <?php if (!empty(
                                    $disease['body_system']
                                )) : ?>

                                    <div class="small text-muted">

                                        <?= e(
                                            $disease['body_system']
                                        ); ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="d-flex gap-2">

                                <a
                                    href="<?= url(
                                        '/disease/'
                                        . $disease['slug']
                                    ); ?>"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i class="bi bi-arrow-right-circle me-1"></i>
                                    View
                                </a>

                                <form
                                    method="POST"
                                    action="<?= url(
                                        '/account/saved-diseases/remove'
                                    ); ?>"
                                    class="d-inline"
                                >

                                    <?= csrfField(); ?>

                                    <input
                                        type="hidden"
                                        name="disease_id"
                                        value="<?= (int) $disease['disease_id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger btn-sm"
                                    >
                                        <i class="bi bi-bookmark-x me-1"></i>
                                        Remove
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

    <div class="mt-4">

        <a
            href="<?= url('/account'); ?>"
            class="btn btn-outline-secondary btn-sm"
        >
            <i class="bi bi-arrow-left me-2"></i>
            Back to Account
        </a>

    </div>

</div>
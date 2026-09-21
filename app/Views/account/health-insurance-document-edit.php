<?php
/**
 * @var array $profile
 * @var array $policy
 * @var array $document
 * @var string|null $error
 */
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Linked Document</h1>
            <p class="text-muted mb-0">
                <?= e($policy['policy_name'] ?? 'Insurance Policy') ?>
            </p>
        </div>

        <a
            href="<?= e(
                url(
                    '/account/health-records/'
                    . $profile['id']
                    . '/insurance/'
                    . $policy['id']
                )
            ) ?>"
            class="btn btn-outline-secondary"
        >
            Back
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label text-muted">
                    Document
                </label>

                <div class="fw-semibold">
                    <?= e(
                        $document['title']
                        ?? $document['original_filename']
                        ?? 'Health Document'
                    ) ?>
                </div>

                <?php if (!empty($document['original_filename'])): ?>
                    <div class="small text-muted">
                        <?= e($document['original_filename']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <form
                method="POST"
                action="<?= e(
                    url(
                        '/account/health-records/'
                        . $profile['id']
                        . '/insurance/'
                        . $policy['id']
                        . '/documents/'
                        . $document['id']
                        . '/update'
                    )
                ) ?>"
            >
                <?= csrfField() ?>

                <div class="mb-4">
                    <label
                        for="document_label"
                        class="form-label"
                    >
                        Document Label
                        <span class="text-muted">
                            (Optional)
                        </span>
                    </label>

                    <input
                        type="text"
                        id="document_label"
                        name="document_label"
                        class="form-control"
                        maxlength="150"
                        value="<?= e(
                            $document['document_label'] ?? ''
                        ) ?>"
                        placeholder="e.g. Policy Card, Premium Receipt"
                    >

                    <div class="form-text">
                        Add a custom label to identify this document
                        within the insurance policy.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>

                    <a
                        href="<?= e(
                            url(
                                '/account/health-records/'
                                . $profile['id']
                                . '/insurance/'
                                . $policy['id']
                            )
                        ) ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
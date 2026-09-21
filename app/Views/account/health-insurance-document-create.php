<?php
/**
 * @var array $profile
 * @var array $policy
 * @var array $documents
 * @var string|null $error
 */
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Link Health Document</h1>
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

    <?php if (empty($documents)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <h2 class="h5">No Documents Available</h2>

                <p class="text-muted mb-4">
                    All your health documents may already be linked
                    to this policy, or you have not uploaded any documents.
                </p>

                <a
                    href="<?= e(
                        url(
                            '/account/health-records/'
                            . $profile['id']
                            . '/documents'
                        )
                    ) ?>"
                    class="btn btn-primary"
                >
                    Manage Health Documents
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form
                    method="POST"
                    action="<?= e(
                        url(
                            '/account/health-records/'
                            . $profile['id']
                            . '/insurance/'
                            . $policy['id']
                            . '/documents/store'
                        )
                    ) ?>"
                >
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label
                            for="document_id"
                            class="form-label"
                        >
                            Select Document
                        </label>

                        <select
                            id="document_id"
                            name="document_id"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Select a document
                            </option>

                            <?php foreach ($documents as $document): ?>
                                <option
                                    value="<?= e(
                                        (string) $document['id']
                                    ) ?>"
                                >
                                    <?= e(
                                        $document['title']
                                        ?: $document['original_filename']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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
                            Link Document
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
    <?php endif; ?>
</div>
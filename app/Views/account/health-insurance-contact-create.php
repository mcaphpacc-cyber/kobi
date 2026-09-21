<?php
$formData = $_POST ?? [];

$contactTypes = [
    'insurer' => 'Insurer',
    'tpa' => 'TPA',
    'claims' => 'Claims',
    'other' => 'Other',
];
?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                Add Insurance Contact
            </h1>

            <p class="text-muted mb-0">
                <?= e($policy['provider_name'] ?? 'Insurance Policy') ?>
            </p>
        </div>

        <a
            href="<?= url(
                '/account/health-records/' .
                (int) $profile['id'] .
                '/insurance/' .
                (int) $policy['id']
            ) ?>"
            class="btn btn-outline-secondary"
        >
            Back
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form
        method="POST"
        action="<?= url(
            '/account/health-records/' .
            (int) $profile['id'] .
            '/insurance/' .
            (int) $policy['id'] .
            '/contacts'
        ) ?>"
    >
        <?= csrfField() ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">
                            Contact Type
                        </label>

                        <select
                            name="contact_type"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Select Contact Type
                            </option>

                            <?php foreach ($contactTypes as $value => $label): ?>
                                <option
                                    value="<?= e($value) ?>"
                                    <?= (($formData['contact_type'] ?? '') === $value)
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Organization Name
                        </label>

                        <input
                            type="text"
                            name="organization_name"
                            class="form-control"
                            maxlength="255"
                            value="<?= e(
                                $formData['organization_name'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Contact Person
                        </label>

                        <input
                            type="text"
                            name="contact_person"
                            class="form-control"
                            maxlength="255"
                            value="<?= e(
                                $formData['contact_person'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            maxlength="50"
                            value="<?= e(
                                $formData['phone'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Alternate Phone
                        </label>

                        <input
                            type="text"
                            name="alternate_phone"
                            class="form-control"
                            maxlength="50"
                            value="<?= e(
                                $formData['alternate_phone'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            maxlength="255"
                            value="<?= e(
                                $formData['email'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Website
                        </label>

                        <input
                            type="url"
                            name="website"
                            class="form-control"
                            maxlength="255"
                            placeholder="https://example.com"
                            value="<?= e(
                                $formData['website'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Address
                        </label>

                        <textarea
                            name="address"
                            class="form-control"
                            rows="3"
                        ><?= e($formData['address'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                        ><?= e($formData['notes'] ?? '') ?></textarea>
                    </div>

                </div>

            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a
                    href="<?= url(
                        '/account/health-records/' .
                        (int) $profile['id'] .
                        '/insurance/' .
                        (int) $policy['id']
                    ) ?>"
                    class="btn btn-outline-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Contact
                </button>
            </div>
        </div>
    </form>

</div>
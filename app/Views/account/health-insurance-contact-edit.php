<?php

$formData = array_merge(
    $contact ?? [],
    $_POST ?? []
);

$contactType = $formData['contact_type'] ?? '';
$organizationName = $formData['organization_name'] ?? '';
$contactPerson = $formData['contact_person'] ?? '';
$phone = $formData['phone'] ?? '';
$alternatePhone = $formData['alternate_phone'] ?? '';
$email = $formData['email'] ?? '';
$website = $formData['website'] ?? '';
$address = $formData['address'] ?? '';
$notes = $formData['notes'] ?? '';

?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                Edit Insurance Contact
            </h1>

            <p class="text-muted mb-0">
                <?= e($policy['policy_name'] ?: $policy['provider_name']) ?>
            </p>
        </div>

        <a
            href="<?= e(
                url(
                    "account/health-records/{$profile['id']}/insurance/{$policy['id']}"
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

    <div class="card shadow-sm">
        <div class="card-body">

            <form
                method="POST"
                action="<?= e(
                    url(
                        "account/health-records/{$profile['id']}/insurance/{$policy['id']}/contacts/{$contact['id']}/update"
                    )
                ) ?>"
            >

                <?= csrfField() ?>

                <div class="row g-3">

                    <div class="col-md-6">

                        <label
                            for="contact_type"
                            class="form-label"
                        >
                            Contact Type
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="contact_type"
                            id="contact_type"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Select Contact Type
                            </option>

                            <?php
                            $contactTypes = [
                                'insurer' => 'Insurer',
                                'tpa' => 'TPA',
                                'claims' => 'Claims',
                                'other' => 'Other',
                            ];
                            ?>

                            <?php foreach (
                                $contactTypes as $value => $label
                            ): ?>

                                <option
                                    value="<?= e($value) ?>"
                                    <?= $contactType === $value
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($label) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label
                            for="organization_name"
                            class="form-label"
                        >
                            Organization Name
                        </label>

                        <input
                            type="text"
                            name="organization_name"
                            id="organization_name"
                            class="form-control"
                            value="<?= e($organizationName) ?>"
                            maxlength="255"
                        >

                    </div>

                    <div class="col-md-6">

                        <label
                            for="contact_person"
                            class="form-label"
                        >
                            Contact Person
                        </label>

                        <input
                            type="text"
                            name="contact_person"
                            id="contact_person"
                            class="form-control"
                            value="<?= e($contactPerson) ?>"
                            maxlength="255"
                        >

                    </div>

                    <div class="col-md-6">

                        <label
                            for="phone"
                            class="form-label"
                        >
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="form-control"
                            value="<?= e($phone) ?>"
                            maxlength="50"
                        >

                    </div>

                    <div class="col-md-6">

                        <label
                            for="alternate_phone"
                            class="form-label"
                        >
                            Alternate Phone
                        </label>

                        <input
                            type="text"
                            name="alternate_phone"
                            id="alternate_phone"
                            class="form-control"
                            value="<?= e($alternatePhone) ?>"
                            maxlength="50"
                        >

                    </div>

                    <div class="col-md-6">

                        <label
                            for="email"
                            class="form-label"
                        >
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                            value="<?= e($email) ?>"
                            maxlength="255"
                        >

                    </div>

                    <div class="col-md-6">

                        <label
                            for="website"
                            class="form-label"
                        >
                            Website
                        </label>

                        <input
                            type="url"
                            name="website"
                            id="website"
                            class="form-control"
                            value="<?= e($website) ?>"
                            maxlength="255"
                        >

                    </div>

                    <div class="col-12">

                        <label
                            for="address"
                            class="form-label"
                        >
                            Address
                        </label>

                        <textarea
                            name="address"
                            id="address"
                            class="form-control"
                            rows="3"
                        ><?= e($address) ?></textarea>

                    </div>

                    <div class="col-12">

                        <label
                            for="notes"
                            class="form-label"
                        >
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            id="notes"
                            class="form-control"
                            rows="4"
                        ><?= e($notes) ?></textarea>

                    </div>

                </div>

                <div class="d-flex gap-2 mt-4">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Update Contact
                    </button>

                    <a
                        href="<?= e(
                            url(
                                "account/health-records/{$profile['id']}/insurance/{$policy['id']}"
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
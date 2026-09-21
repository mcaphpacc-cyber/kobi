<?php
$formData = array_merge(
    $member ?? [],
    $_POST ?? []
);

$relationships = [
    'self' => 'Self',
    'spouse' => 'Spouse',
    'child' => 'Child',
    'parent' => 'Parent',
    'sibling' => 'Sibling',
    'other' => 'Other'
];

$selectedRelationship =
    $formData['relationship'] ?? 'other';

$isPrimary =
    !empty($formData['is_primary_insured']);
?>

<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?= url(
                '/account/health-records/' .
                (int) $profile['id'] .
                '/insurance/' .
                (int) $policy['id']
            ) ?>"
            class="btn btn-outline-secondary mb-3"
        >
            ← Back to Policy
        </a>

        <h1 class="h3 mb-1">
            Edit Insured Member
        </h1>

        <p class="text-muted mb-0">
            <?= e(
                $policy['policy_name']
                ?: $policy['provider_name']
                ?: 'Insurance Policy'
            ) ?>
        </p>

    </div>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <h2 class="h5 mb-0">
                Member Information
            </h2>

        </div>

        <div class="card-body">

            <form
                method="POST"
                action="<?= url(
                    '/account/health-records/' .
                    (int) $profile['id'] .
                    '/insurance/' .
                    (int) $policy['id'] .
                    '/members/' .
                    (int) $member['id'] .
                    '/update'
                ) ?>"
            >

                <?= csrfField() ?>


                <div class="mb-3">

                    <label
                        for="member_name"
                        class="form-label"
                    >
                        Member Name
                    </label>

                    <input
                        type="text"
                        id="member_name"
                        name="member_name"
                        class="form-control"
                        maxlength="150"
                        value="<?= e(
                            $formData['member_name'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label
                        for="relationship"
                        class="form-label"
                    >
                        Relationship
                    </label>

                    <select
                        id="relationship"
                        name="relationship"
                        class="form-select"
                        required
                    >

                        <?php foreach (
                            $relationships
                            as $value => $label
                        ): ?>

                            <option
                                value="<?= e($value) ?>"
                                <?= $selectedRelationship === $value
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($label) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="mb-3">

                    <label
                        for="member_id_number"
                        class="form-label"
                    >
                        Member ID Number
                    </label>

                    <input
                        type="text"
                        id="member_id_number"
                        name="member_id_number"
                        class="form-control"
                        maxlength="100"
                        value="<?= e(
                            $formData['member_id_number'] ?? ''
                        ) ?>"
                    >

                </div>


                <div class="mb-3">

                    <label
                        for="health_card_number"
                        class="form-label"
                    >
                        Health Card Number
                    </label>

                    <input
                        type="text"
                        id="health_card_number"
                        name="health_card_number"
                        class="form-control"
                        maxlength="100"
                        value="<?= e(
                            $formData['health_card_number'] ?? ''
                        ) ?>"
                    >

                </div>


                <div class="mb-3">

                    <label
                        for="date_of_birth"
                        class="form-label"
                    >
                        Date of Birth
                    </label>

                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        class="form-control"
                        value="<?= e(
                            $formData['date_of_birth'] ?? ''
                        ) ?>"
                    >

                </div>


                <div class="form-check mb-3">

                    <input
                        type="checkbox"
                        id="is_primary_insured"
                        name="is_primary_insured"
                        value="1"
                        class="form-check-input"
                        <?= $isPrimary
                            ? 'checked'
                            : '' ?>
                    >

                    <label
                        for="is_primary_insured"
                        class="form-check-label"
                    >
                        Primary Insured
                    </label>

                </div>


                <div class="mb-4">

                    <label
                        for="notes"
                        class="form-label"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        class="form-control"
                        rows="4"
                    ><?= e(
                        $formData['notes'] ?? ''
                    ) ?></textarea>

                </div>


                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Update Member
                    </button>

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

                </div>

            </form>

        </div>

    </div>

</div>
<?php
$formData = $_POST ?? [];

$policyTypes = [
    'individual' => 'Individual',
    'family_floater' => 'Family Floater',
    'employer' => 'Employer',
    'government' => 'Government',
    'supplementary' => 'Supplementary',
    'other' => 'Other'
];

$statuses = [
    'active' => 'Active',
    'expired' => 'Expired',
    'cancelled' => 'Cancelled',
    'pending' => 'Pending',
    'unknown' => 'Unknown'
];
?>

<div class="container py-4">

    <div class="mb-4">
        <div class="text-muted mb-1">
            Health Record
        </div>

        <h1 class="h3 mb-1">
            <?= e($profile['full_name']) ?>
        </h1>

        <div class="text-muted">
            Add Health Insurance Policy
        </div>
    </div>


    <?php if (!empty($error)): ?>

        <div class="alert alert-danger" role="alert">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <div class="card border shadow-sm">

        <div class="card-body p-4">

            <form
                method="POST"
                action="<?= url(
                    '/account/health-records/' .
                    (int) $profile['id'] .
                    '/insurance'
                ) ?>"
            >

                <?= csrfField() ?>


                <h5 class="mb-3">
                    Policy Information
                </h5>


                <div class="row g-3">

                    <div class="col-12 col-md-6">

                        <label
                            for="provider_name"
                            class="form-label"
                        >
                            Insurance Provider
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="provider_name"
                            name="provider_name"
                            value="<?= e(
                                $formData['provider_name'] ?? ''
                            ) ?>"
                            maxlength="255"
                            required
                        >

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            for="policy_number"
                            class="form-label"
                        >
                            Policy Number
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="policy_number"
                            name="policy_number"
                            value="<?= e(
                                $formData['policy_number'] ?? ''
                            ) ?>"
                            maxlength="255"
                        >

                    </div>


                    <div class="col-12">

                        <label
                            for="policy_name"
                            class="form-label"
                        >
                            Policy Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="policy_name"
                            name="policy_name"
                            value="<?= e(
                                $formData['policy_name'] ?? ''
                            ) ?>"
                            maxlength="255"
                            placeholder="Example: Family Health Insurance"
                        >

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            for="policy_type"
                            class="form-label"
                        >
                            Policy Type
                        </label>

                        <select
                            class="form-select"
                            id="policy_type"
                            name="policy_type"
                        >

                            <?php foreach (
                                $policyTypes as $value => $label
                            ): ?>

                                <option
                                    value="<?= e($value) ?>"
                                    <?= (
                                        ($formData['policy_type']
                                            ?? 'individual')
                                        === $value
                                    )
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($label) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            for="status"
                            class="form-label"
                        >
                            Status
                        </label>

                        <select
                            class="form-select"
                            id="status"
                            name="status"
                        >

                            <?php foreach (
                                $statuses as $value => $label
                            ): ?>

                                <option
                                    value="<?= e($value) ?>"
                                    <?= (
                                        ($formData['status']
                                            ?? 'active')
                                        === $value
                                    )
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($label) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            for="sum_insured"
                            class="form-label"
                        >
                            Sum Insured
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="sum_insured"
                            name="sum_insured"
                            value="<?= e(
                                $formData['sum_insured'] ?? ''
                            ) ?>"
                            min="0"
                            max="9999999999999.99"
                            step="0.01"
                            placeholder="Example: 500000"
                        >

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            for="currency_code"
                            class="form-label"
                        >
                            Currency
                        </label>

                        <select
                            class="form-select"
                            id="currency_code"
                            name="currency_code"
                        >

                            <?php
                            $currency =
                                $formData['currency_code'] ?? 'INR';
                            ?>

                            <option
                                value="INR"
                                <?= $currency === 'INR'
                                    ? 'selected'
                                    : '' ?>
                            >
                                INR - Indian Rupee
                            </option>

                            <option
                                value="USD"
                                <?= $currency === 'USD'
                                    ? 'selected'
                                    : '' ?>
                            >
                                USD - US Dollar
                            </option>

                            <option
                                value="EUR"
                                <?= $currency === 'EUR'
                                    ? 'selected'
                                    : '' ?>
                            >
                                EUR - Euro
                            </option>

                            <option
                                value="GBP"
                                <?= $currency === 'GBP'
                                    ? 'selected'
                                    : '' ?>
                            >
                                GBP - British Pound
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            for="start_date"
                            class="form-label"
                        >
                            Start Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="start_date"
                            name="start_date"
                            value="<?= e(
                                $formData['start_date'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            for="expiry_date"
                            class="form-label"
                        >
                            Expiry Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="expiry_date"
                            name="expiry_date"
                            value="<?= e(
                                $formData['expiry_date'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="col-12">

                        <label
                            for="renewal_notes"
                            class="form-label"
                        >
                            Renewal Notes
                        </label>

                        <textarea
                            class="form-control"
                            id="renewal_notes"
                            name="renewal_notes"
                            rows="3"
                            maxlength="5000"
                            placeholder="Renewal date, reminders, or renewal instructions"
                        ><?= e(
                            $formData['renewal_notes'] ?? ''
                        ) ?></textarea>

                    </div>


                    <div class="col-12">

                        <label
                            for="general_notes"
                            class="form-label"
                        >
                            General Notes
                        </label>

                        <textarea
                            class="form-control"
                            id="general_notes"
                            name="general_notes"
                            rows="4"
                            maxlength="5000"
                            placeholder="Additional policy information"
                        ><?= e(
                            $formData['general_notes'] ?? ''
                        ) ?></textarea>

                    </div>

                </div>


                <hr class="my-4">


                <div class="d-flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Insurance Policy
                    </button>


                    <a
                        href="<?= url(
                            '/account/health-records/' .
                            (int) $profile['id'] .
                            '/insurance'
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
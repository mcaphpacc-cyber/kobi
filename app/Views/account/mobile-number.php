<div class="border-top pt-3 mt-4">
    <h3 class="h5">Account mobile number</h3>
    <p class="text-muted small">Private account contact information. No mobile verification is performed. Stored numbers are not eligible for sharing.</p>
    <?php if (!empty($mobileError)): ?>
        <div class="alert alert-danger text-break" role="alert"><?= e($mobileError) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['mobile_updated']) && $_GET['mobile_updated'] === '1'): ?>
        <div class="alert alert-success" role="status">Mobile number saved. An empty value removes it.</div>
    <?php endif; ?>
    <?php if (empty($user['mobile_number'])): ?><p class="small text-muted">No mobile number stored.</p><?php endif; ?>
    <form method="post" action="<?= e(url('/account/profile/mobile')) ?>">
        <?= csrfField() ?>
        <label for="mobile_number" class="form-label">Mobile Number</label>
        <input type="tel" class="form-control" id="mobile_number" name="mobile_number" maxlength="64" autocomplete="tel"
            value="<?= e($mobileForm ?? $user['mobile_number'] ?? '') ?>" aria-describedby="mobile-help">
        <div class="form-text mb-3" id="mobile-help">Include + and the country code. Spaces, hyphens and parentheses are accepted. Leave empty to remove your number.</div>
        <button class="btn btn-primary" type="submit">Save Mobile Number</button>
    </form>
</div>

<div class="container py-4">
    <h1 class="h2">Saved Symptom Checks</h1>
    <p class="text-muted">Your previous KOBI symptom-check interactions, not diagnoses or clinician records.</p>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-primary" href="<?= e(url('/account')) ?>">My Account</a>
        <a class="btn btn-outline-primary" href="<?= e(url('/symptom-checker')) ?>">Start a new check</a>
    </div>
    <?php if (isset($_GET['deleted'])): ?><p class="alert alert-success" role="status">Saved symptom check deleted.</p><?php endif; ?>
    <?php if ($listing['rows'] === []): ?><p class="alert alert-info">No saved checks on this page. You can save a completed check from the Symptom Checker.</p><?php endif; ?>
    <div class="list-group mb-3">
        <?php foreach ($listing['rows'] as $row): ?>
            <div class="list-group-item">
                <p class="mb-1">Checked on <?= e($row['checked_at']) ?> UTC</p>
                <p class="small text-muted mb-2">Saved on <?= e($row['saved_at']) ?> UTC · <?= (int) $row['selected_symptom_count'] ?> selected symptoms · <?= (int) $row['displayed_result_count'] ?> results shown</p>
                <a class="btn btn-outline-primary" href="<?= e(url('/account/saved-symptom-checks/' . (int) $row['id'])) ?>">View previous result</a>
                <form class="mt-3" method="POST" action="<?= e(url('/account/saved-symptom-checks/' . (int) $row['id'] . '/delete')) ?>">
                    <?= csrfField() ?>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="confirm_delete" value="1" required> <span class="form-check-label">Permanently delete this saved check</span></label>
                    <button class="btn btn-outline-danger mt-2" type="submit">Delete saved check</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <nav class="d-flex flex-wrap gap-2" aria-label="Saved checks pages">
        <?php if ($listing['page'] > 1): ?><a class="btn btn-outline-primary" href="<?= e(url('/account/saved-symptom-checks?page=' . ($listing['page'] - 1))) ?>">Previous page</a><?php endif; ?>
        <?php if ($listing['has_next']): ?><a class="btn btn-outline-primary" href="<?= e(url('/account/saved-symptom-checks?page=' . ($listing['page'] + 1))) ?>">Next page</a><?php endif; ?>
    </nav>
</div>

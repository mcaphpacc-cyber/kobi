<?php
$profile = $dashboard['selected_profile'];
$counts = $dashboard['counts'];
$profilePath = $profile !== null ? '/account/health-records/' . $profile['id'] : null;
?>
<div class="container py-5">
    <h1 class="h2">Personal Health Dashboard</h1>
    <p class="text-muted">Your recorded personal health information and links to manage it.</p>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-outline-primary" href="<?= e(url('/account')) ?>">Account settings</a>
        <a class="btn btn-outline-primary" href="<?= e(url('/account/health-records')) ?>">Health Records</a>
    </div>

    <aside class="card border-primary shadow-sm mb-4" aria-labelledby="dashboard-guidance-heading">
        <div class="card-body">
            <h2 id="dashboard-guidance-heading" class="h5">Suggested next steps</h2>
            <p class="text-muted small">Optional ways to organize or view your recorded information.</p>
            <div class="row g-3">
                <?php foreach ($dashboard['guidance'] as $action): ?>
                    <div class="col-12 col-lg" data-guidance-action="<?= e($action['key']) ?>">
                        <h3 class="h6"><?= e($action['title']) ?></h3>
                        <p class="small mb-2"><?= e($action['description']) ?></p>
                        <a class="btn btn-outline-primary" href="<?= e(url($action['path'])) ?>"><?= e($action['label']) ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <div class="row g-4">
        <section class="col-12" aria-labelledby="dashboard-profile-heading">
            <div class="card shadow-sm"><div class="card-body">
                <h2 id="dashboard-profile-heading" class="h5">Selected Health Profile</h2>
                <?php if ($dashboard['selection_unavailable']): ?>
                    <p class="text-muted">The requested profile is unavailable. Choose an accessible profile below.</p>
                <?php endif; ?>
                <?php if ($profile !== null): ?>
                    <p class="text-break"><strong><?= e($profile['full_name']) ?></strong><br>
                        <?= e(ucfirst($profile['profile_type'])) ?> profile · <?= e(ucfirst($profile['role'])) ?> access</p>
                    <a class="btn btn-outline-primary" href="<?= e(url($profilePath)) ?>">View health profile</a>
                <?php elseif ($dashboard['profiles'] === []): ?>
                    <p>No health profiles available.</p>
                    <a class="btn btn-outline-primary" href="<?= e(url('/account/health-records')) ?>">Set up a health profile</a>
                <?php else: ?>
                    <p>Choose a profile to view its recorded health information.</p>
                <?php endif; ?>
                <?php if ($dashboard['profiles'] !== []): ?>
                    <form method="GET" action="<?= e(url('/account/health-dashboard')) ?>" class="mt-3">
                        <label for="dashboard-profile" class="form-label">Health profile</label>
                        <div class="row g-2">
                            <div class="col-12 col-md-8">
                                <select id="dashboard-profile" name="profile_id" class="form-select" required>
                                    <option value="">Choose a profile</option>
                                    <?php foreach ($dashboard['profiles'] as $available): ?>
                                        <option value="<?= $available['id'] ?>" <?= $profile !== null && $profile['id'] === $available['id'] ? 'selected' : '' ?>><?= e($available['full_name']) ?> (<?= e($available['role']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto"><button class="btn btn-primary" type="submit">View profile overview</button></div>
                        </div>
                    </form>
                <?php endif; ?>
            </div></div>
        </section>

        <section class="col-12 col-md-6" aria-labelledby="dashboard-records-heading">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 id="dashboard-records-heading" class="h5">Health Records</h2>
                <?php if ($counts !== null): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach (['conditions' => 'Active/chronic conditions', 'allergies' => 'Allergies', 'procedures' => 'Procedures'] as $category => $label): ?>
                            <li class="mb-3"><a href="<?= e(url($profilePath . '/' . $category)) ?>"><?= e($label) ?></a>: <?= $counts[$category] ?>
                                <?php if ($counts[$category] === 0): ?><span class="text-muted d-block">No <?= e($category) ?> entries recorded.</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted small mb-0">Historical condition entries: <?= $counts['historical_conditions'] ?></p>
                <?php else: ?><p class="text-muted">Select a health profile to see record counts.</p><?php endif; ?>
            </div></div>
        </section>
        <section class="col-12 col-md-6" aria-labelledby="dashboard-medications-heading">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 id="dashboard-medications-heading" class="h5">Current Medications</h2>
                <?php if ($counts !== null): ?>
                    <p>Medications recorded as current: <strong><?= $counts['current_medications'] ?></strong></p>
                    <?php if ($counts['current_medications'] === 0): ?><p class="text-muted">No current medications recorded.</p><?php endif; ?>
                    <a class="btn btn-outline-primary" href="<?= e(url($profilePath . '/medicines')) ?>">View all medications</a>
                <?php else: ?><p class="text-muted">Select a health profile to see medication counts.</p><?php endif; ?>
            </div></div>
        </section>
        <section class="col-12 col-md-6" aria-labelledby="dashboard-documents-heading">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 id="dashboard-documents-heading" class="h5">Documents</h2>
                <?php if ($counts !== null): ?>
                    <p>Documents recorded: <strong><?= $counts['documents'] ?></strong></p>
                    <?php if ($counts['documents'] === 0): ?><p class="text-muted">No documents yet.</p><?php endif; ?>
                    <a class="btn btn-outline-primary" href="<?= e(url($profilePath . '/documents')) ?>">View documents</a>
                <?php else: ?><p class="text-muted">Select a health profile to see document counts.</p><?php endif; ?>
            </div></div>
        </section>
        <section class="col-12 col-md-6" aria-labelledby="dashboard-summary-heading">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 id="dashboard-summary-heading" class="h5">Medical Summary</h2>
                <?php if ($profile !== null): ?>
                    <p>A summary of this profile's recorded information is available to view.</p>
                    <a class="btn btn-outline-primary" href="<?= e(url($profilePath . '/medical-summary')) ?>">View medical summary</a>
                <?php else: ?><p class="text-muted">Select a health profile to view its medical summary.</p><?php endif; ?>
            </div></div>
        </section>
        <section class="col-12 col-md-6" aria-labelledby="dashboard-saved-heading">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 id="dashboard-saved-heading" class="h5">Saved Diseases</h2>
                <p>Diseases saved to your account: <strong><?= $dashboard['saved_disease_count'] ?></strong></p>
                <?php if ($dashboard['saved_disease_count'] === 0): ?><p class="text-muted">No saved diseases yet.</p><?php endif; ?>
                <p class="text-muted small">Saved for reading; these are not health-profile diagnoses.</p>
                <a class="btn btn-outline-primary" href="<?= e(url('/account/saved-diseases')) ?>">View saved diseases</a>
            </div></div>
        </section>
    </div>
</div>

<?php use App\Services\TreatmentGovernanceRules; ?>
<div class="container py-4">
    <h1 class="h3">Treatment editorial</h1>
    <p class="text-muted">Internal preparation only. Saving content does not approve or publish it.</p>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/treatments/operations')) ?>">Operations and history</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('admin/treatments/create')) ?>">Create treatment draft</a>
        <?php foreach ($systems as $system): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/treatment-sources/'.$system['id'])) ?>"><?= e($system['name_en']) ?> sources</a>
        <?php endforeach; ?>
    </div>
    <form method="get" action="<?= e(url('admin/treatments')) ?>" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-4"><label for="filter-system" class="form-label">Treatment system</label><select id="filter-system" name="treatment_system_id" class="form-select"><option value="">All permitted systems</option><?php foreach ($systems as $system): ?><option value="<?= (int)$system['id'] ?>" <?= ($filters['treatment_system_id']??null)===$system['id'] ? 'selected' : '' ?>><?= e($system['name_en']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label for="filter-disease" class="form-label">Disease ID</label><input id="filter-disease" type="number" min="1" name="disease_id" class="form-control" value="<?= e((string)($filters['disease_id']??'')) ?>"></div>
            <?php foreach (['review_state'=>TreatmentGovernanceRules::REVIEW,'publication_status'=>TreatmentGovernanceRules::PUBLICATION,'evidence_classification'=>TreatmentGovernanceRules::EVIDENCE,'completeness'=>['unassessed','missing_sources']] as $field=>$options): ?>
                <div class="col-md-4"><label class="form-label" for="filter-<?= e($field) ?>"><?= e(ucfirst(str_replace('_',' ',$field))) ?></label><select class="form-select" id="filter-<?= e($field) ?>" name="<?= e($field) ?>"><option value="">All</option><?php foreach ($options as $option): ?><option value="<?= e($option) ?>" <?= ($filters[$field]??null)===$option ? 'selected' : '' ?>><?= e(str_replace('_',' ',$option)) ?></option><?php endforeach; ?></select></div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3"><button class="btn btn-primary">Filter protocols</button></div>
        <small class="text-muted mt-2">Completeness filters flag unassessed classifications or missing overview references; they are not clinical approval checks.</small>
    </form>
    <div class="table-responsive"><table class="table table-striped align-middle"><caption>Protocols in systems you can edit</caption><thead><tr><th>ID / title</th><th>Disease / system</th><th>Review / publication</th><th>Revision</th><th>Evidence / purpose</th><th>Overview references</th><th>Updated (UTC)</th></tr></thead><tbody>
        <?php foreach ($protocols as $p): ?><tr><td><a href="<?= e(url('admin/treatments/'.$p['id'].'/edit')) ?>">#<?= (int)$p['id'] ?> <?= e($p['title_en']??'Untitled') ?></a></td><td><?= e($p['disease_en']) ?><br><small><?= e($p['system_name']) ?></small></td><td><?= e($p['review_state']) ?><br><?= e($p['publication_status']) ?></td><td><?= (int)$p['content_revision'] ?></td><td><?= e(str_replace('_',' ',$p['evidence_classification'])) ?><br><?= e(str_replace('_',' ',$p['content_purpose']??'Unassigned')) ?></td><td><?= (int)$p['source_count'] ?></td><td><?= e($p['updated_at']??'—') ?></td></tr><?php endforeach; ?>
        <?php if (!$protocols): ?><tr><td colspan="7">No protocols match these filters.</td></tr><?php endif; ?>
    </tbody></table></div>
    <nav aria-label="Editorial pages" class="d-flex gap-2">
        <?php if ($page>1): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments').'?'.http_build_query($filters+['page'=>$page-1])) ?>">Previous</a><?php endif; ?>
        <?php if ($more): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments').'?'.http_build_query($filters+['page'=>$page+1])) ?>">Next</a><?php endif; ?>
    </nav>
</div>

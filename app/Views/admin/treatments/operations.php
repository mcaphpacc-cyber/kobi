<?php use App\Services\TreatmentGovernanceRules; ?>
<div class="container py-4">
    <h1 class="h3">Treatment operations</h1>
    <p class="text-muted">Internal governance operations and append-only history for your permitted treatment systems. These controls do not publish content.</p>
    <form method="get" action="<?= e(url('admin/treatments/operations')) ?>" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-3"><label for="operations-system" class="form-label">Treatment system</label><select id="operations-system" name="treatment_system_id" class="form-select"><option value="">All permitted systems</option><?php foreach ($systems as $system): ?><option value="<?= (int)$system['id'] ?>" <?= ($filters['treatment_system_id']??null)===$system['id'] ? 'selected' : '' ?>><?= e($system['name_en']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label for="operations-disease" class="form-label">Disease ID</label><input id="operations-disease" type="number" min="1" name="disease_id" class="form-control" value="<?= e((string)($filters['disease_id']??'')) ?>"></div>
            <?php foreach (['review_state'=>TreatmentGovernanceRules::REVIEW,'publication_status'=>TreatmentGovernanceRules::PUBLICATION] as $field=>$values): ?><div class="col-md-3"><label for="operations-<?= e($field) ?>" class="form-label"><?= $field==='review_state' ? 'Review state' : 'Publication state' ?></label><select id="operations-<?= e($field) ?>" name="<?= e($field) ?>" class="form-select"><option value="">All states</option><?php foreach ($values as $value): ?><option value="<?= e($value) ?>" <?= ($filters[$field]??null)===$value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></div><?php endforeach; ?>
        </div><div class="mt-3"><button class="btn btn-outline-primary">Filter protocols</button></div>
    </form>
    <div class="table-responsive"><table class="table table-striped"><caption>Protocols in your permitted systems</caption><thead><tr><th>Protocol</th><th>Disease / system</th><th>Review</th><th>Publication</th><th>Revision</th></tr></thead><tbody>
        <?php foreach ($protocols as $protocol): ?><tr><td><a href="<?= e(url('admin/treatments/'.$protocol['id'].'/operations')) ?>">#<?= (int)$protocol['id'] ?> <?= e($protocol['title_en']??$protocol['title_hi']??'Untitled') ?></a></td><td><?= e($protocol['disease_en']) ?> / <?= e($protocol['system_name']) ?></td><td><?= e($protocol['review_state']) ?></td><td><?= e($protocol['publication_status']) ?></td><td><?= (int)$protocol['content_revision'] ?></td></tr><?php endforeach; ?>
        <?php if (!$protocols): ?><tr><td colspan="5">No matching protocols in your permitted systems.</td></tr><?php endif; ?>
    </tbody></table></div>
    <nav aria-label="Operations pages" class="d-flex gap-2"><?php if ($page>1): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/operations').'?'.http_build_query($filters+['page'=>$page-1])) ?>">Previous page</a><?php endif; ?><?php if ($more): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/operations').'?'.http_build_query($filters+['page'=>$page+1])) ?>">Next page</a><?php endif; ?></nav>
</div>

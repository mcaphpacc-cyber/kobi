<?php
use App\Services\TreatmentGovernanceRules;
$reviewProtocol=$reviewContent['protocol'];
$reviewSources=array_column($reviewContent['sources']??[],null,'id');
$includedItems=[]; $excludedItems=[];
foreach ($reviewContent['items']??[] as $reviewItem) { if ((int)$reviewItem['is_included']===1) { $includedItems[]=$reviewItem; } else { $excludedItems[]=$reviewItem; } }
$excludedItemIds=array_map('intval',array_column($excludedItems,'id'));
?>
<div class="card card-body mb-3">
    <h3 class="h5">Protocol content — revision <?= (int)$reviewProtocol['content_revision'] ?></h3>
    <p>Risk eligibility for public publication: <strong><?= e((string)($reviewProtocol['risk_eligibility']??'not_assessed')) ?></strong> — separate from evidence and recommendation.</p>
    <dl class="row"><?php foreach (array_diff(TreatmentGovernanceRules::PROTOCOL_CONTENT,['disease_id','treatment_system_id']) as $field): ?><dt class="col-md-3"><?= e(ucfirst(str_replace('_',' ',$field))) ?></dt><dd class="col-md-9 text-break"><?= nl2br(e((string)($reviewProtocol[$field]??'—'))) ?></dd><?php endforeach; ?></dl>
</div>
<h3 class="h5">Included items assessed by this review</h3>
<?php foreach ($includedItems as $reviewItem): ?><div class="card card-body mb-3"><h4 class="h6">Item #<?= (int)$reviewItem['id'] ?> — included</h4><dl class="row"><?php foreach (array_diff(TreatmentGovernanceRules::ITEM_CONTENT,['is_included']) as $field): ?><dt class="col-md-3"><?= e(ucfirst(str_replace('_',' ',$field))) ?></dt><dd class="col-md-9 text-break"><?= nl2br(e((string)($reviewItem[$field]??'—'))) ?></dd><?php endforeach; ?></dl></div><?php endforeach; ?>
<?php if (!$includedItems): ?><p>No items are included. Approval is blocked.</p><?php endif; ?>
<?php if ($excludedItems): ?><details class="mb-3"><summary>Excluded legacy/unused items — not approved for use (<?= count($excludedItems) ?>)</summary><ul><?php foreach ($excludedItems as $reviewItem): ?><li>#<?= (int)$reviewItem['id'] ?> <?= e((string)($reviewItem['title_en']??'Untitled')) ?> — excluded</li><?php endforeach; ?></ul></details><?php endif; ?>
<h3 class="h5">Explicit claim support</h3>
<?php foreach (['protocol_sources'=>'Protocol overview','item_sources'=>'Item'] as $group=>$label): ?>
    <?php foreach ($reviewContent[$group]??[] as $ref):
        $reviewSource=$reviewSources[(int)$ref['source_id']]??[];
        $liveSource=$liveSources[(int)$ref['source_id']]??[];
        $safeUrl=is_string($reviewSource['url']??null) && filter_var($reviewSource['url'],FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($reviewSource['url'],PHP_URL_SCHEME)),['http','https'],true);
    ?>
        <div class="card card-body mb-3"><h4 class="h6"><?= e($label) ?><?= $group==='item_sources' ? ' #'.(int)$ref['item_id'] : '' ?> → Source #<?= (int)$ref['source_id'] ?></h4>
            <?php if ($group==='item_sources' && in_array((int)$ref['item_id'],$excludedItemIds,true)): ?><p class="text-muted">Excluded item reference — not approval support for an included item.</p><?php endif; ?>
            <p><strong>Current provenance status: <?= e((string)($liveSource['status']??'Unavailable')) ?></strong>. Provenance verification does not establish efficacy.</p>
            <dl class="row"><dt class="col-md-3">Claim scope</dt><dd class="col-md-9 text-break"><?= nl2br(e((string)($ref['claim_scope']??''))) ?></dd><dt class="col-md-3">Locator</dt><dd class="col-md-9 text-break"><?= e((string)($ref['locator']??'—')) ?></dd>
            <?php foreach (TreatmentGovernanceRules::SOURCE_CONTENT as $field): if ($field==='url') { continue; } ?><dt class="col-md-3"><?= e(ucfirst(str_replace('_',' ',$field))) ?></dt><dd class="col-md-9 text-break"><?= nl2br(e((string)($reviewSource[$field]??'—'))) ?></dd><?php endforeach; ?>
            <dt class="col-md-3">URL</dt><dd class="col-md-9 text-break"><?php if ($safeUrl): ?><a href="<?= e($reviewSource['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($reviewSource['url']) ?></a><?php else: ?><?= e((string)($reviewSource['url']??'—')) ?><?php endif; ?></dd></dl>
            <a href="<?= e(url('admin/treatment-sources/'.$protocol['treatment_system_id'].'/'.$ref['source_id'])) ?>">Inspect current source record / explicit verification</a>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>

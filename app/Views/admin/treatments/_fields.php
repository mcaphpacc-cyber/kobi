<?php
use App\Services\TreatmentGovernanceRules;
foreach ($fieldNames as $fieldName):
    $fieldId=preg_replace('/[^a-zA-Z0-9_-]/','-',$fieldPrefix.'-'.$fieldName);
    $value=$fieldValues[$fieldName]??null;
    $options=match($fieldName) {
        'evidence_classification'=>TreatmentGovernanceRules::EVIDENCE,
        'content_purpose'=>TreatmentGovernanceRules::PURPOSE,
        'item_type'=>TreatmentGovernanceRules::ITEM_TYPES,
        'is_included'=>['0'=>'Excluded','1'=>'Included'],
        default=>null,
    };
?>
<div class="col-md-6 mb-3">
    <label class="form-label" for="<?= e($fieldId) ?>"><?= e(ucfirst(str_replace('_',' ',$fieldName))) ?></label>
    <?php if ($options!==null): ?>
        <select class="form-select" name="<?= e($fieldPrefix.'['.$fieldName.']') ?>" id="<?= e($fieldId) ?>">
            <?php if (in_array($fieldName,['content_purpose','item_type'],true)): ?><option value="">Unassigned</option><?php endif; ?>
            <?php foreach ($options as $key=>$option): $optionValue=$fieldName==='is_included' ? (string)$key : $option; ?><option value="<?= e($optionValue) ?>" <?= (string)$value===$optionValue ? 'selected' : '' ?>><?= e($fieldName==='is_included' ? $option : str_replace('_',' ',$option)) ?></option><?php endforeach; ?>
        </select>
    <?php elseif ($fieldName==='sort_order' || str_starts_with($fieldName,'title_')): ?>
        <input class="form-control" type="<?= $fieldName==='sort_order' ? 'number' : 'text' ?>" <?= $fieldName==='sort_order' ? 'min="0" step="1"' : 'maxlength="255"' ?> id="<?= e($fieldId) ?>" name="<?= e($fieldPrefix.'['.$fieldName.']') ?>" value="<?= e((string)($value??'')) ?>">
    <?php else: ?>
        <textarea class="form-control" rows="3" lang="<?= str_ends_with($fieldName,'_hi') ? 'hi' : 'en' ?>" id="<?= e($fieldId) ?>" name="<?= e($fieldPrefix.'['.$fieldName.']') ?>"><?= e($value??'') ?></textarea>
    <?php endif; ?>
</div>
<?php endforeach; ?>

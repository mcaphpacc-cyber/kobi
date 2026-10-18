<?php
declare(strict_types=1);

// Phase 3.18 stop-condition reproducer, not a draft persistence implementation.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('MEDICINE_POLICY_LIBRARY', true);
require __DIR__ . '/medicine-policy.php';

$selected = ['15'=>'Acetazolamide', '54'=>'Allopurinol', '319'=>'Carbamazepine', '481'=>'Colchicine', '2064'=>'Topiramate'];
$package = json_decode(file_get_contents(dirname(__DIR__) . '/docs/MEDICINE_PHASE_3_17_EVIDENCE_PACKAGE.json'), true, 64, JSON_THROW_ON_ERROR);

medicineTest('retained source packages retain exact scope, bytes and unappraised unbound state', function () use ($selected, $package): void {
    medicineCheck(array_column($package['packages'], 'substance_id') === array_map('strval', array_keys($selected)));
    foreach ($package['packages'] as $item) {
        medicineCheck($selected[$item['substance_id']] === $item['stored_name']);
        medicineCheck($item['identity_appraised'] === false);
        foreach (['target_id','revision','snapshot_json','snapshot_hash'] as $field) { medicineCheck($item['binding'][$field] === null); }
        foreach ($item['sources'] as $source) {
            medicineCheck(hash_file('sha256', dirname(__DIR__) . '/' . $source['raw_file']) === $source['source_sha256']);
            medicineCheck($source['proposed_evidence']['source_sha256'] === $source['source_sha256']);
        }
    }
});

foreach ($selected as $id => $name) {
    medicineTest('disabled intake denies draft creation for ' . $id, function () use ($id): void {
        [$r,$s,$actor,$auth,$elig,$identity,$policy] = medicineV1Fixture();
        // Synthetic grants are fixture-only. A disabled intake must still reject them.
        $policy->configuration['pilot_enabled'] = false;
        $before = serialize([$r->inner->data,$r->eventRows,$r->assessmentRows,$r->pilotRows]);
        medicineReject(fn() => $identity->registerChemicalReference(['substance_id'=>(string)$id]), 403);
        medicineReject(fn() => $identity->createMedicine(['ingredients'=>[['substance_id'=>(string)$id]]]), 403);
        medicineCheck($before === serialize([$r->inner->data,$r->eventRows,$r->assessmentRows,$r->pilotRows]));
    });
    medicineTest('candidate chemical reference cannot become unapproved composition for ' . $id, function () use ($id, $name): void {
        [$r,$s,$actor,$auth,$elig,$identity,$policy] = medicineV1Fixture();
        // Enable intake ONLY in memory to expose the second independent gate.
        // "unambiguous" is a synthetic fixture requirement, not a scientific attestation.
        $policy->configuration['pilot_substances'] = [['substance_id'=>(string)$id,'unambiguous'=>true,'representative_reference'=>'SYNTHETIC_ONLY']];
        $r->inner->data['substances'][(string)$id] = ['substance_id'=>(string)$id,'canonical_name'=>$name,'identity_status'=>'CANDIDATE','identity_type'=>'UNKNOWN','is_active'=>1];
        $ref = $identity->registerChemicalReference(['substance_id'=>(string)$id]);
        medicineCheck($ref['status'] === 'candidate');
        $before = serialize([$r->inner->data,$r->eventRows,$r->assessmentRows,$r->pilotRows]);
        medicineReject(fn() => $identity->createMedicine(['ingredients'=>[['substance_id'=>(string)$id]],'display_name'=>$name]), 409);
        medicineReject(fn() => $elig->requireCurrent('chemical', $ref['chemical_ref_id']), 409);
        medicineCheck($before === serialize([$r->inner->data,$r->eventRows,$r->assessmentRows,$r->pilotRows]));
        medicineCheck($r->inner->data['identities'] === [] && $r->inner->data['presentations'] === []);
        medicineCheck($r->latest('chemical', $ref['chemical_ref_id'])['action'] === 'create');
    });
}
$failures = array_values(array_filter($medicineCases, fn($case) => $case['status'] === 'FAIL'));
echo json_encode(['status'=>'STOP_CONDITION_REPRODUCER_ONLY','cases'=>count($medicineCases),'assertions'=>$medicineAssertions,'failures'=>$failures,'database_connections'=>0], JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR) . PHP_EOL;
exit($failures ? 1 : 0);

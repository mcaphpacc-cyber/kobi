<?php
declare(strict_types=1);
namespace App\Services;

/** Pure preparation facts; never a canonical chemical adapter or eligibility decision. */
final class MedicineProvisionalIdentity
{
    public const PACKAGE_SHA256 = '3fc38ca47fa9ace4bd8a9963854e120425909565b2a91e2c8c4ac763da454086';
    private const CANDIDATES = ['15'=>'Acetazolamide','54'=>'Allopurinol','319'=>'Carbamazepine','481'=>'Colchicine','2064'=>'Topiramate'];

    public function candidate(string $id): string
    {
        return self::CANDIDATES[$id] ?? throw new MedicinePersistenceException('Medicine provisional candidate is outside the selected scope.', 403);
    }

    public function package(string $id): array
    {
        $this->candidate($id);
        $root = dirname(__DIR__, 2);
        $bytes = @file_get_contents($root . '/docs/MEDICINE_PHASE_3_17_EVIDENCE_PACKAGE.json');
        if ($bytes === false || !hash_equals(self::PACKAGE_SHA256, hash('sha256', $bytes))) {
            throw new MedicinePersistenceException('Medicine preparation evidence package has changed.', 409);
        }
        $manifest = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
        foreach ($manifest['packages'] as $package) {
            if ($package['substance_id'] !== $id) { continue; }
            if ($package['identity_appraised'] !== false || $package['stored_name'] !== $this->candidate($id) || count($package['sources']) !== 2) {
                throw new MedicinePersistenceException('Medicine preparation evidence is invalid.', 409);
            }
            foreach ($package['sources'] as $index => $source) {
                // Fixed deployment files, never a request-controlled path or URL.
                $file = $root . '/storage/logs/medicine317-preparation/' . $id . ($index === 0 ? '-pubchem.json' : '-dailymed.xml');
                $captured = @file_get_contents($file);
                if ($captured === false || !hash_equals($source['source_sha256'], hash('sha256', $captured))
                    || $source['proposed_evidence']['source_sha256'] !== $source['source_sha256']) {
                    throw new MedicinePersistenceException('Medicine captured evidence has changed.', 409);
                }
            }
            return $package;
        }
        throw new MedicinePersistenceException('Medicine preparation evidence is unavailable.', 409);
    }

    public function facts(string $id, array $registry, array $package): array
    {
        return [
            'domain'=>'provisional_identity', 'target'=>'provisional:S:' . $id,
            'registry'=>$registry, 'package_sha256'=>self::PACKAGE_SHA256,
            'proposal'=>['canonical_name'=>$this->candidate($id), 'category'=>'proposed_single_ingredient',
                'composition'=>(new MedicineCanonicalizer())->composition([['tag'=>'S','id'=>$id]])['key'],
                'ingredient_relationship'=>'PROPOSED_MAPPING_REQUIRES_REVIEW',
                'chemical_form'=>null, 'stereochemistry'=>null, 'chemical_form_status'=>'PENDING_REVIEW',
                'stereochemistry_status'=>'PENDING_REVIEW', 'mapping_status'=>'PENDING_REVIEW'],
            'unresolved_questions'=>[$package['chemical_form_review'], 'Exact mapping to the UNKNOWN registry referent and single-ingredient identity require independent professional appraisal.'],
            'sources'=>$package['sources'], 'evidence_status'=>'NOT_APPRAISED', 'review_status'=>'NOT_ASSIGNED',
            'reviewer_id'=>null, 'approval_status'=>'NONE', 'canonical_identity_id'=>null, 'chemical_ref_id'=>null,
        ];
    }

    public function evidence(array $facts, int $revision, string $snapshotSha256): array
    {
        return array_map(static fn(array $source): array => [
            'source'=>$source['proposed_evidence'], 'proposed_tier'=>$source['proposed_tier'],
            'tier_status'=>$source['tier_status'], 'retrieved_at'=>$source['accessed_at_utc'],
            'appraisal_status'=>'NOT_APPRAISED', 'scope'=>'provisional_identity',
            'target'=>$facts['target'], 'revision'=>$revision, 'snapshot_sha256'=>$snapshotSha256,
        ], $facts['sources']);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HealthProfileRepository;
use App\Repositories\MedicineRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDOException;
use Throwable;

/** Internal commands only; no existing endpoint is connected to this service. */
class MedicineIdentityService
{
    private const MEDICATION_FIELDS = ['id', 'health_profile_id', 'brand_name', 'generic_name', 'strength', 'dose', 'frequency', 'route', 'status', 'started_on', 'stopped_on', 'prescribed_by', 'notes'];

    public function __construct(
        private MedicineRepository $repository,
        private MedicineCanonicalizer $canonicalizer,
        private AuthService $auth,
        private HealthProfileRepository $profiles,
        private MedicineAuthorizationService $authority
    ) {}

    public function registerChemicalReference(array $input): array
    {
        return $this->command(function () use ($input): array {
            $actor = $this->catalogActor('register_reference');
            $references = $this->inputReferences([$input]); $reference = $references[0];
            $this->authority->preparePilot('chemical',$references);
            $existing = $this->repository->reference($reference['tag'], $reference['id'], true);
            $this->lockRegistry($references);
            if ($existing !== null) {
                $this->authority->requirePilotTarget('chemical',(string)$existing['chemical_ref_id']);
                $this->state($existing, false);
                return ['chemical_ref_id' => $this->canonicalizer->id($existing['chemical_ref_id']), 'status' => $existing['identity_status'], 'created' => false];
            }
            $now = $this->now();
            $id = $this->repository->insertReference(['substance_id' => $reference['tag'] === 'S' ? $reference['id'] : null, 'form_id' => $reference['tag'] === 'F' ? $reference['id'] : null, 'created_by' => $actor, 'created_at' => $now, 'updated_at' => $now]);
            $this->authority->recordCreation('chemical',$id,$actor);
            return ['chemical_ref_id' => $id, 'status' => 'candidate', 'created' => true];
        });
    }

    /** Optional presentation is part of the SAME transaction as a newly created composition. */
    public function createMedicine(array $input): array
    {
        return $this->command(function () use ($input): array {
            $actor = $this->catalogActor('create_identity');
            $this->canonicalizer->fields($input, ['ingredients', 'display_name', 'presentation'], ['ingredients']);
            if (!is_array($input['ingredients'])) { throw new MedicinePersistenceException('Invalid medicine ingredients.', 422); }
            $references=$this->inputReferences($input['ingredients']);
            $this->authority->preparePilot('composition',$references);
            $composition = $this->resolve($references);
            $label = $this->canonicalizer->text($input['display_name'] ?? null, 500);
            $existing = $this->repository->identityByHash($composition['hash']);
            $created = $existing === null;
            if ($created) {
                $now = $this->now();
                $id = $this->repository->insertIdentity(['composition_hash' => $composition['hash'], 'composition_key' => $composition['key'], 'display_name' => $label, 'created_by' => $actor, 'created_at' => $now, 'updated_at' => $now]);
                foreach ($composition['references'] as $index => $reference) {
                    $this->repository->insertIngredient(['medicine_identity_id' => $id, 'chemical_ref_id' => $reference['chemical_ref_id'], 'display_order' => $index + 1, 'created_at' => $now]);
                }
            } else {
                $id = $this->canonicalizer->id($existing['medicine_identity_id']);
                $this->authority->requirePilotTarget('composition',$id);
                if ($existing['composition_key'] !== $composition['key']) { throw new MedicinePersistenceException('Medicine identity conflict.', 409); }
            }
            $identity = $this->loadIdentity($id, false);
            if($created){$this->authority->recordCreation('composition',$id,$actor);}
            $result = ['medicine_identity_id' => $id, 'status' => $identity['row']['identity_status'], 'created' => $created];
            if (array_key_exists('presentation', $input)) {
                if (!is_array($input['presentation'])) { throw new MedicinePersistenceException('Invalid medicine presentation.', 422); }
                $this->authority->requireCatalog($actor, 'create_presentation');
                $result['presentation'] = $this->persistPresentation($identity, $input['presentation'], $actor);
            }
            return $result;
        });
    }

    public function createPresentation(mixed $identityId, array $input): array
    {
        return $this->command(function () use ($identityId, $input): array {
            $actor = $this->catalogActor('create_presentation');
            return $this->persistPresentation($this->loadIdentity($this->canonicalizer->id($identityId), false), $input, $actor);
        });
    }

    /** Scoped private snapshot/token for future integration; original text is never rewritten. */
    public function medicationAssociation(mixed $profileId, mixed $medicationId): array
    {
        return $this->command(function () use ($profileId, $medicationId): array {
            $profile = (int) $this->canonicalizer->id($profileId); $medication = (int) $this->canonicalizer->id($medicationId);
            $this->privateActor($profile, false);
            $record = $this->privateMedication($profile, $medication);
            $link = $this->repository->link($medication, true);
            $selections = $this->repository->selections($medication);
            return ['record_digest' => $this->recordDigest($record, $link, $selections), 'link_revision' => $link === null ? 0 : (int) $link['link_revision'],
                'association_mode' => $link['association_mode'] ?? 'text', 'medicine_identity_id' => $link['medicine_identity_id'] ?? null,
                'medicine_presentation_id' => $link['medicine_presentation_id'] ?? null, 'selected_ingredients' => $selections];
        });
    }

    public function setMedicationAssociation(mixed $profileId, mixed $medicationId, array $input): array
    {
        return $this->command(function () use ($profileId, $medicationId, $input): array {
            $profile = (int) $this->canonicalizer->id($profileId); $medication = (int) $this->canonicalizer->id($medicationId);
            $actor = $this->privateActor($profile, true);
            $this->canonicalizer->fields($input, ['association_mode', 'medicine_identity_id', 'medicine_presentation_id', 'ingredients', 'expected_record_digest', 'expected_link_revision'], ['association_mode', 'expected_record_digest', 'expected_link_revision']);
            $mode = $input['association_mode']; $identity = null; $presentation = null; $references = [];
            if ($mode === 'composition') {
                if (array_key_exists('medicine_presentation_id', $input) || array_key_exists('ingredients', $input)) { throw new MedicinePersistenceException('Invalid medicine association.', 422); }
                $identity = $this->loadIdentity($this->canonicalizer->id($input['medicine_identity_id'] ?? null), true)['row']['medicine_identity_id'];
            } elseif ($mode === 'presentation') {
                if (array_key_exists('medicine_identity_id', $input) || array_key_exists('ingredients', $input)) { throw new MedicinePersistenceException('Invalid medicine association.', 422); }
                $presentation = $this->loadPresentation($this->canonicalizer->id($input['medicine_presentation_id'] ?? null), true)['row']['medicine_presentation_id'];
            } elseif ($mode === 'ingredients') {
                if (array_key_exists('medicine_identity_id', $input) || array_key_exists('medicine_presentation_id', $input) || !is_array($input['ingredients'] ?? null)) { throw new MedicinePersistenceException('Invalid medicine association.', 422); }
                $references = $this->resolve($this->inputReferences($input['ingredients']))['references'];
            } elseif ($mode === 'text') {
                if (array_key_exists('medicine_identity_id', $input) || array_key_exists('medicine_presentation_id', $input) || array_key_exists('ingredients', $input)) { throw new MedicinePersistenceException('Invalid medicine association.', 422); }
            } else { throw new MedicinePersistenceException('Invalid medicine association.', 422); }
            $record = $this->privateMedication($profile, $medication);
            $link = $this->repository->link($medication, true);
            $revision = $link === null ? 0 : (int) $link['link_revision'];
            if (!is_string($input['expected_record_digest']) || !preg_match('/\A[a-f0-9]{64}\z/D', $input['expected_record_digest'])
                || !hash_equals($this->recordDigest($record, $link, $this->repository->selections($medication)), $input['expected_record_digest'])
                || (!is_int($input['expected_link_revision']) && !is_string($input['expected_link_revision']))
                || !preg_match('/\A(?:0|[1-9][0-9]{0,9})\z/D', (string) $input['expected_link_revision'])
                || (string) $revision !== (string) $input['expected_link_revision'] || $revision >= 4294967295) {
                throw new MedicinePersistenceException('Medicine association is stale. Reload the record.', 409);
            }
            $this->repository->clearSelections($medication);
            if ($mode === 'text') { $this->repository->deleteLink($medication); return ['association_mode' => 'text', 'link_revision' => 0]; }
            $now = $this->now();
            $this->repository->saveLink(['health_medicine_id' => $medication, 'association_mode' => $mode, 'medicine_identity_id' => $identity, 'medicine_presentation_id' => $presentation, 'link_revision' => $revision + 1, 'created_by' => $link['created_by'] ?? $actor, 'updated_by' => $actor, 'created_at' => $link['created_at'] ?? $now, 'updated_at' => $now], $link !== null);
            foreach ($references as $index => $reference) {
                $this->repository->insertSelection(['health_medicine_id' => $medication, 'chemical_ref_id' => $reference['chemical_ref_id'], 'association_mode' => 'ingredients', 'display_order' => $index + 1, 'observed_name' => null, 'created_at' => $now]);
            }
            return ['association_mode' => $mode, 'link_revision' => $revision + 1];
        });
    }

    private function inputReferences(array $ingredients): array
    {
        if (!array_is_list($ingredients) || count($ingredients) < 1 || count($ingredients) > 16) { throw new MedicinePersistenceException('Select between one and sixteen ingredients.', 422); }
        $references = [];
        foreach ($ingredients as $ingredient) {
            if (!is_array($ingredient)) { throw new MedicinePersistenceException('Invalid medicine ingredient.', 422); }
            $this->canonicalizer->fields($ingredient, ['substance_id', 'form_id']);
            if (array_key_exists('substance_id', $ingredient) === array_key_exists('form_id', $ingredient)) { throw new MedicinePersistenceException('Select exactly one chemical reference per ingredient.', 422); }
            $tag = array_key_exists('substance_id', $ingredient) ? 'S' : 'F';
            $references[] = ['tag' => $tag, 'id' => $this->canonicalizer->id($ingredient[$tag === 'S' ? 'substance_id' : 'form_id'])];
        }
        return $this->canonicalizer->composition($references)['references'];
    }

    private function resolve(array $references): array
    {
        foreach ($references as &$reference) {
            $row = $this->repository->reference($reference['tag'], $reference['id'], true);
            if ($row === null) { throw new MedicinePersistenceException('Chemical reference is unavailable or unverified.', 422); }
            $this->state($row, true);
            $reference['chemical_ref_id'] = $this->canonicalizer->id($row['chemical_ref_id']);
            $this->authority->requireCurrent('chemical', $reference['chemical_ref_id']);
        }
        unset($reference);
        $this->lockRegistry($references);
        return $this->canonicalizer->composition($references);
    }

    private function lockRegistry(array $references): void
    {
        $substances = []; $forms = [];
        foreach ($references as $reference) {
            if ($reference['tag'] === 'S') { $substances[$reference['id']] = $reference['id']; }
            else {
                $form = $this->repository->form($reference['id']);
                if ($form === null) { throw new MedicinePersistenceException('Chemical reference is unavailable.', 422); }
                $parent = $this->canonicalizer->id($form['substance_id']);
                $substances[$parent] = $parent; $forms[$reference['id']] = ['id' => $reference['id'], 'parent' => $parent];
            }
        }
        usort($substances, static fn(string $a, string $b): int => strlen($a) <=> strlen($b) ?: strcmp($a, $b));
        foreach ($substances as $id) {
            $row = $this->repository->substance($id, true);
            if ($row === null || (string) $row['is_active'] !== '1') { throw new MedicinePersistenceException('Substance is unavailable or inactive.', 422); }
        }
        uasort($forms, static fn(array $a, array $b): int => strlen($a['id']) <=> strlen($b['id']) ?: strcmp($a['id'], $b['id']));
        foreach ($forms as $form) {
            $row = $this->repository->form($form['id'], true);
            if ($row === null || (string) $row['is_active'] !== '1' || $this->canonicalizer->id($row['substance_id']) !== $form['parent']) {
                throw new MedicinePersistenceException('Chemical form is unavailable or changed.', 422);
            }
        }
    }

    private function loadIdentity(string $id, bool $verified): array
    {
        $members = $this->repository->ingredients($id);
        $references = [];
        foreach ($members as $member) {
            if (($member['substance_id'] === null) === ($member['form_id'] === null)) { throw new MedicinePersistenceException('Medicine composition is inconsistent.', 409); }
            $references[] = ['tag' => $member['substance_id'] !== null ? 'S' : 'F', 'id' => $this->canonicalizer->id($member['substance_id'] ?? $member['form_id'])];
        }
        $composition = $this->resolve($this->canonicalizer->composition($references)['references']);
        $row = $this->repository->identity($id, true);
        if ($row === null) { throw new MedicinePersistenceException('Medicine identity is unavailable.', 422); }
        $this->state($row, $verified);
        if ($verified) { $this->authority->requireCurrent('composition', $id); }
        $current = $this->repository->ingredients($id, true);
        $byRef = []; foreach ($current as $member) { $byRef[$this->canonicalizer->id($member['chemical_ref_id'])] = $member; }
        if (count($current) !== count($composition['references']) || count($byRef) !== count($current) || (string) $row['key_version'] !== '1'
            || $row['composition_key'] !== $composition['key'] || !hash_equals($composition['hash'], $row['composition_hash'])) {
            throw new MedicinePersistenceException('Medicine composition is inconsistent.', 409);
        }
        foreach ($composition['references'] as &$reference) {
            $member = $byRef[$reference['chemical_ref_id']] ?? null;
            if ($member === null || $this->canonicalizer->id($member['medicine_identity_id']) !== $id
                || $this->canonicalizer->id($member['substance_id'] ?? $member['form_id']) !== $reference['id']) { throw new MedicinePersistenceException('Medicine composition is inconsistent.', 409); }
            $reference['medicine_ingredient_id'] = $this->canonicalizer->id($member['medicine_ingredient_id']);
        }
        unset($reference);
        return ['row' => $row, 'members' => $composition['references']];
    }

    private function persistPresentation(array $identity, array $input, int $actor): array
    {
        $this->authority->preparePilot('presentation',$identity['members']);
        $id = $this->canonicalizer->id($identity['row']['medicine_identity_id']);
        $canonical = $this->canonicalizer->presentation($id, $identity['members'], $input);
        $existing = $this->repository->presentationByHash($canonical['hash']);
        if ($existing !== null) {
            $this->authority->requirePilotTarget('presentation',(string)$existing['medicine_presentation_id']);
            if ($existing['presentation_key'] !== $canonical['key']) { throw new MedicinePersistenceException('Medicine presentation conflict.', 409); }
            $loaded = $this->loadPresentation($this->canonicalizer->id($existing['medicine_presentation_id']), false, $existing);
            return ['medicine_presentation_id' => (string) $existing['medicine_presentation_id'], 'status' => $loaded['row']['identity_status'], 'created' => false];
        }
        $now = $this->now();
        $presentation = $this->repository->insertPresentation(['medicine_identity_id' => $id, 'presentation_hash' => $canonical['hash'], 'presentation_key' => $canonical['key'], 'dosage_form_code' => $canonical['dosage_form_code'], 'route_code' => $canonical['route_code'], 'release_code' => $canonical['release_code'], 'display_name' => $canonical['display_name'], 'created_by' => $actor, 'created_at' => $now, 'updated_at' => $now]);
        foreach ($canonical['rows'] as $strength) { $this->repository->insertStrength(['medicine_presentation_id' => $presentation, 'medicine_identity_id' => $id, 'created_at' => $now] + $strength); }
        $this->loadPresentation($presentation, false);
        $this->authority->recordCreation('presentation',$presentation,$actor);
        return ['medicine_presentation_id' => $presentation, 'status' => 'candidate', 'created' => true];
    }

    private function loadPresentation(string $id, bool $verified, ?array $currentHint = null): array
    {
        // Hash lookup uses a current locking read: retain its row when a concurrent
        // winner was committed after our repeatable-read snapshot was established.
        $initial = $currentHint ?? $this->repository->presentation($id);
        if ($initial === null) { throw new MedicinePersistenceException('Medicine presentation is unavailable.', 422); }
        $identity = $this->loadIdentity($this->canonicalizer->id($initial['medicine_identity_id']), $verified);
        $row = $this->repository->presentation($id, true);
        if ($row === null || (string) $row['medicine_identity_id'] !== (string) $initial['medicine_identity_id']) { throw new MedicinePersistenceException('Medicine presentation changed.', 409); }
        $this->state($row, $verified);
        if ($verified) { $this->authority->requireCurrent('presentation', $id); }
        $byIngredient = []; foreach ($identity['members'] as $member) { $byIngredient[$member['medicine_ingredient_id']] = $member['chemical_ref_id']; }
        $strengths = [];
        foreach ($this->repository->strengths($id, true) as $strength) {
            if ((string) $strength['medicine_identity_id'] !== (string) $row['medicine_identity_id'] || !isset($byIngredient[$strength['medicine_ingredient_id']])) { throw new MedicinePersistenceException('Medicine strength vector is inconsistent.', 409); }
            $strengths[] = ['chemical_ref_id' => $byIngredient[$strength['medicine_ingredient_id']], 'ratio_numerator' => $strength['ratio_numerator'], 'ratio_denominator' => $strength['ratio_denominator'], 'numerator_unit_code' => $strength['numerator_unit_code'], 'denominator_unit_code' => $strength['denominator_unit_code'], 'original_strength_text' => $strength['original_strength_text']];
            $stored = end($strengths); unset($stored['chemical_ref_id']);
            $normalized = $this->canonicalizer->strength($stored);
            if ($normalized['ratio_numerator'] !== $strength['ratio_numerator'] || $normalized['ratio_denominator'] !== $strength['ratio_denominator']) { throw new MedicinePersistenceException('Medicine strength vector is inconsistent.', 409); }
        }
        $canonical = $this->canonicalizer->presentation((string) $row['medicine_identity_id'], $identity['members'], ['dosage_form_code' => $row['dosage_form_code'], 'route_code' => $row['route_code'], 'release_code' => $row['release_code'], 'strengths' => $strengths]);
        if ((string) $row['key_version'] !== '1' || $canonical['key'] !== $row['presentation_key'] || !hash_equals($canonical['hash'], $row['presentation_hash'])) { throw new MedicinePersistenceException('Medicine presentation is inconsistent.', 409); }
        return ['row' => $row];
    }

    private function state(array $row, bool $verified): void
    {
        if (!in_array($row['identity_status'] ?? null, ['candidate', 'verified'], true) || (int) ($row['metadata_revision'] ?? 0) < 1
            || ($verified && $row['identity_status'] !== 'verified')) { throw new MedicinePersistenceException('Medicine reference is unavailable or unverified.', 409); }
        if ($row['identity_status'] === 'verified') {
            $time = is_string($row['verified_at'] ?? null) ? DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u', $row['verified_at'], new DateTimeZone('UTC')) : false;
            if (empty($row['verified_by']) || !$time || $time->format('Y-m-d H:i:s.u') !== $row['verified_at'] || $this->canonicalizer->text($row['verification_reference'] ?? null, 16383) === null) {
                throw new MedicinePersistenceException('Medicine verification is inconsistent.', 409);
            }
            $this->canonicalizer->id($row['verified_by']);
        }
    }

    private function actor(): int
    {
        $id = $this->auth->id();
        if ($id === null || $id < 1 || ($this->repository->actor($id, true)['status'] ?? null) !== 'active') { throw new MedicinePersistenceException('Medicine access denied.', 403); }
        return $id;
    }

    private function catalogActor(string $action): int
    {
        $actor = $this->actor(); $this->authority->requireCatalog($actor, $action); return $actor;
    }

    private function privateActor(int $profile, bool $write): int
    {
        $actor = $this->actor(); $this->repository->lockProfile($profile, $actor);
        $accessible = $this->profiles->findAccessible($profile, $actor);
        if ($accessible === null || ($write && !in_array($accessible['role'] ?? null, ['owner', 'editor'], true))) { throw new MedicinePersistenceException('Medicine access denied.', 403); }
        return $actor;
    }

    private function privateMedication(int $profile, int $medication): array
    {
        $record = $this->repository->medication($medication, true);
        if ($record === null || (int) $record['health_profile_id'] !== $profile) { throw new MedicinePersistenceException('Medicine access denied.', 403); }
        return $record;
    }

    private function recordDigest(array $record, ?array $link, array $selections): string
    {
        $snapshot = []; foreach (self::MEDICATION_FIELDS as $field) { $snapshot[$field] = $record[$field] === null ? null : (string) $record[$field]; }
        // Include link state to prevent stale revision reuse after delete/recreate (ABA).
        if ($link !== null) { foreach ($link as &$value) { $value = $value === null ? null : (string) $value; } unset($value); ksort($link); }
        foreach ($selections as &$selection) { foreach ($selection as &$value) { $value = $value === null ? null : (string) $value; } unset($value); ksort($selection); } unset($selection);
        return hash('sha256', json_encode(['record' => $snapshot, 'link' => $link, 'selections' => $selections], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function now(): string { return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u'); }

    private function command(callable $operation): mixed
    {
        try { return $this->repository->transaction($operation); }
        catch (MedicinePersistenceException $error) { throw $error; }
        catch (PDOException $error) { throw new MedicinePersistenceException('Medicine storage is temporarily unavailable.', 503); }
        catch (Throwable $error) { throw new MedicinePersistenceException('Medicine operation could not be completed.', 500); }
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use PDOException;
use RuntimeException;
use Throwable;

/** Internal persistence primitives; identity and eligibility rules belong to services. */
class MedicineRepository extends BaseRepository
{
    public function transaction(callable $action): mixed
    {
        if ($this->db->inTransaction()) { throw new RuntimeException('Nested medicine transaction is unavailable.'); }
        for ($attempt = 0; ; $attempt++) {
            $this->beginTransaction();
            try { $value = $action(); $this->commit(); return $value; }
            catch (Throwable $error) {
                if ($this->db->inTransaction()) { $this->rollback(); }
                // Retry the complete command in a fresh transaction, never a stale snapshot.
                if ($error instanceof PDOException && in_array((int) ($error->errorInfo[1] ?? 0), [1213, 1062], true) && $attempt < 2) { continue; }
                throw $error;
            }
        }
    }

    public function actor(int $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT id,status FROM users WHERE id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $id]);
    }

    public function lockProfile(int $profile, int $actor): void
    {
        $this->fetch('SELECT id,status FROM health_profiles WHERE id=:id FOR UPDATE', ['id' => $profile]);
        $this->fetch('SELECT id,role,status FROM health_profile_members WHERE health_profile_id=:profile AND user_id=:actor FOR UPDATE', ['profile' => $profile, 'actor' => $actor]);
    }

    public function reference(string $tag, string $id, bool $lock = false): ?array
    {
        $column = $tag === 'S' ? 'substance_id' : 'form_id';
        return $this->fetch('SELECT * FROM kobi_medicine_chemical_refs WHERE ' . $column . '=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $id]);
    }

    public function substance(string $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT substance_id,is_active FROM kobi_substances WHERE substance_id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $id]);
    }

    public function form(string $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT form_id,substance_id,is_active FROM kobi_substance_forms WHERE form_id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $id]);
    }

    public function identity(string $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT * FROM kobi_medicine_identities WHERE medicine_identity_id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $id]);
    }

    public function identityByHash(string $hash): ?array
    {
        return $this->fetch('SELECT * FROM kobi_medicine_identities WHERE composition_hash=:hash FOR UPDATE', ['hash' => $hash]);
    }

    public function ingredients(string $identity, bool $lock = false): array
    {
        return $this->fetchAll('SELECT i.*,r.substance_id,r.form_id FROM kobi_medicine_ingredients i JOIN kobi_medicine_chemical_refs r ON r.chemical_ref_id=i.chemical_ref_id WHERE i.medicine_identity_id=:id ORDER BY i.display_order' . ($lock ? ' FOR UPDATE' : ''), ['id' => $identity]);
    }

    public function presentation(string $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT * FROM kobi_medicine_presentations WHERE medicine_presentation_id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $id]);
    }

    public function presentationByHash(string $hash): ?array
    {
        return $this->fetch('SELECT * FROM kobi_medicine_presentations WHERE presentation_hash=:hash FOR UPDATE', ['hash' => $hash]);
    }

    public function strengths(string $presentation, bool $lock = false): array
    {
        return $this->fetchAll('SELECT * FROM kobi_medicine_strengths WHERE medicine_presentation_id=:id ORDER BY medicine_ingredient_id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $presentation]);
    }

    public function insertReference(array $row): string
    {
        return $this->insert('kobi_medicine_chemical_refs', $row, ['substance_id', 'form_id', 'created_by', 'created_at', 'updated_at']);
    }

    public function insertIdentity(array $row): string
    {
        return $this->insert('kobi_medicine_identities', $row, ['composition_hash', 'composition_key', 'display_name', 'created_by', 'created_at', 'updated_at']);
    }

    public function insertIngredient(array $row): string
    {
        return $this->insert('kobi_medicine_ingredients', $row, ['medicine_identity_id', 'chemical_ref_id', 'display_order', 'created_at']);
    }

    public function insertPresentation(array $row): string
    {
        return $this->insert('kobi_medicine_presentations', $row, ['medicine_identity_id', 'presentation_hash', 'presentation_key', 'dosage_form_code', 'route_code', 'release_code', 'display_name', 'created_by', 'created_at', 'updated_at']);
    }

    public function insertStrength(array $row): void
    {
        $this->insert('kobi_medicine_strengths', $row, ['medicine_presentation_id', 'medicine_identity_id', 'medicine_ingredient_id', 'ratio_numerator', 'ratio_denominator', 'numerator_unit_code', 'denominator_unit_code', 'original_strength_text', 'created_at']);
    }

    public function medication(int $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT * FROM health_medicines WHERE id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $id]);
    }

    public function link(int $medication, bool $lock = false): ?array
    {
        return $this->fetch('SELECT * FROM health_medicine_identity_links WHERE health_medicine_id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $medication]);
    }

    public function selections(int $medication): array
    {
        return $this->fetchAll('SELECT chemical_ref_id,display_order,observed_name FROM health_medicine_selected_ingredients WHERE health_medicine_id=:id ORDER BY display_order', ['id' => $medication]);
    }

    public function clearSelections(int $medication): void
    {
        $this->execute('DELETE FROM health_medicine_selected_ingredients WHERE health_medicine_id=:id', ['id' => $medication]);
    }

    public function deleteLink(int $medication): void
    {
        $this->execute('DELETE FROM health_medicine_identity_links WHERE health_medicine_id=:id', ['id' => $medication]);
    }

    public function saveLink(array $row, bool $exists): void
    {
        $fields = ['health_medicine_id', 'association_mode', 'medicine_identity_id', 'medicine_presentation_id', 'link_revision', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        if (!$exists) { $this->insert('health_medicine_identity_links', $row, $fields); return; }
        $params = array_intersect_key($row, array_flip(['health_medicine_id', 'association_mode', 'medicine_identity_id', 'medicine_presentation_id', 'link_revision', 'updated_by', 'updated_at']));
        $this->execute('UPDATE health_medicine_identity_links SET association_mode=:association_mode,medicine_identity_id=:medicine_identity_id,medicine_presentation_id=:medicine_presentation_id,link_revision=:link_revision,updated_by=:updated_by,updated_at=:updated_at WHERE health_medicine_id=:health_medicine_id', $params);
    }

    public function insertSelection(array $row): void
    {
        $this->insert('health_medicine_selected_ingredients', $row, ['health_medicine_id', 'chemical_ref_id', 'association_mode', 'display_order', 'observed_name', 'created_at']);
    }

    private function insert(string $table, array $row, array $fields): string
    {
        if (array_diff(array_keys($row), $fields) || array_diff($fields, array_keys($row))) { throw new RuntimeException('Invalid internal medicine persistence shape.'); }
        // Table/columns are private fixed literals, never request-controlled identifiers.
        $this->execute('INSERT INTO ' . $table . ' (`' . implode('`,`', $fields) . '`) VALUES (:' . implode(',:', $fields) . ')', $row);
        return $this->lastInsertId();
    }
}

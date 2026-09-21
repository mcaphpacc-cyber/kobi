<?php

namespace App\Repositories;

class HealthInsuranceContactRepository extends BaseRepository
{
    /**
     * Get all contacts for a policy.
     */
    public function findByPolicyId(int $policyId): array
    {
        $sql = "
            SELECT
                id,
                policy_id,
                contact_type,
                organization_name,
                contact_person,
                phone,
                alternate_phone,
                email,
                website,
                address,
                notes,
                created_at,
                updated_at
            FROM health_insurance_contacts
            WHERE policy_id = :policy_id
            ORDER BY
                contact_type ASC,
                organization_name ASC,
                id ASC
        ";

        return $this->fetchAll($sql, [
            ':policy_id' => $policyId,
        ]);
    }

    /**
     * Find a contact by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                policy_id,
                contact_type,
                organization_name,
                contact_person,
                phone,
                alternate_phone,
                email,
                website,
                address,
                notes,
                created_at,
                updated_at
            FROM health_insurance_contacts
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            ':id' => $id,
        ]) ?: null;
    }

    /**
     * Create an insurance contact.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO health_insurance_contacts (
                policy_id,
                contact_type,
                organization_name,
                contact_person,
                phone,
                alternate_phone,
                email,
                website,
                address,
                notes
            ) VALUES (
                :policy_id,
                :contact_type,
                :organization_name,
                :contact_person,
                :phone,
                :alternate_phone,
                :email,
                :website,
                :address,
                :notes
            )
        ";

        $this->query($sql, [
            ':policy_id' => $data['policy_id'],
            ':contact_type' => $data['contact_type'],
            ':organization_name' => $data['organization_name'],
            ':contact_person' => $data['contact_person'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':alternate_phone' => $data['alternate_phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':website' => $data['website'] ?? null,
            ':address' => $data['address'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);

        return (int) $this->lastInsertId();
    }

    /**
     * Update an insurance contact.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE health_insurance_contacts
            SET
                contact_type = :contact_type,
                organization_name = :organization_name,
                contact_person = :contact_person,
                phone = :phone,
                alternate_phone = :alternate_phone,
                email = :email,
                website = :website,
                address = :address,
                notes = :notes
            WHERE id = :id
        ";

        return $this->execute($sql, [
            ':id' => $id,
            ':contact_type' => $data['contact_type'],
            ':organization_name' => $data['organization_name'],
            ':contact_person' => $data['contact_person'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':alternate_phone' => $data['alternate_phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':website' => $data['website'] ?? null,
            ':address' => $data['address'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Delete an insurance contact.
     */
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM health_insurance_contacts
            WHERE id = :id
        ";

        return $this->execute($sql, [
            ':id' => $id,
        ]);
    }

    /**
     * Count contacts for a policy.
     */
    public function countByPolicyId(int $policyId): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM health_insurance_contacts
            WHERE policy_id = :policy_id
        ";

        $result = $this->fetch($sql, [
            ':policy_id' => $policyId,
        ]);

        return (int) ($result['total'] ?? 0);
    }
}
<?php

namespace App\Repositories;

class HealthInsuranceMemberRepository extends BaseRepository
{
    /**
     * Get all insured members for a policy.
     */
    public function findByPolicyId(int $policyId): array
    {
        $sql = "
            SELECT
                id,
                policy_id,
                health_profile_id,
                member_name,
                relationship,
                member_id_number,
                health_card_number,
                date_of_birth,
                is_primary_insured,
                notes,
                created_at,
                updated_at
            FROM health_insurance_members
            WHERE policy_id = :policy_id
            ORDER BY
                is_primary_insured DESC,
                member_name ASC,
                id ASC
        ";

        return $this->fetchAll($sql, [
            ':policy_id' => $policyId,
        ]);
    }

    /**
     * Find a member by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                policy_id,
                health_profile_id,
                member_name,
                relationship,
                member_id_number,
                health_card_number,
                date_of_birth,
                is_primary_insured,
                notes,
                created_at,
                updated_at
            FROM health_insurance_members
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            ':id' => $id,
        ]) ?: null;
    }

    /**
     * Create an insured member.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO health_insurance_members (
                policy_id,
                health_profile_id,
                member_name,
                relationship,
                member_id_number,
                health_card_number,
                date_of_birth,
                is_primary_insured,
                notes
            ) VALUES (
                :policy_id,
                :health_profile_id,
                :member_name,
                :relationship,
                :member_id_number,
                :health_card_number,
                :date_of_birth,
                :is_primary_insured,
                :notes
            )
        ";

        $this->query($sql, [
            ':policy_id' => $data['policy_id'],
            ':health_profile_id' => $data['health_profile_id'] ?? null,
            ':member_name' => $data['member_name'],
            ':relationship' => $data['relationship'],
            ':member_id_number' => $data['member_id_number'] ?? null,
            ':health_card_number' => $data['health_card_number'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':is_primary_insured' => $data['is_primary_insured'] ?? 0,
            ':notes' => $data['notes'] ?? null,
        ]);

        return (int) $this->lastInsertId();
    }

    /**
     * Update an insured member.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE health_insurance_members
            SET
                health_profile_id = :health_profile_id,
                member_name = :member_name,
                relationship = :relationship,
                member_id_number = :member_id_number,
                health_card_number = :health_card_number,
                date_of_birth = :date_of_birth,
                is_primary_insured = :is_primary_insured,
                notes = :notes
            WHERE id = :id
        ";

        return $this->execute($sql, [
            ':id' => $id,
            ':health_profile_id' => $data['health_profile_id'] ?? null,
            ':member_name' => $data['member_name'],
            ':relationship' => $data['relationship'],
            ':member_id_number' => $data['member_id_number'] ?? null,
            ':health_card_number' => $data['health_card_number'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':is_primary_insured' => $data['is_primary_insured'] ?? 0,
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Delete an insured member.
     */
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM health_insurance_members
            WHERE id = :id
        ";

        return $this->execute($sql, [
            ':id' => $id,
        ]);
    }

    /**
     * Count members for a policy.
     */
    public function countByPolicyId(int $policyId): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM health_insurance_members
            WHERE policy_id = :policy_id
        ";

        $result = $this->fetch($sql, [
            ':policy_id' => $policyId,
        ]);

        return (int) ($result['total'] ?? 0);
    }
}
<?php

namespace App\Repositories;

class HealthConditionRepository extends BaseRepository
{
    /** The caller must authorize the profile before these API reads. */
    public function countApiByProfile(int $profileId): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS total FROM health_conditions WHERE health_profile_id = :profile', ['profile' => $profileId]);
        return (int) ($row['total'] ?? 0);
    }

    public function listApiByProfile(int $profileId, int $limit, int $offset): array
    {
        // Match the existing public API's bounded integer LIMIT/OFFSET convention.
        $limit = max(1, min(50, $limit));
        $offset = max(0, min(499950, $offset));
        return $this->fetchAll("SELECT id, condition_name, status, diagnosed_on, resolved_on
            FROM health_conditions WHERE health_profile_id = :profile
            ORDER BY CASE status WHEN 'active' THEN 1 WHEN 'chronic' THEN 2
                WHEN 'historical' THEN 3 WHEN 'resolved' THEN 4 ELSE 5 END,
                diagnosed_on DESC, id DESC LIMIT {$limit} OFFSET {$offset}", ['profile' => $profileId]);
    }

    public function findApiByIdInProfile(int $conditionId, int $profileId): ?array
    {
        return $this->fetch('SELECT id, condition_name, status, diagnosed_on, resolved_on
            FROM health_conditions WHERE id = :id AND health_profile_id = :profile LIMIT 1',
            ['id' => $conditionId, 'profile' => $profileId]);
    }

    /**
     * Get all medical conditions for a health profile.
     */
    public function findByProfile(int $healthProfileId): array
    {
        $sql = "
            SELECT
                id,
                health_profile_id,
                condition_name,
                status,
                diagnosed_on,
                resolved_on,
                doctor_hospital,
                notes,
                created_at,
                updated_at
            FROM health_conditions
            WHERE health_profile_id = :health_profile_id
            ORDER BY
                CASE status
                    WHEN 'active' THEN 1
                    WHEN 'chronic' THEN 2
                    WHEN 'historical' THEN 3
                    WHEN 'resolved' THEN 4
                    ELSE 5
                END,
                diagnosed_on DESC,
                id DESC
        ";

        return $this->fetchAll($sql, [
            'health_profile_id' => $healthProfileId,
        ]);
    }

    /**
     * Find a condition by ID within a specific health profile.
     */
    public function findById(
        int $id,
        int $healthProfileId
    ): ?array {
        $sql = "
            SELECT
                id,
                health_profile_id,
                condition_name,
                status,
                diagnosed_on,
                resolved_on,
                doctor_hospital,
                notes,
                created_at,
                updated_at
            FROM health_conditions
            WHERE id = :id
              AND health_profile_id = :health_profile_id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id,
            'health_profile_id' => $healthProfileId,
        ]);
    }

    /**
     * Create a medical condition.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO health_conditions (
                health_profile_id,
                condition_name,
                status,
                diagnosed_on,
                resolved_on,
                doctor_hospital,
                notes
            ) VALUES (
                :health_profile_id,
                :condition_name,
                :status,
                :diagnosed_on,
                :resolved_on,
                :doctor_hospital,
                :notes
            )
        ";

        $this->execute($sql, [
            'health_profile_id' => $data['health_profile_id'],
            'condition_name' => $data['condition_name'],
            'status' => $data['status'],
            'diagnosed_on' => $data['diagnosed_on'],
            'resolved_on' => $data['resolved_on'],
            'doctor_hospital' => $data['doctor_hospital'],
            'notes' => $data['notes'],
        ]);

        return (int) $this->lastInsertId();
    }

    /**
     * Update a medical condition.
     */
    public function update(int $id, int $healthProfileId, array $data): bool
    {
        $sql = "
            UPDATE health_conditions
            SET
                condition_name = :condition_name,
                status = :status,
                diagnosed_on = :diagnosed_on,
                resolved_on = :resolved_on,
                doctor_hospital = :doctor_hospital,
                notes = :notes
            WHERE id = :id
              AND health_profile_id = :health_profile_id
        ";

        return $this->execute($sql, [
            'id' => $id,
            'health_profile_id' => $healthProfileId,
            'condition_name' => $data['condition_name'],
            'status' => $data['status'],
            'diagnosed_on' => $data['diagnosed_on'],
            'resolved_on' => $data['resolved_on'],
            'doctor_hospital' => $data['doctor_hospital'],
            'notes' => $data['notes'],
        ]);
    }

    /**
     * Delete a medical condition.
     */
    public function delete(int $id, int $healthProfileId): bool
    {
        $sql = "
            DELETE FROM health_conditions
            WHERE id = :id
              AND health_profile_id = :health_profile_id
        ";

        return $this->execute($sql, [
            'id' => $id,
            'health_profile_id' => $healthProfileId,
        ]);
    }
}

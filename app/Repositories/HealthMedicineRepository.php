<?php

namespace App\Repositories;

class HealthMedicineRepository extends BaseRepository
{
    /**
     * Get all medicines for a health profile.
     */
    public function findByProfileId(int $profileId): array
    {
        $sql = "
            SELECT *
            FROM health_medicines
            WHERE health_profile_id = :health_profile_id
            ORDER BY
                CASE status
                    WHEN 'current' THEN 1
                    WHEN 'stopped' THEN 2
                    WHEN 'historical' THEN 3
                END,
                started_on DESC,
                id DESC
        ";

        return $this->fetchAll(
            $sql,
            [
                'health_profile_id' => $profileId
            ]
        );
    }


    /**
     * Find a medicine by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM health_medicines
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'id' => $id
            ]
        ) ?: null;
    }


    /**
     * Create a medicine record.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO health_medicines (
                health_profile_id,
                brand_name,
                generic_name,
                strength,
                dose,
                frequency,
                route,
                status,
                started_on,
                stopped_on,
                prescribed_by,
                notes
            )
            VALUES (
                :health_profile_id,
                :brand_name,
                :generic_name,
                :strength,
                :dose,
                :frequency,
                :route,
                :status,
                :started_on,
                :stopped_on,
                :prescribed_by,
                :notes
            )
        ";

        $this->execute(
            $sql,
            [
                'health_profile_id' => $data['health_profile_id'],
                'brand_name'        => $data['brand_name'],
                'generic_name'     => $data['generic_name'] ?? null,
                'strength'         => $data['strength'] ?? null,
                'dose'             => $data['dose'] ?? null,
                'frequency'        => $data['frequency'] ?? null,
                'route'            => $data['route'] ?? null,
                'status'           => $data['status'] ?? 'current',
                'started_on'       => $data['started_on'] ?? null,
                'stopped_on'       => $data['stopped_on'] ?? null,
                'prescribed_by'    => $data['prescribed_by'] ?? null,
                'notes'            => $data['notes'] ?? null
            ]
        );

        return $this->lastInsertId();
    }


    /**
     * Update a medicine record.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE health_medicines
            SET
                brand_name = :brand_name,
                generic_name = :generic_name,
                strength = :strength,
                dose = :dose,
                frequency = :frequency,
                route = :route,
                status = :status,
                started_on = :started_on,
                stopped_on = :stopped_on,
                prescribed_by = :prescribed_by,
                notes = :notes
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id'               => $id,
                'brand_name'       => $data['brand_name'],
                'generic_name'    => $data['generic_name'] ?? null,
                'strength'        => $data['strength'] ?? null,
                'dose'            => $data['dose'] ?? null,
                'frequency'       => $data['frequency'] ?? null,
                'route'            => $data['route'] ?? null,
                'status'           => $data['status'] ?? 'current',
                'started_on'      => $data['started_on'] ?? null,
                'stopped_on'      => $data['stopped_on'] ?? null,
                'prescribed_by'   => $data['prescribed_by'] ?? null,
                'notes'           => $data['notes'] ?? null
            ]
        );
    }


    /**
     * Delete a medicine record.
     */
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM health_medicines
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id' => $id
            ]
        );
    }
}
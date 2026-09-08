<?php

namespace App\Repositories;

class HealthAllergyRepository extends BaseRepository
{
    public function findByProfileId(int $profileId): array
    {
        $sql = "
            SELECT *
            FROM health_allergies
            WHERE health_profile_id = :health_profile_id
            ORDER BY
                CASE severity
                    WHEN 'life-threatening' THEN 1
                    WHEN 'severe' THEN 2
                    WHEN 'moderate' THEN 3
                    WHEN 'mild' THEN 4
                    ELSE 5
                END,
                allergen ASC,
                id DESC
        ";

        return $this->fetchAll(
            $sql,
            [
                'health_profile_id' => $profileId
            ]
        );
    }


    public function findById(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM health_allergies
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


    public function create(array $data): int
    {
        $sql = "
            INSERT INTO health_allergies (
                health_profile_id,
                allergen,
                category,
                reaction,
                severity,
                identified_on,
                notes
            )
            VALUES (
                :health_profile_id,
                :allergen,
                :category,
                :reaction,
                :severity,
                :identified_on,
                :notes
            )
        ";

        $this->execute(
            $sql,
            [
                'health_profile_id' => $data['health_profile_id'],
                'allergen'          => $data['allergen'],
                'category'          => $data['category'] ?? 'other',
                'reaction'          => $data['reaction'] ?? null,
                'severity'          => $data['severity'] ?? null,
                'identified_on'     => $data['identified_on'] ?? null,
                'notes'             => $data['notes'] ?? null
            ]
        );

        return $this->lastInsertId();
    }


    public function update(
        int $id,
        array $data
    ): bool {
        $sql = "
            UPDATE health_allergies
            SET
                allergen = :allergen,
                category = :category,
                reaction = :reaction,
                severity = :severity,
                identified_on = :identified_on,
                notes = :notes
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id'            => $id,
                'allergen'      => $data['allergen'],
                'category'      => $data['category'] ?? 'other',
                'reaction'      => $data['reaction'] ?? null,
                'severity'      => $data['severity'] ?? null,
                'identified_on' => $data['identified_on'] ?? null,
                'notes'         => $data['notes'] ?? null
            ]
        );
    }


    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM health_allergies
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
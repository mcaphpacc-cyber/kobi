<?php

declare(strict_types=1);

namespace App\Repositories;

class UserSavedDiseaseRepository extends BaseRepository
{
    /**
     * Save a disease for a user.
     */
    public function save(
        int $userId,
        int $diseaseId
    ): bool {
        return $this->execute(
            "
            INSERT INTO user_saved_diseases (
                user_id,
                disease_id
            )
            VALUES (
                :user_id,
                :disease_id
            )
            ",
            [
                'user_id' => $userId,
                'disease_id' => $diseaseId
            ]
        );
    }

    /**
     * Remove a saved disease for a user.
     */
    public function remove(
        int $userId,
        int $diseaseId
    ): bool {
        return $this->execute(
            "
            DELETE FROM user_saved_diseases
            WHERE user_id = :user_id
              AND disease_id = :disease_id
            ",
            [
                'user_id' => $userId,
                'disease_id' => $diseaseId
            ]
        );
    }

    /**
     * Determine whether a disease is saved by a user.
     */
    public function isSaved(
        int $userId,
        int $diseaseId
    ): bool {
        $row = $this->fetch(
            "
            SELECT id
            FROM user_saved_diseases
            WHERE user_id = :user_id
              AND disease_id = :disease_id
            LIMIT 1
            ",
            [
                'user_id' => $userId,
                'disease_id' => $diseaseId
            ]
        );

        return $row !== null;
    }

    /**
     * Get all saved disease IDs for a user.
     */
    public function findDiseaseIdsByUser(
        int $userId
    ): array {
        return $this->fetchAll(
            "
            SELECT disease_id
            FROM user_saved_diseases
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC
            ",
            [
                'user_id' => $userId
            ]
        );
    }

    public function findSavedDiseasesByUser(
        int $userId
    ): array {
        return $this->fetchAll(
            "
            SELECT
                d.id AS disease_id,
                d.disease_en,
                d.disease_hi,
                d.slug,
                d.gender,
                d.body_part_id,
                usd.created_at AS saved_at

            FROM user_saved_diseases usd

            INNER JOIN diseases d
                ON d.id = usd.disease_id

            WHERE usd.user_id = :user_id

            ORDER BY
                usd.created_at DESC,
                usd.id DESC
            ",
            [
                'user_id' => $userId
            ]
        );
    }
}
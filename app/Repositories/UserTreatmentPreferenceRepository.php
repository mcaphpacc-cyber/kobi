<?php

declare(strict_types=1);

namespace App\Repositories;

class UserTreatmentPreferenceRepository extends BaseRepository
{
    /**
     * Get the saved treatment order for a user.
     */
    public function findByUser(
        int $userId
    ): array {
        return $this->fetchAll(
            "
            SELECT
                treatment_system_id,
                sort_order
            FROM user_treatment_preferences
            WHERE user_id = :user_id
            ORDER BY sort_order ASC
            ",
            [
                'user_id' => $userId
            ]
        );
    }

    /**
     * Save the complete treatment order for a user.
     *
     * The existing preference set is replaced atomically.
     */
    public function saveOrder(
        int $userId,
        array $orderedTreatmentSystemIds
    ): bool {
        $this->beginTransaction();

        try {

            $this->execute(
                "
                DELETE FROM user_treatment_preferences
                WHERE user_id = :user_id
                ",
                [
                    'user_id' => $userId
                ]
            );

            foreach (
                $orderedTreatmentSystemIds
                as $index => $treatmentSystemId
            ) {
                $this->execute(
                    "
                    INSERT INTO user_treatment_preferences (
                        user_id,
                        treatment_system_id,
                        sort_order
                    )
                    VALUES (
                        :user_id,
                        :treatment_system_id,
                        :sort_order
                    )
                    ",
                    [
                        'user_id' => $userId,
                        'treatment_system_id' =>
                            (int) $treatmentSystemId,
                        'sort_order' => $index + 1
                    ]
                );
            }

            $this->commit();

            return true;

        } catch (\Throwable $exception) {

            $this->rollback();

            throw $exception;
        }
    }

    /**
     * Remove all treatment preferences for a user.
     *
     * This restores the KOBI default treatment order.
     */
    public function deleteByUser(
        int $userId
    ): bool {
        return $this->execute(
            "
            DELETE FROM user_treatment_preferences
            WHERE user_id = :user_id
            ",
            [
                'user_id' => $userId
            ]
        );
    }

    /**
     * Get all active treatment systems.
     */
    public function findActiveTreatmentSystems(): array
    {
        return $this->fetchAll(
            "
            SELECT
                id,
                name_en,
                name_hi,
                slug
            FROM treatment_systems
            WHERE is_active = 1
            ORDER BY id ASC
            "
        );
    }
}
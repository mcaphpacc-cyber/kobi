<?php

declare(strict_types=1);

namespace App\Repositories;

class HealthInsurancePolicyRepository extends BaseRepository
{
    /**
     * Find all insurance policies
     * belonging to a health profile.
     */
    public function findByProfileId(
        int $profileId
    ): array {
        $sql = "
            SELECT
                id,
                health_profile_id,
                created_by,
                provider_name,
                policy_number,
                policy_name,
                policy_type,
                status,
                sum_insured,
                currency_code,
                start_date,
                expiry_date,
                renewal_notes,
                general_notes,
                created_at,
                updated_at
            FROM health_insurance_policies
            WHERE health_profile_id = :health_profile_id
            ORDER BY
                expiry_date IS NULL ASC,
                expiry_date ASC,
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
     * Find one policy by ID.
     */
    public function findById(
        int $id
    ): ?array {
        $sql = "
            SELECT
                id,
                health_profile_id,
                created_by,
                provider_name,
                policy_number,
                policy_name,
                policy_type,
                status,
                sum_insured,
                currency_code,
                start_date,
                expiry_date,
                renewal_notes,
                general_notes,
                created_at,
                updated_at
            FROM health_insurance_policies
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'id' => $id
            ]
        );
    }

    /**
     * Create an insurance policy.
     */
    public function create(
        array $data
    ): int {
        $sql = "
            INSERT INTO health_insurance_policies (
                health_profile_id,
                created_by,
                provider_name,
                policy_number,
                policy_name,
                policy_type,
                status,
                sum_insured,
                currency_code,
                start_date,
                expiry_date,
                renewal_notes,
                general_notes
            )
            VALUES (
                :health_profile_id,
                :created_by,
                :provider_name,
                :policy_number,
                :policy_name,
                :policy_type,
                :status,
                :sum_insured,
                :currency_code,
                :start_date,
                :expiry_date,
                :renewal_notes,
                :general_notes
            )
        ";

        $this->query(
            $sql,
            [
                'health_profile_id' =>
                    $data['health_profile_id'],

                'created_by' =>
                    $data['created_by'],

                'provider_name' =>
                    $data['provider_name'],

                'policy_number' =>
                    $data['policy_number'] ?? null,

                'policy_name' =>
                    $data['policy_name'] ?? null,

                'policy_type' =>
                    $data['policy_type'],

                'status' =>
                    $data['status'],

                'sum_insured' =>
                    $data['sum_insured'] ?? null,

                'currency_code' =>
                    $data['currency_code'] ?? 'INR',

                'start_date' =>
                    $data['start_date'] ?? null,

                'expiry_date' =>
                    $data['expiry_date'] ?? null,

                'renewal_notes' =>
                    $data['renewal_notes'] ?? null,

                'general_notes' =>
                    $data['general_notes'] ?? null
            ]
        );

        return (int) $this->lastInsertId();
    }

    /**
     * Update an insurance policy.
     */
    public function update(
        int $id,
        array $data
    ): bool {
        $sql = "
            UPDATE health_insurance_policies
            SET
                provider_name = :provider_name,
                policy_number = :policy_number,
                policy_name = :policy_name,
                policy_type = :policy_type,
                status = :status,
                sum_insured = :sum_insured,
                currency_code = :currency_code,
                start_date = :start_date,
                expiry_date = :expiry_date,
                renewal_notes = :renewal_notes,
                general_notes = :general_notes
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id' => $id,

                'provider_name' =>
                    $data['provider_name'],

                'policy_number' =>
                    $data['policy_number'] ?? null,

                'policy_name' =>
                    $data['policy_name'] ?? null,

                'policy_type' =>
                    $data['policy_type'],

                'status' =>
                    $data['status'],

                'sum_insured' =>
                    $data['sum_insured'] ?? null,

                'currency_code' =>
                    $data['currency_code'] ?? 'INR',

                'start_date' =>
                    $data['start_date'] ?? null,

                'expiry_date' =>
                    $data['expiry_date'] ?? null,

                'renewal_notes' =>
                    $data['renewal_notes'] ?? null,

                'general_notes' =>
                    $data['general_notes'] ?? null
            ]
        );
    }

    /**
     * Delete an insurance policy.
     */
    public function delete(
        int $id
    ): bool {
        $sql = "
            DELETE FROM health_insurance_policies
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id' => $id
            ]
        );
    }

    /**
     * Count policies belonging
     * to a health profile.
     */
    public function countByProfileId(
        int $profileId
    ): int {
        $sql = "
            SELECT COUNT(*) AS total
            FROM health_insurance_policies
            WHERE health_profile_id = :health_profile_id
        ";

        $row = $this->fetch(
            $sql,
            [
                'health_profile_id' => $profileId
            ]
        );

        return (int) ($row['total'] ?? 0);
    }
}
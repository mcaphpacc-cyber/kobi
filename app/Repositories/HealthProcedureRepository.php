<?php

namespace App\Repositories;

class HealthProcedureRepository extends BaseRepository
{
    public function findByProfileId(
        int $profileId
    ): array {
        $sql = "
            SELECT *
            FROM health_procedures
            WHERE health_profile_id = :health_profile_id
            ORDER BY
                performed_on DESC,
                id DESC
        ";

        return $this->fetchAll(
            $sql,
            [
                'health_profile_id' => $profileId
            ]
        );
    }


    public function findById(
        int $id
    ): ?array {
        $sql = "
            SELECT *
            FROM health_procedures
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


    public function create(
        array $data
    ): int {
        $sql = "
            INSERT INTO health_procedures (
                health_profile_id,
                procedure_name,
                procedure_type,
                performed_on,
                hospital,
                doctor,
                reason,
                notes
            )
            VALUES (
                :health_profile_id,
                :procedure_name,
                :procedure_type,
                :performed_on,
                :hospital,
                :doctor,
                :reason,
                :notes
            )
        ";

        $this->execute(
            $sql,
            [
                'health_profile_id' =>
                    $data['health_profile_id'],

                'procedure_name' =>
                    $data['procedure_name'],

                'procedure_type' =>
                    $data['procedure_type'] ??
                    'procedure',

                'performed_on' =>
                    $data['performed_on'] ?? null,

                'hospital' =>
                    $data['hospital'] ?? null,

                'doctor' =>
                    $data['doctor'] ?? null,

                'reason' =>
                    $data['reason'] ?? null,

                'notes' =>
                    $data['notes'] ?? null
            ]
        );

        return $this->lastInsertId();
    }


    public function update(
        int $id,
        array $data
    ): bool {
        $sql = "
            UPDATE health_procedures
            SET
                procedure_name = :procedure_name,
                procedure_type = :procedure_type,
                performed_on = :performed_on,
                hospital = :hospital,
                doctor = :doctor,
                reason = :reason,
                notes = :notes
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id' =>
                    $id,

                'procedure_name' =>
                    $data['procedure_name'],

                'procedure_type' =>
                    $data['procedure_type'] ??
                    'procedure',

                'performed_on' =>
                    $data['performed_on'] ?? null,

                'hospital' =>
                    $data['hospital'] ?? null,

                'doctor' =>
                    $data['doctor'] ?? null,

                'reason' =>
                    $data['reason'] ?? null,

                'notes' =>
                    $data['notes'] ?? null
            ]
        );
    }


    public function delete(
        int $id
    ): bool {
        $sql = "
            DELETE FROM health_procedures
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
<?php

declare(strict_types=1);

namespace App\Repositories;

class MedicalSummaryShareRepository extends BaseRepository
{
    /**
     * Find a medical summary share by ID.
     */
    public function findById(
        int $id
    ): ?array {
        $sql = "
            SELECT *
            FROM medical_summary_shares
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
     * Find a medical summary share by
     * its hashed access token.
     */
    public function findByTokenHash(
        string $tokenHash
    ): ?array {
        $sql = "
            SELECT *
            FROM medical_summary_shares
            WHERE token_hash = :token_hash
            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'token_hash' => $tokenHash
            ]
        ) ?: null;
    }


    /**
     * Find all shares belonging to a
     * health profile.
     */
    public function findByProfileId(
        int $profileId
    ): array {
        $sql = "
            SELECT *
            FROM medical_summary_shares
            WHERE health_profile_id = :health_profile_id
            ORDER BY created_at DESC, id DESC
        ";

        return $this->fetchAll(
            $sql,
            [
                'health_profile_id' => $profileId
            ]
        );
    }


    /**
     * Create a medical summary share.
     */
    public function create(
        array $data
    ): int {
        $sql = "
            INSERT INTO medical_summary_shares (
                health_profile_id,
                created_by,
                token_hash,
                snapshot,
                snapshot_schema_version,
                snapshot_created_at,
                expires_at,
                status
            )
            VALUES (
                :health_profile_id,
                :created_by,
                :token_hash,
                :snapshot,
                :snapshot_schema_version,
                :snapshot_created_at,
                :expires_at,
                :status
            )
        ";

        $this->execute(
            $sql,
            [
                'health_profile_id' =>
                    $data['health_profile_id'],

                'created_by' =>
                    $data['created_by'],

                'token_hash' =>
                    $data['token_hash'],

                'snapshot' =>
                    $data['snapshot'],

                'snapshot_schema_version' =>
                    $data['snapshot_schema_version']
                    ?? '1.0',

                'snapshot_created_at' =>
                    $data['snapshot_created_at'],

                'expires_at' =>
                    $data['expires_at'],

                'status' =>
                    $data['status']
                    ?? 'active'
            ]
        );

        return (int) $this->lastInsertId();
    }


    /**
     * Revoke an active medical summary share.
     */
    public function revoke(
        int $id,
        int $revokedBy
    ): bool {
        $sql = "
            UPDATE medical_summary_shares
            SET
                status = 'revoked',
                revoked_at = CURRENT_TIMESTAMP(),
                revoked_by = :revoked_by
            WHERE id = :id
              AND status = 'active'
        ";

        return $this->execute(
            $sql,
            [
                'id' =>
                    $id,

                'revoked_by' =>
                    $revokedBy
            ]
        );
    }


    /**
     * Log access to a medical summary share.
     */
    public function logAccess(
        int $shareId,
        ?string $ipAddress,
        ?string $userAgent
    ): int {
        $sql = "
            INSERT INTO medical_summary_share_access_logs (
                share_id,
                ip_address,
                user_agent
            )
            VALUES (
                :share_id,
                :ip_address,
                :user_agent
            )
        ";

        $this->execute(
            $sql,
            [
                'share_id' =>
                    $shareId,

                'ip_address' =>
                    $ipAddress,

                'user_agent' =>
                    $userAgent
            ]
        );

        return (int) $this->lastInsertId();
    }
}
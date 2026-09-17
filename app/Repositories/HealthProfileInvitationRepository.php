<?php

declare(strict_types=1);

namespace App\Repositories;

class HealthProfileInvitationRepository extends BaseRepository
{
    /**
     * Find an invitation by ID.
     */
    public function findById(
        int $invitationId
    ): ?array {
        $sql = "
            SELECT
                hpi.id,
                hpi.health_profile_id,
                hpi.invited_by,
                hpi.email,
                hpi.role,
                hpi.token_hash,
                hpi.expires_at,
                hpi.status,
                hpi.accepted_at,
                hpi.created_at,
                hpi.updated_at

            FROM health_profile_invitations hpi

            WHERE hpi.id = :invitation_id

            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'invitation_id' => $invitationId
            ]
        );
    }

    /**
     * Find an invitation by its token hash.
     */
    public function findByTokenHash(
        string $tokenHash
    ): ?array {
        $sql = "
            SELECT
                hpi.id,
                hpi.health_profile_id,
                hpi.invited_by,
                hpi.email,
                hpi.role,
                hpi.token_hash,
                hpi.expires_at,
                hpi.status,
                hpi.accepted_at,
                hpi.created_at,
                hpi.updated_at

            FROM health_profile_invitations hpi

            WHERE hpi.token_hash = :token_hash

            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'token_hash' => $tokenHash
            ]
        );
    }

    /**
     * Find pending invitations for a health profile.
     */
    public function findPendingByProfileId(
        int $profileId
    ): array {
        $sql = "
            SELECT
                hpi.id,
                hpi.health_profile_id,
                hpi.invited_by,
                hpi.email,
                hpi.role,
                hpi.expires_at,
                hpi.status,
                hpi.accepted_at,
                hpi.created_at,
                hpi.updated_at

            FROM health_profile_invitations hpi

            WHERE hpi.health_profile_id = :profile_id
              AND hpi.status = 'pending'

            ORDER BY
                hpi.created_at DESC,
                hpi.id DESC
        ";

        return $this->fetchAll(
            $sql,
            [
                'profile_id' => $profileId
            ]
        );
    }

    /**
     * Find a pending invitation for a profile and email address.
     */
    public function findPendingByProfileAndEmail(
        int $profileId,
        string $email
    ): ?array {
        $sql = "
            SELECT
                hpi.id,
                hpi.health_profile_id,
                hpi.invited_by,
                hpi.email,
                hpi.role,
                hpi.token_hash,
                hpi.expires_at,
                hpi.status,
                hpi.accepted_at,
                hpi.created_at,
                hpi.updated_at

            FROM health_profile_invitations hpi

            WHERE hpi.health_profile_id = :profile_id
              AND hpi.email = :email
              AND hpi.status = 'pending'

            ORDER BY
                hpi.id DESC

            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'profile_id' => $profileId,
                'email' => $email
            ]
        );
    }

    /**
     * Create a health profile invitation.
     */
    public function create(
        array $data
    ): int {
        $sql = "
            INSERT INTO health_profile_invitations (
                health_profile_id,
                invited_by,
                email,
                role,
                token_hash,
                expires_at,
                status
            )
            VALUES (
                :health_profile_id,
                :invited_by,
                :email,
                :role,
                :token_hash,
                :expires_at,
                'pending'
            )
        ";

        $this->execute(
            $sql,
            [
                'health_profile_id' =>
                    $data['health_profile_id'],

                'invited_by' =>
                    $data['invited_by'],

                'email' =>
                    $data['email'],

                'role' =>
                    $data['role'],

                'token_hash' =>
                    $data['token_hash'],

                'expires_at' =>
                    $data['expires_at']
            ]
        );

        return (int) $this->lastInsertId();
    }

    /**
     * Mark an invitation as accepted.
     */
    public function markAccepted(
        int $invitationId
    ): bool {
        $sql = "
            UPDATE health_profile_invitations

            SET
                status = 'accepted',
                accepted_at = CURRENT_TIMESTAMP

            WHERE id = :invitation_id
              AND status = 'pending'
        ";

        return $this->execute(
            $sql,
            [
                'invitation_id' => $invitationId
            ]
        );
    }

    /**
     * Revoke a pending invitation.
     */
    public function revoke(
        int $invitationId
    ): bool {
        $sql = "
            UPDATE health_profile_invitations

            SET
                status = 'revoked'

            WHERE id = :invitation_id
              AND status = 'pending'
        ";

        return $this->execute(
            $sql,
            [
                'invitation_id' => $invitationId
            ]
        );
    }

    /**
     * Accept an invitation and create/reactivate
     * the corresponding health profile membership
     * as one atomic database operation.
     */
    public function acceptWithMembership(
        int $invitationId,
        int $profileId,
        int $userId,
        string $role
    ): bool {
        $this->beginTransaction();

        try
        {
            $existingMember = $this->fetch(
                "
                SELECT
                    id,
                    status

                FROM health_profile_members

                WHERE health_profile_id = :profile_id
                AND user_id = :user_id

                LIMIT 1
                ",
                [
                    'profile_id' => $profileId,
                    'user_id' => $userId
                ]
            );

            if ($existingMember !== null)
            {
                if ($existingMember['status'] === 'active')
                {
                    throw new \RuntimeException(
                        'You already have access to this health profile.'
                    );
                }

                $membershipUpdated = $this->execute(
                    "
                    UPDATE health_profile_members

                    SET
                        role = :role,
                        status = 'active'

                    WHERE id = :id
                    AND status = 'revoked'
                    ",
                    [
                        'role' => $role,
                        'id' => $existingMember['id']
                    ]
                );

                if (!$membershipUpdated)
                {
                    throw new \RuntimeException(
                        'Unable to activate health profile access.'
                    );
                }
            }
            else
            {
                $membershipCreated = $this->execute(
                    "
                    INSERT INTO health_profile_members (
                        health_profile_id,
                        user_id,
                        role,
                        status
                    )
                    VALUES (
                        :profile_id,
                        :user_id,
                        :role,
                        'active'
                    )
                    ",
                    [
                        'profile_id' => $profileId,
                        'user_id' => $userId,
                        'role' => $role
                    ]
                );

                if (!$membershipCreated)
                {
                    throw new \RuntimeException(
                        'Unable to create health profile access.'
                    );
                }
            }

            $invitationUpdated = $this->execute(
                "
                UPDATE health_profile_invitations

                SET
                    status = 'accepted',
                    accepted_at = CURRENT_TIMESTAMP

                WHERE id = :invitation_id
                AND status = 'pending'
                ",
                [
                    'invitation_id' => $invitationId
                ]
            );

            if (!$invitationUpdated)
            {
                throw new \RuntimeException(
                    'Unable to accept the invitation.'
                );
            }

            $this->commit();

            return true;
        }
        catch (\Throwable $e)
        {
            $this->rollback();

            throw $e;
        }
    }
}
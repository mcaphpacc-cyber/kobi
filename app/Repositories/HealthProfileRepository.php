<?php

declare(strict_types=1);

namespace App\Repositories;

class HealthProfileRepository extends BaseRepository
{
    /**
     * Find all active health profiles
     * accessible by a user.
     */
    public function findAccessibleByUser(
        int $userId
    ): array {
        $sql = "
            SELECT
                hp.id,
                hp.profile_type,
                hp.full_name,
                hp.date_of_birth,
                hp.gender,
                hp.created_by,
                hp.status,
                hp.created_at,
                hp.updated_at,
                hpm.role,
                hpm.status AS membership_status

            FROM health_profiles hp

            INNER JOIN health_profile_members hpm
                ON hpm.health_profile_id = hp.id

            WHERE hpm.user_id = :user_id
              AND hpm.status = 'active'
              AND hp.status = 'active'

            ORDER BY
                CASE
                    WHEN hpm.role = 'owner' THEN 0
                    ELSE 1
                END,
                hp.full_name ASC
        ";

        return $this->fetchAll(
            $sql,
            [
                'user_id' => $userId
            ]
        );
    }

    /**
     * Find one active health profile
     * accessible by a user.
     */
    public function findAccessible(
        int $profileId,
        int $userId
    ): ?array {
        $sql = "
            SELECT
                hp.id,
                hp.profile_type,
                hp.full_name,
                hp.date_of_birth,
                hp.gender,
                hp.created_by,
                hp.status,
                hp.created_at,
                hp.updated_at,
                hpm.role,
                hpm.status AS membership_status

            FROM health_profiles hp

            INNER JOIN health_profile_members hpm
                ON hpm.health_profile_id = hp.id

            WHERE hp.id = :profile_id
              AND hpm.user_id = :user_id
              AND hpm.status = 'active'
              AND hp.status = 'active'

            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'profile_id' => $profileId,
                'user_id' => $userId
            ]
        );
    }

    /**
     * Check whether a user has active access
     * to a health profile.
     */
    public function hasAccess(
        int $profileId,
        int $userId
    ): bool {
        $sql = "
            SELECT 1
            FROM health_profile_members

            WHERE health_profile_id = :profile_id
              AND user_id = :user_id
              AND status = 'active'

            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'profile_id' => $profileId,
                'user_id' => $userId
            ]
        ) !== null;
    }

    /**
     * Create a health profile.
     */
    public function createProfile(
        string $profileType,
        string $fullName,
        ?string $dateOfBirth,
        string $gender,
        int $createdBy
    ): int {
        $sql = "
            INSERT INTO health_profiles (
                profile_type,
                full_name,
                date_of_birth,
                gender,
                created_by
            )
            VALUES (
                :profile_type,
                :full_name,
                :date_of_birth,
                :gender,
                :created_by
            )
        ";

        $this->execute(
            $sql,
            [
                'profile_type' => $profileType,
                'full_name' => $fullName,
                'date_of_birth' => $dateOfBirth,
                'gender' => $gender,
                'created_by' => $createdBy
            ]
        );

        return (int) $this->lastInsertId();
    }

    /**
     * Add a user as a member of a health profile.
     */
    public function addMember(
        int $profileId,
        int $userId,
        string $role = 'viewer'
    ): bool {
        $sql = "
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
        ";

        return $this->execute(
            $sql,
            [
                'profile_id' => $profileId,
                'user_id' => $userId,
                'role' => $role
            ]
        );
    }

    /**
     * Find the active self profile owned by a user.
     */
    public function findSelfProfile(
        int $userId
    ): ?array {
        $sql = "
            SELECT
                hp.id,
                hp.profile_type,
                hp.full_name,
                hp.date_of_birth,
                hp.gender,
                hp.created_by,
                hp.status,
                hp.created_at,
                hp.updated_at,
                hpm.role,
                hpm.status AS membership_status

            FROM health_profiles hp

            INNER JOIN health_profile_members hpm
                ON hpm.health_profile_id = hp.id

            WHERE hp.profile_type = 'self'
            AND hp.created_by = :created_by
            AND hpm.user_id = :member_user_id
            AND hpm.role = 'owner'
            AND hpm.status = 'active'
            AND hp.status = 'active'

            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'created_by' => $userId,
                'member_user_id' => $userId
            ]
        );
    }

    /**
     * Create a health profile and its owner membership
     * as one atomic database operation.
     */
    public function createProfileWithOwner(
        string $profileType,
        string $fullName,
        ?string $dateOfBirth,
        string $gender,
        int $userId
    ): int {
        $this->beginTransaction();

        try
        {
            $profileId = $this->createProfile(
                $profileType,
                $fullName,
                $dateOfBirth,
                $gender,
                $userId
            );

            $created = $this->addMember(
                $profileId,
                $userId,
                'owner'
            );

            if (!$created)
            {
                throw new \RuntimeException(
                    'Unable to create health profile membership.'
                );
            }

            $this->commit();

            return $profileId;
        }
        catch (\Throwable $e)
        {
            $this->rollback();

            throw $e;
        }
    }

    public function updateProfile(
        int $profileId,
        int $userId,
        string $fullName,
        ?string $dateOfBirth,
        string $gender
    ): bool {
        $sql = "
            UPDATE health_profiles hp
            INNER JOIN health_profile_members hpm
                ON hpm.health_profile_id = hp.id
            SET
                hp.full_name = :full_name,
                hp.date_of_birth = :date_of_birth,
                hp.gender = :gender
            WHERE
                hp.id = :profile_id
                AND hpm.user_id = :user_id
                AND hpm.status = 'active'
                AND hpm.role IN ('owner', 'editor')
                AND hp.status = 'active'
        ";

        return $this->execute(
            $sql,
            [
                'full_name' => $fullName,
                'date_of_birth' => $dateOfBirth,
                'gender' => $gender,
                'profile_id' => $profileId,
                'user_id' => $userId
            ]
        );
    }
}
<?php

declare(strict_types=1);

namespace App\Repositories;

class UserRepository extends BaseRepository
{
    /**
     * Find a user by email address.
     */
    public function findByEmail(
        string $email
    ): ?array {
        $sql = "
            SELECT
                id,
                name,
                email,
                password_hash,
                status,
                email_verified_at,
                last_login_at,
                created_at,
                updated_at
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'email' => strtolower(trim($email))
            ]
        );
    }

    /**
     * Find a user by ID.
     */
    public function findById(
        int $id
    ): ?array {
        $sql = "
            SELECT
                id,
                name,
                email,
                status,
                email_verified_at,
                last_login_at,
                created_at,
                updated_at
            FROM users
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
     * Create a new user.
     *
     * The password must already be hashed before
     * reaching the repository.
     */
    public function create(
        string $name,
        string $email,
        string $passwordHash
    ): int {
        $sql = "
            INSERT INTO users (
                name,
                email,
                password_hash
            )
            VALUES (
                :name,
                :email,
                :password_hash
            )
        ";

        $this->query(
            $sql,
            [
                'name' => trim($name),
                'email' => strtolower(trim($email)),
                'password_hash' => $passwordHash
            ]
        );

        return (int) $this->lastInsertId();
    }

    /**
     * Update the user's last login timestamp.
     */
    public function updateLastLogin(
        int $userId
    ): bool {
        return $this->execute(
            "
            UPDATE users
            SET last_login_at = CURRENT_TIMESTAMP
            WHERE id = :id
            ",
            [
                'id' => $userId
            ]
        );
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(
        int $userId,
        string $name,
        string $email
    ): bool {
        return $this->execute(
            "
            UPDATE users
            SET
                name = :name,
                email = :email
            WHERE id = :id
            ",
            [
                'id' => $userId,
                'name' => trim($name),
                'email' => strtolower(trim($email))
            ]
        );
    }

    /**
     * Update the user's password hash.
     */
    public function updatePassword(
        int $userId,
        string $passwordHash
    ): bool {
        return $this->execute(
            "
            UPDATE users
            SET password_hash = :password_hash
            WHERE id = :id
            ",
            [
                'id' => $userId,
                'password_hash' => $passwordHash
            ]
        );
    }

    /**
     * Find a user's password hash by ID.
     */
    public function findPasswordHash(
        int $userId
    ): ?string {
        $row = $this->fetch(
            "
            SELECT password_hash
            FROM users
            WHERE id = :id
            LIMIT 1
            ",
            [
                'id' => $userId
            ]
        );

        if (!$row) {
            return null;
        }

        return $row['password_hash'];
    }

    public function changePassword(
        string $currentPassword,
        string $newPassword,
        string $confirmation
    ): void {
        $userId = $this->id();

        if (!$userId) {
            throw new RuntimeException(
                'You must be logged in to change your password.'
            );
        }

        if ($currentPassword === '') {
            throw new RuntimeException(
                'Current password is required.'
            );
        }

        if (strlen($newPassword) < 8) {
            throw new RuntimeException(
                'New password must be at least 8 characters long.'
            );
        }

        if ($newPassword !== $confirmation) {
            throw new RuntimeException(
                'New passwords do not match.'
            );
        }

        $passwordHash = $this->userRepository
            ->findPasswordHash($userId);

        if (
            !$passwordHash ||
            !password_verify(
                $currentPassword,
                $passwordHash
            )
        ) {
            throw new RuntimeException(
                'Current password is incorrect.'
            );
        }

        $newPasswordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if ($newPasswordHash === false) {
            throw new RuntimeException(
                'Unable to secure the new password.'
            );
        }

        $updated = $this->userRepository->updatePassword(
            $userId,
            $newPasswordHash
        );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to update your password.'
            );
        }

        $this->session->regenerate();

        $this->session->put(
            self::SESSION_USER_ID,
            $userId
        );
    }
}
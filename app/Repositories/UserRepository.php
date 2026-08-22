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
}
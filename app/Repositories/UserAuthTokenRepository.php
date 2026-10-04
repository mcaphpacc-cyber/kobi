<?php

declare(strict_types=1);

namespace App\Repositories;

class UserAuthTokenRepository extends BaseRepository
{
    /**
     * Create an authentication token record.
     *
     * The token hash must already be generated before
     * reaching the repository.
     */
    public function create(
        int $userId,
        string $tokenHash,
        string $type,
        string $expiresAt
    ): int {
        $sql = "
            INSERT INTO user_auth_tokens (
                user_id,
                token_hash,
                type,
                expires_at
            )
            VALUES (
                :user_id,
                :token_hash,
                :type,
                :expires_at
            )
        ";

        $this->query(
            $sql,
            [
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'type' => $type,
                'expires_at' => $expiresAt
            ]
        );

        return (int) $this->lastInsertId();
    }

    /**
     * Find a token by its hash and type.
     *
     * Only unused tokens are returned.
     */
    public function findValidToken(
        string $tokenHash,
        string $type
    ): ?array {
        $sql = "
            SELECT
                id,
                user_id,
                token_hash,
                type,
                expires_at,
                used_at,
                created_at
            FROM user_auth_tokens
            WHERE
                token_hash = :token_hash
                AND type = :type
                AND used_at IS NULL
                AND expires_at > CURRENT_TIMESTAMP
            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'token_hash' => $tokenHash,
                'type' => $type
            ]
        );
    }

    /**
     * Atomically consume a valid authentication token.
     */
    public function markUsed(int $tokenId): bool
    {
        return $this->execute(
            "
            UPDATE user_auth_tokens
            SET used_at = CURRENT_TIMESTAMP
            WHERE id = :id
            AND used_at IS NULL
            AND expires_at > CURRENT_TIMESTAMP
            ",
            [
                'id' => $tokenId
            ]
        );
    }

    /**
     * Invalidate all unused tokens of a given type
     * belonging to a user.
     */
    public function invalidateUnusedTokens(
        int $userId,
        string $type
    ): bool {
        return $this->execute(
            "
            UPDATE user_auth_tokens
            SET used_at = CURRENT_TIMESTAMP
            WHERE
                user_id = :user_id
                AND type = :type
                AND used_at IS NULL
            ",
            [
                'user_id' => $userId,
                'type' => $type
            ]
        );
    }

    /**
     * Delete expired or already-used tokens.
     *
     * This is maintenance only and is not required
     * for token validation.
     */
    public function deleteExpiredOrUsed(): bool
    {
        return $this->execute(
            "
            DELETE FROM user_auth_tokens
            WHERE
                used_at IS NOT NULL
                OR expires_at <= CURRENT_TIMESTAMP
            "
        );
    }

    /**
     * Determine whether a recent token already exists for a user.
     */
    public function hasRecentToken(
        int $userId,
        string $type,
        int $seconds = 60
    ): bool {
        if ($userId <= 0 || $seconds <= 0) {
            return false;
        }

        $sql = "
            SELECT id
            FROM user_auth_tokens
            WHERE user_id = :user_id
            AND type = :type
            AND created_at > DATE_SUB(
                CURRENT_TIMESTAMP,
                INTERVAL {$seconds} SECOND
            )
            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'user_id' => $userId,
                'type' => $type
            ]
        ) !== null;
    }
}
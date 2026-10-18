<?php
declare(strict_types=1);
namespace App\Repositories;

class ApiAuthRepository extends BaseRepository
{
    public function begin(): void { if (!$this->db->beginTransaction()) { throw new \RuntimeException('Unable to start API transaction'); } }
    public function commit(): bool { return $this->db->commit(); }
    public function rollback(): bool { return $this->db->inTransaction() ? $this->db->rollBack() : false; }

    public function userByEmail(string $email): ?array
    { return $this->fetch('SELECT id FROM users WHERE email = :email', ['email'=>$email]); }
    public function lockUser(int $id): ?array
    { return $this->fetch('SELECT id, email, password_hash, password_set_at, status, email_verified_at FROM users WHERE id = :id FOR UPDATE', ['id'=>$id]); }
    public function candidate(string $digest, bool $refresh): ?array
    {
        return $refresh
            ? $this->fetch('SELECT s.user_id, s.id FROM user_api_refresh_tokens t JOIN user_api_sessions s ON s.id=t.api_session_id WHERE t.token_hash=:hash', ['hash'=>$digest])
            : $this->fetch('SELECT user_id, id FROM user_api_sessions WHERE access_hash=:hash', ['hash'=>$digest]);
    }
    public function lockSession(int $id): ?array
    { return $this->fetch('SELECT * FROM user_api_sessions WHERE id=:id FOR UPDATE', ['id'=>$id]); }
    public function lockRefresh(string $digest): ?array
    { return $this->fetch('SELECT * FROM user_api_refresh_tokens WHERE token_hash=:hash FOR UPDATE', ['hash'=>$digest]); }
    public function activeSessions(int $userId, string $now): array
    { return $this->fetchAll('SELECT id FROM user_api_sessions WHERE user_id=:user AND revoked_at IS NULL AND absolute_expires_at>:now ORDER BY created_at,id FOR UPDATE', ['user'=>$userId,'now'=>$now]); }
    public function createSession(array $row): int
    {
        $this->query('INSERT INTO user_api_sessions (public_id,user_id,device_label,created_at,last_refreshed_at,absolute_expires_at,credential_stamp,access_hash,access_expires_at,refresh_generation) VALUES (:public,:user,:label,:created,:refreshed,:absolute,:stamp,:hash,:access,1)', $row);
        return (int)$this->db->lastInsertId();
    }
    public function createRefresh(int $session, int $generation, string $hash, string $issued, string $expires): void
    { $this->query('INSERT INTO user_api_refresh_tokens (api_session_id,generation,token_hash,issued_at,expires_at) VALUES (:session,:generation,:hash,:issued,:expires)', compact('session','generation','hash','issued','expires')); }
    public function consume(int $id, string $now): bool
    { return $this->query('UPDATE user_api_refresh_tokens SET consumed_at=:now WHERE id=:id AND consumed_at IS NULL', compact('now','id'))->rowCount()===1; }
    public function rotate(int $id, int $generation, string $hash, string $access, string $now): void
    { $this->query('UPDATE user_api_sessions SET refresh_generation=:generation,access_hash=:hash,access_expires_at=:access,last_refreshed_at=:now WHERE id=:id', compact('generation','hash','access','now','id')); }
    public function revoke(int $id, string $now, string $reason): void
    { $this->query('UPDATE user_api_sessions SET revoked_at=:now,revocation_reason=:reason WHERE id=:id AND revoked_at IS NULL', compact('now','reason','id')); }
    public function revokeAll(int $user, string $now): void
    { $this->query("UPDATE user_api_sessions SET revoked_at=:now,revocation_reason='logout_all' WHERE user_id=:user AND revoked_at IS NULL", compact('now','user')); }
    public function cleanup(string $cutoff, int $limit = 500): int
    {
        $limit=max(1,min(500,$limit));
        return $this->query('DELETE FROM user_api_sessions WHERE (revoked_at IS NOT NULL AND revoked_at<:revoked) OR absolute_expires_at<:expired ORDER BY id LIMIT '.$limit, ['revoked'=>$cutoff,'expired'=>$cutoff])->rowCount();
    }
}

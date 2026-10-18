<?php

declare(strict_types=1);

namespace App\Repositories;

class TreatmentEditorialPermissionRepository extends BaseRepository
{
    public function systems(int $userId): array
    {
        return $this->fetchAll('SELECT s.name_en,s.name_hi,s.is_active,p.* FROM treatment_systems s JOIN treatment_editorial_permissions p ON p.treatment_system_id=s.id JOIN users u ON u.id=p.user_id WHERE p.user_id=:user_id AND p.revoked_at IS NULL AND u.status=\'active\' ORDER BY s.id', ['user_id'=>$userId]);
    }

    public function lockUsers(array $ids): array
    {
        $ids = array_values(array_unique($ids)); sort($ids, SORT_NUMERIC); $rows = [];
        foreach ($ids as $id) { $rows[$id] = $this->fetch('SELECT id,status FROM users WHERE id=:id FOR UPDATE', ['id'=>$id]); }
        return $rows;
    }

    public function user(int $id): ?array
    {
        return $this->fetch('SELECT id,status FROM users WHERE id=:id', ['id'=>$id]);
    }

    public function active(int $userId, int $systemId, bool $lock = false): ?array
    {
        return $this->fetch('SELECT * FROM treatment_editorial_permissions WHERE user_id=:user_id AND treatment_system_id=:system_id AND revoked_at IS NULL'.($lock ? ' FOR UPDATE' : ''), ['user_id'=>$userId,'system_id'=>$systemId]);
    }

    public function insert(array $row): int
    {
        $this->execute('INSERT INTO treatment_editorial_permissions (user_id,treatment_system_id,can_edit,can_review,can_publish,qualification_scope,authorized_by,granted_at) VALUES (:user_id,:treatment_system_id,:can_edit,:can_review,:can_publish,:qualification_scope,:authorized_by,:granted_at)', $row);
        return (int) $this->lastInsertId();
    }

    public function revoke(int $id, int $actor, string $now, string $reason): void
    {
        $this->execute('UPDATE treatment_editorial_permissions SET revoked_by=:actor,revoked_at=:now,revocation_reason=:reason WHERE id=:id AND revoked_at IS NULL', ['actor'=>$actor,'now'=>$now,'reason'=>$reason,'id'=>$id]);
    }
}

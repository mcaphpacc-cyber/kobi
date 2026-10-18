<?php
declare(strict_types=1);
namespace App\Repositories;

class SavedSymptomCheckRepository extends BaseRepository
{
    public function createForUser(int $userId, string $key, array $snapshot, string $json): int
    {
        $this->execute('INSERT INTO user_saved_symptom_checks
            (user_id,check_key,checked_at,saved_at,selected_symptom_count,displayed_result_count,snapshot_json)
            VALUES (:user,:key,:checked,:saved,:symptoms,:results,:json)', [
            'user' => $userId, 'key' => $key, 'checked' => $snapshot['checked_at'],
            'saved' => gmdate('Y-m-d H:i:s'), 'symptoms' => count($snapshot['selected']),
            'results' => count($snapshot['results']), 'json' => $json,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetch('SELECT * FROM user_saved_symptom_checks WHERE id=:id AND user_id=:user', ['id' => $id, 'user' => $userId]);
    }

    public function findKeyForUser(string $key, int $userId): ?array
    {
        return $this->fetch('SELECT id FROM user_saved_symptom_checks WHERE check_key=:key AND user_id=:user', ['key' => $key, 'user' => $userId]);
    }

    public function pageForUser(int $userId, int $page): array
    {
        $offset = (max(1, min(100000, $page)) - 1) * 20;
        return $this->fetchAll('SELECT id,checked_at,saved_at,selected_symptom_count,displayed_result_count
            FROM user_saved_symptom_checks WHERE user_id=:user ORDER BY checked_at DESC,id DESC LIMIT 21 OFFSET ' . $offset, ['user' => $userId]);
    }

    public function deleteForUser(int $id, int $userId): bool
    {
        return $this->query('DELETE FROM user_saved_symptom_checks WHERE id=:id AND user_id=:user', ['id' => $id, 'user' => $userId])->rowCount() === 1;
    }

    public function countApiForUser(int $userId): int
    {
        return (int) $this->fetch('SELECT COUNT(*) AS total FROM user_saved_symptom_checks WHERE user_id=:user', ['user' => $userId])['total'];
    }

    public function listApiForUser(int $userId, int $limit, int $offset): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, min(499950, $offset));
        return $this->fetchAll('SELECT id,checked_at,saved_at,selected_symptom_count,displayed_result_count
            FROM user_saved_symptom_checks WHERE user_id=:user ORDER BY checked_at DESC,id DESC LIMIT ' . $limit . ' OFFSET ' . $offset, ['user' => $userId]);
    }
}

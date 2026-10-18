<?php
declare(strict_types=1);
namespace App\Repositories;

use RuntimeException;
use Throwable;

class MedicationScheduleRepository extends BaseRepository
{
    /** Resolve the parent only inside an already authorized profile. Recheck under withMedicine's lock. */
    public function slotInProfile(int $profileId, int $slotId): ?array
    {
        return $this->fetch('SELECT s.* FROM health_medicine_schedule_slots s
            JOIN health_medicines m ON m.id=s.health_medicine_id
            WHERE s.id=:slot AND m.health_profile_id=:profile', ['slot'=>$slotId,'profile'=>$profileId]);
    }

    /** API callers authorize the profile before either read. */
    public function countApiSlots(int $profileId): int
    {
        $row=$this->fetch('SELECT COUNT(*) AS total FROM health_medicine_schedule_slots s
            JOIN health_medicines m ON m.id=s.health_medicine_id WHERE m.health_profile_id=:profile', ['profile'=>$profileId]);
        return (int)($row['total']??0);
    }
    public function listApiSlots(int $profileId, int $limit, int $offset): array
    {
        $limit=max(1,min(50,$limit)); $offset=max(0,min(499950,$offset));
        return $this->fetchAll('SELECT s.id,s.health_medicine_id,s.local_time,s.timezone,s.starts_on,s.ends_on,s.is_active
            FROM health_medicine_schedule_slots s JOIN health_medicines m ON m.id=s.health_medicine_id
            WHERE m.health_profile_id=:profile ORDER BY s.is_active DESC,s.local_time ASC,s.id ASC
            LIMIT '.$limit.' OFFSET '.$offset, ['profile'=>$profileId]);
    }

    /** Lock the parent and current membership for all schedule mutations. */
    public function withMedicine(int $profileId, int $medicineId, int $userId, callable $operation): mixed
    {
        $this->beginTransaction();
        try {
            $medicine = $this->fetch('SELECT m.*, pm.role FROM health_medicines m
                JOIN health_profiles p ON p.id=m.health_profile_id
                JOIN health_profile_members pm ON pm.health_profile_id=p.id
                WHERE m.id=:medicine AND p.id=:profile AND pm.user_id=:user
                  AND p.status=\'active\' AND pm.status=\'active\' FOR UPDATE',
                ['medicine'=>$medicineId, 'profile'=>$profileId, 'user'=>$userId]);
            if (!$medicine || !in_array($medicine['role'], ['owner','editor'], true)) {
                throw new RuntimeException('You do not have permission to modify this health record.');
            }
            $result = $operation($medicine);
            if (!$this->commit()) { throw new RuntimeException('Unable to commit medication schedule.'); }
            return $result;
        } catch (Throwable $exception) { $this->rollback(); throw $exception; }
    }

    public function slots(int $medicineId): array
    {
        return $this->fetchAll('SELECT * FROM health_medicine_schedule_slots WHERE health_medicine_id=:id ORDER BY is_active DESC, local_time, id', ['id'=>$medicineId]);
    }

    public function slot(int $medicineId, int $slotId): ?array
    {
        return $this->fetch('SELECT * FROM health_medicine_schedule_slots WHERE id=:id AND health_medicine_id=:medicine', ['id'=>$slotId,'medicine'=>$medicineId]);
    }

    public function profileSlots(int $profileId): array
    {
        return $this->fetchAll('SELECT s.*, m.brand_name, m.status, m.started_on FROM health_medicine_schedule_slots s
            JOIN health_medicines m ON m.id=s.health_medicine_id WHERE m.health_profile_id=:profile', ['profile'=>$profileId]);
    }

    public function create(array $data): int
    {
        $this->execute('INSERT INTO health_medicine_schedule_slots
            (health_medicine_id,local_time,timezone,starts_on,ends_on,is_active,created_by,created_at,updated_at)
            VALUES (:health_medicine_id,:local_time,:timezone,:starts_on,:ends_on,:is_active,:created_by,:created_at,:updated_at)', $data);
        return (int)$this->lastInsertId();
    }

    public function retire(int $id, string $now): void
    {
        $this->execute('UPDATE health_medicine_schedule_slots SET is_active=0, updated_at=:now WHERE id=:id', ['now'=>$now,'id'=>$id]);
    }

    public function checkoff(int $slotId, string $date): ?array
    {
        return $this->fetch('SELECT * FROM health_medicine_schedule_checkoffs WHERE schedule_id=:id AND scheduled_on=:day', ['id'=>$slotId,'day'=>$date]);
    }

    public function mark(array $data): void
    {
        $this->execute('INSERT INTO health_medicine_schedule_checkoffs
            (schedule_id,scheduled_on,state,medicine_label,recorded_by,recorded_at,created_at,updated_at)
            VALUES (:schedule_id,:scheduled_on,:state,:medicine_label,:recorded_by,:recorded_at,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE state=VALUES(state), recorded_by=VALUES(recorded_by),
                recorded_at=VALUES(recorded_at), updated_at=VALUES(updated_at)', $data);
    }

    public function history(int $profileId, int $offset): array
    {
        $offset=max(0,$offset);
        return $this->fetchAll('SELECT c.*, s.local_time, s.timezone FROM health_medicine_schedule_checkoffs c
            JOIN health_medicine_schedule_slots s ON s.id=c.schedule_id
            JOIN health_medicines m ON m.id=s.health_medicine_id
            WHERE m.health_profile_id=:profile ORDER BY c.scheduled_on DESC, s.local_time DESC, c.id DESC LIMIT 21 OFFSET '.$offset,
            ['profile'=>$profileId]);
    }
}

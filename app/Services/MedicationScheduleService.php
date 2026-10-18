<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\MedicationScheduleRepository;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class MedicationScheduleService
{
    public function __construct(private MedicationScheduleRepository $repository, private HealthMedicineService $medicines,
        private HealthProfileService $profiles, private AuthService $auth, private ApiHealthProfileService $apiProfiles) {}

    protected function now(): DateTimeImmutable { return new DateTimeImmutable('now', new DateTimeZone('UTC')); }
    private function timestamp(): string { return $this->now()->format('Y-m-d H:i:s'); }
    private function actor(): int
    {
        $id=$this->auth->id();
        if ($id===null) { throw new RuntimeException('Authentication required.'); }
        return $id;
    }
    public function profile(int $profileId): array { $this->actor(); return $this->profiles->getProfile($profileId); }
    public function medicine(int $profileId, int $medicineId): array { $this->actor(); return $this->medicines->getMedicine($profileId,$medicineId); }
    public function slots(int $profileId, int $medicineId): array
    {
        $this->medicine($profileId,$medicineId);
        return $this->repository->slots($medicineId);
    }
    public function slot(int $profileId, int $medicineId, int $slotId): array
    {
        $this->medicine($profileId,$medicineId);
        return $this->repository->slot($medicineId,$slotId) ?? throw new RuntimeException('Schedule not found.');
    }
    private function text(array $data, string $key): string
    {
        $value=$data[$key]??'';
        if (!is_string($value)) { throw new RuntimeException('Invalid schedule input.'); }
        return trim($value);
    }
    private function date(string $value): string
    {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value,new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d')!==$value || $value<'1000-01-01' || $value>'9999-12-31') {
            throw new RuntimeException('Enter a valid schedule date.');
        }
        return $value;
    }
    private function prepare(array $data): array
    {
        $time=$this->text($data,'local_time'); $zone=$this->text($data,'timezone');
        if (!preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/',$time)) { throw new RuntimeException('Enter a valid time in HH:MM format.'); }
        if (!in_array($zone,DateTimeZone::listIdentifiers(),true)) { throw new RuntimeException('Choose a valid timezone.'); }
        $start=$this->date($this->text($data,'starts_on')); $end=$this->text($data,'ends_on');
        $end=$end===''?null:$this->date($end);
        if ($end!==null && $end<$start) { throw new RuntimeException('Schedule end date cannot precede its start date.'); }
        if ($start<$this->now()->setTimezone(new DateTimeZone($zone))->format('Y-m-d')) {
            throw new RuntimeException('New or replacement schedules must start today or later.');
        }
        $active=$data['is_active']??'1';
        if (!in_array($active,['0','1'],true)) { throw new RuntimeException('Choose a valid schedule status.'); }
        return ['local_time'=>$time,'timezone'=>$zone,'starts_on'=>$start,'ends_on'=>$end,'is_active'=>(int)$active];
    }
    public function save(int $profileId, int $medicineId, array $data, ?int $slotId=null): int
    {
        $actor=$this->actor(); $this->medicine($profileId,$medicineId); $prepared=$this->prepare($data);
        return $this->repository->withMedicine($profileId,$medicineId,$actor,function(array $medicine) use ($prepared,$slotId,$medicineId,$actor): int {
            if ($medicine['status']!=='current') { throw new RuntimeException('Only current medicines can be scheduled.'); }
            if ($slotId!==null && !$this->repository->slot($medicineId,$slotId)) { throw new RuntimeException('Schedule not found.'); }
            foreach ($this->repository->slots($medicineId) as $slot) {
                if ((int)$slot['is_active']!==1 || (int)$slot['id']===$slotId || $prepared['is_active']!==1) { continue; }
                if ($slot['timezone']!==$prepared['timezone']) { throw new RuntimeException('Active times for a medicine must use the same timezone.'); }
                if ($slot['local_time']===$prepared['local_time']) { throw new RuntimeException('An active schedule already uses this time.'); }
            }
            $now=$this->timestamp();
            if ($slotId!==null) { $this->repository->retire($slotId,$now); }
            return $this->repository->create($prepared+['health_medicine_id'=>$medicineId,'created_by'=>$actor,'created_at'=>$now,'updated_at'=>$now]);
        });
    }
    /** Removal retires the slot so recorded history stays understandable. */
    public function remove(int $profileId, int $medicineId, int $slotId): void
    {
        $actor=$this->actor(); $this->medicine($profileId,$medicineId);
        $this->repository->withMedicine($profileId,$medicineId,$actor,function() use ($medicineId,$slotId): void {
            if (!$this->repository->slot($medicineId,$slotId)) { throw new RuntimeException('Schedule not found.'); }
            $this->repository->retire($slotId,$this->timestamp());
        });
    }
    private function eligible(array $slot, array $medicine): ?string
    {
        $day=$this->now()->setTimezone(new DateTimeZone($slot['timezone']))->format('Y-m-d');
        return $medicine['status']==='current' && (int)$slot['is_active']===1 && $day>=$slot['starts_on']
            && ($slot['ends_on']===null || $day<=$slot['ends_on'])
            && (empty($medicine['started_on']) || $day>=$medicine['started_on']) ? $day : null;
    }
    public function dueToday(int $profileId): array
    {
        $this->profile($profileId);
        return $this->assembleDueToday($profileId);
    }
    /** Bearer access never falls back to the browser actor. */
    public function dueTodayForPrincipal(ApiAuthPrincipal $principal, string $profileId): array
    {
        $profile=$this->apiProfiles->detail($principal,$profileId);
        return $this->assembleDueToday((int)$profile['id']);
    }
    private function assembleDueToday(int $profileId): array
    {
        $rows=[];
        foreach ($this->repository->profileSlots($profileId) as $slot) {
            $day=$this->eligible($slot,$slot);
            if ($day!==null) { $rows[]=$slot+['scheduled_on'=>$day,'state'=>$this->repository->checkoff((int)$slot['id'],$day)['state']??null]; }
        }
        usort($rows,static fn(array $a,array $b): int => [$a['local_time'],$a['brand_name'],(int)$a['id']] <=> [$b['local_time'],$b['brand_name'],(int)$b['id']]);
        return $rows;
    }
    public function summary(int $profileId): array
    {
        $this->profile($profileId); $counts=[];
        foreach ($this->repository->profileSlots($profileId) as $slot) {
            if ((int)$slot['is_active']===1) { $id=(int)$slot['health_medicine_id']; $counts[$id]=($counts[$id]??0)+1; }
        }
        return $counts;
    }
    public function mark(int $profileId, int $medicineId, int $slotId, mixed $day, mixed $state): void
    {
        $actor=$this->actor(); $this->medicine($profileId,$medicineId);
        if (!in_array($state,['taken','skipped'],true)) { throw new RuntimeException('Choose Taken or Skipped.'); }
        $this->repository->withMedicine($profileId,$medicineId,$actor,function(array $medicine) use ($medicineId,$slotId,$day,$state,$actor): void {
            $slot=$this->repository->slot($medicineId,$slotId) ?? throw new RuntimeException('Schedule not found.');
            $this->recordOccurrence($medicine,$slot,$actor,$state,$day,true);
        });
    }

    /** Server-today API action. Projection runs under the lock, before commit/publication. */
    public function markTodayForPrincipal(ApiAuthPrincipal $principal, string $profileId, int $slotId, string $state, callable $project): array
    {
        $profile=$this->apiProfiles->requireAccessible($principal,$profileId);
        if (!in_array($profile['role']??null,['owner','editor'],true)) {
            throw new ApiAuthException(403,'FORBIDDEN','You do not have permission to modify this health record.');
        }
        $slot=$this->repository->slotInProfile((int)$profile['id'],$slotId);
        if ($slot===null) { throw new ApiAuthException(404,'NOT_FOUND','Schedule not found.'); }
        $medicineId=(int)$slot['health_medicine_id'];
        try {
            return $this->repository->withMedicine((int)$profile['id'],$medicineId,$principal->userId,
                function(array $medicine) use ($medicineId,$slotId,$principal,$state,$project): array {
                    $slot=$this->repository->slot($medicineId,$slotId) ?? throw new RuntimeException('Schedule not found.');
                    return $project($this->recordOccurrence($medicine,$slot,$principal->userId,$state,null,false));
                });
        } catch (RuntimeException $e) {
            // Translate only known domain denials. Database/projection faults remain safe 500s.
            if (get_class($e)!==RuntimeException::class) { throw $e; }
            throw match ($e->getMessage()) {
                'Schedule not found.' => new ApiAuthException(404,'NOT_FOUND','Schedule not found.'),
                'You do not have permission to modify this health record.' => new ApiAuthException(403,'FORBIDDEN',$e->getMessage()),
                'Choose Taken or Skipped.' => new ApiAuthException(422,'VALIDATION_ERROR',$e->getMessage()),
                'This occurrence is no longer available for today. Reload Due Today.' => new ApiAuthException(409,'OCCURRENCE_UNAVAILABLE',$e->getMessage()),
                default => $e,
            };
        }
    }

    /** Shared browser/API eligibility and upsert. Caller holds the medicine/membership lock. */
    private function recordOccurrence(array $medicine, array $slot, int $actor, mixed $state, mixed $day, bool $requireDay): array
    {
        if (!in_array($state,['taken','skipped'],true)) { throw new RuntimeException('Choose Taken or Skipped.'); }
        $today=$this->eligible($slot,$medicine);
        if ($today===null || ($requireDay && $day!==$today)) {
            throw new RuntimeException('This occurrence is no longer available for today. Reload Due Today.');
        }
        $now=$this->timestamp();
        $this->repository->mark(['schedule_id'=>(int)$slot['id'],'scheduled_on'=>$today,'state'=>$state,'medicine_label'=>$medicine['brand_name'],
            'recorded_by'=>$actor,'recorded_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
        return $slot+['scheduled_on'=>$today,'state'=>$state];
    }
    public function history(int $profileId, int $page): array
    {
        $this->profile($profileId); $page=max(1,min(100000,$page));
        $rows=$this->repository->history($profileId,($page-1)*20);
        return ['rows'=>array_slice($rows,0,20),'page'=>$page,'has_next'=>count($rows)>20];
    }
}

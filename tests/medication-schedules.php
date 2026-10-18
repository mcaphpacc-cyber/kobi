<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/vendor/autoload.php';

use App\Services\MedicationScheduleService;
use App\Repositories\MedicationScheduleRepository;

$scheduleChecks=0;
function msCheck(bool $ok,string $message): void { global $scheduleChecks; if (!$ok) { throw new LogicException($message); } ++$scheduleChecks; }
function msDenied(callable $action): void { try { $action(); } catch (RuntimeException $e) { msCheck(true,'rejected'); return; } throw new LogicException('Expected rejection.'); }
class ScheduleTestAuth extends App\Services\AuthService {
    public ?int $uid=1;
    public function __construct() {}
    public function id(): ?int { return $this->uid; }
    public function check(): bool { return $this->uid!==null; }
    public function user(): ?array { return $this->uid===null?null:['id'=>$this->uid]; }
}
class ScheduleTestProfiles extends App\Services\HealthProfileService {
    public string $role='owner';
    public function __construct(public ScheduleTestAuth $auth) {}
    public function getProfile(int $id): array {
        if ($id!==10 || $this->auth->uid!==1) { throw new RuntimeException('Health profile not found.'); }
        return ['id'=>10,'role'=>$this->role,'full_name'=>'Synthetic profile'];
    }
}
class ScheduleTestMedicines extends App\Services\HealthMedicineService {
    public array $record=['id'=>20,'health_profile_id'=>10,'brand_name'=>'Synthetic medicine','status'=>'current','started_on'=>null];
    public function __construct(public ScheduleTestProfiles $profiles) {}
    public function getMedicine(int $profileId,int $id): array {
        $this->profiles->getProfile($profileId);
        if ($id!==20) { throw new RuntimeException('Medicine not found.'); }
        return $this->record;
    }
}
class ScheduleMemoryRepository extends MedicationScheduleRepository {
    public array $records=[]; public array $marks=[]; private int $next=0;
    public function __construct(public ScheduleTestMedicines $medicines) {}
    public function withMedicine(int $profileId,int $medicineId,int $userId,callable $operation): mixed {
        $profile=$this->medicines->profiles->getProfile($profileId);
        if ($userId!==1 || !in_array($profile['role'],['owner','editor'],true)) { throw new RuntimeException('You do not have permission to modify this health record.'); }
        return $operation($this->medicines->getMedicine($profileId,$medicineId));
    }
    public function slots(int $id): array { return array_values(array_filter($this->records,fn($r)=>(int)$r['health_medicine_id']===$id)); }
    public function slot(int $medicineId,int $slotId): ?array { $s=$this->records[$slotId]??null; return $s && (int)$s['health_medicine_id']===$medicineId?$s:null; }
    public function profileSlots(int $id): array { return array_map(fn($s)=>$s+$this->medicines->record,$this->slots(20)); }
    public function create(array $data): int { $id=++$this->next; $this->records[$id]=['id'=>$id]+$data; return $id; }
    public function retire(int $id,string $now): void { $this->records[$id]['is_active']=0; }
    public function checkoff(int $slotId,string $date): ?array { return $this->marks[$slotId.':'.$date]??null; }
    public function mark(array $data): void {
        $key=$data['schedule_id'].':'.$data['scheduled_on'];
        if (isset($this->marks[$key])) { $data['medicine_label']=$this->marks[$key]['medicine_label']; $data['created_at']=$this->marks[$key]['created_at']; }
        $this->marks[$key]=$data;
    }
    public function history(int $profileId,int $offset): array {
        return array_slice(array_map(fn($c)=>$c+$this->records[$c['schedule_id']],array_values($this->marks)),$offset,21);
    }
}
class ScheduleClockService extends MedicationScheduleService {
    public string $instant='2026-10-14T12:00:00Z';
    protected function now(): DateTimeImmutable { return new DateTimeImmutable($this->instant); }
}
class ScheduleTestSession extends App\Core\Session {
    public function __construct() {}
    public function verifyCsrfToken(?string $token): bool { return $token==='fixture-csrf'; }
}
class ScheduleTestController extends App\Controllers\MedicationScheduleController {
    public array $rendered=[];
    protected function view(string $view,array $data=[]): void { $this->rendered=[$view,$data]; }
}

$scheduleAuth=new ScheduleTestAuth(); $scheduleProfiles=new ScheduleTestProfiles($scheduleAuth);
$scheduleMedicines=new ScheduleTestMedicines($scheduleProfiles); $scheduleRepo=new ScheduleMemoryRepository($scheduleMedicines);
$scheduleApiProfiles=new App\Services\ApiHealthProfileService((new ReflectionClass(App\Repositories\HealthProfileRepository::class))->newInstanceWithoutConstructor());
$scheduleService=new ScheduleClockService($scheduleRepo,$scheduleMedicines,$scheduleProfiles,$scheduleAuth,$scheduleApiProfiles);
$scheduleInput=['local_time'=>'09:00','timezone'=>'UTC','starts_on'=>'2026-10-14','ends_on'=>'','is_active'=>'1'];
msCheck($scheduleService->dueToday(10)===[],'no schedule');
$slot=$scheduleService->save(10,20,$scheduleInput);
msCheck(count($scheduleService->slots(10,20))===1,'create');
msCheck(count($scheduleService->dueToday(10))===1 && $scheduleService->dueToday(10)[0]['state']===null,'today unmarked');
$slot2=$scheduleService->save(10,20,array_replace($scheduleInput,['local_time'=>'22:00']));
msCheck(count($scheduleService->dueToday(10))===2,'multiple daily times, including future time today');
msDenied(fn()=>$scheduleService->save(10,20,$scheduleInput));
msDenied(fn()=>$scheduleService->save(10,20,array_replace($scheduleInput,['timezone'=>'Asia/Kolkata','local_time'=>'10:00'])));
foreach ([['local_time'=>'24:00'],['local_time'=>['09:00']],['timezone'=>'Mars/Zone'],['starts_on'=>'2026-02-30'],['starts_on'=>'2026-10-13'],['ends_on'=>'2026-10-13'],['is_active'=>'2']] as $bad) {
    msDenied(fn()=>$scheduleService->save(10,20,array_replace($scheduleInput,$bad)));
}
msDenied(fn()=>$scheduleService->mark(10,20,$slot,'2026-10-13','taken'));
msDenied(fn()=>$scheduleService->mark(10,20,$slot,'2026-10-15','taken'));
msDenied(fn()=>$scheduleService->mark(10,20,$slot,'2026-10-14','TAKEN'));
msDenied(fn()=>$scheduleService->mark(10,20,999,'2026-10-14','taken'));
$scheduleService->mark(10,20,$slot,'2026-10-14','taken');
msCheck($scheduleService->dueToday(10)[0]['state']==='taken','Taken survives refresh');
$scheduleMedicines->record['brand_name']='Changed label';
$scheduleService->mark(10,20,$slot,'2026-10-14','skipped');
msCheck(count($scheduleRepo->marks)===1 && $scheduleService->dueToday(10)[0]['state']==='skipped','one occurrence correction');
msCheck($scheduleService->history(10,1)['rows'][0]['medicine_label']==='Synthetic medicine','original medicine label');
$replacement=$scheduleService->save(10,20,array_replace($scheduleInput,['local_time'=>'10:00']),$slot);
msCheck($replacement!==$slot && $scheduleRepo->records[$slot]['is_active']===0,'edit retires original');
msCheck($scheduleService->history(10,1)['rows'][0]['local_time']==='09:00','historical slot time retained');
$scheduleService->remove(10,20,$replacement);
msCheck(count($scheduleService->dueToday(10))===1 && count($scheduleRepo->marks)===1,'remove retains history');
msDenied(fn()=>$scheduleService->mark(10,20,$replacement,'2026-10-14','taken'));
$scheduleProfiles->role='viewer';
msCheck(count($scheduleService->dueToday(10))===1,'viewer can read');
foreach ([fn()=>$scheduleService->save(10,20,$scheduleInput),fn()=>$scheduleService->remove(10,20,$slot2),fn()=>$scheduleService->mark(10,20,$slot2,'2026-10-14','taken')] as $action) { msDenied($action); }
$scheduleProfiles->role='editor';
$scheduleService->mark(10,20,$slot2,'2026-10-14','taken'); msCheck(count($scheduleRepo->marks)===2,'editor mark');
$scheduleAuth->uid=2;
foreach ([fn()=>$scheduleService->save(10,20,$scheduleInput),fn()=>$scheduleService->slot(10,20,$slot),fn()=>$scheduleService->save(10,20,$scheduleInput,$slot),fn()=>$scheduleService->remove(10,20,$slot),fn()=>$scheduleService->dueToday(10),fn()=>$scheduleService->history(10,1),fn()=>$scheduleService->mark(10,20,$slot,'2026-10-14','taken')] as $action) { msDenied($action); }
$scheduleAuth->uid=null; msDenied(fn()=>$scheduleService->dueToday(10));
$scheduleAuth->uid=1; $scheduleProfiles->role='owner';
msDenied(fn()=>$scheduleService->slots(10,999)); msDenied(fn()=>$scheduleService->slots(11,20));
$scheduleMedicines->record['status']='stopped';
msCheck($scheduleService->dueToday(10)===[],'stopped excluded');
msDenied(fn()=>$scheduleService->save(10,20,$scheduleInput)); msDenied(fn()=>$scheduleService->mark(10,20,$slot2,'2026-10-14','taken'));
$scheduleMedicines->record['status']='historical'; msCheck($scheduleService->dueToday(10)===[],'historical excluded');
$scheduleMedicines->record['status']='current'; $scheduleMedicines->record['started_on']='2026-10-15';
msCheck($scheduleService->dueToday(10)===[],'medicine start lower bound'); $scheduleMedicines->record['started_on']=null;
$scheduleRepo->records[$slot2]['ends_on']='2026-10-14'; msCheck(count($scheduleService->dueToday(10))===1,'end inclusive');
$scheduleService->instant='2026-10-15T00:00:00Z'; msCheck($scheduleService->dueToday(10)===[],'end expired');
$scheduleRepo->records[$slot2]['ends_on']=null;
$scheduleRepo->records[$slot2]['timezone']='America/New_York';
msCheck($scheduleService->dueToday(10)[0]['scheduled_on']==='2026-10-14','local midnight differs from UTC');
$scheduleService->instant='2026-11-01T05:30:00Z'; $scheduleService->mark(10,20,$slot2,'2026-11-01','taken');
$scheduleService->instant='2026-11-01T06:30:00Z'; $scheduleService->mark(10,20,$slot2,'2026-11-01','skipped');
msCheck(count(array_filter($scheduleRepo->marks,fn($r)=>$r['scheduled_on']==='2026-11-01'))===1,'DST repeated hour single local day');
$scheduleController=new ScheduleTestController($scheduleAuth,new ScheduleTestSession(),$scheduleService);
foreach (['store','update','remove','mark'] as $action) {
    $_POST=['_csrf_token'=>'invalid']; $before=serialize([$scheduleRepo->records,$scheduleRepo->marks]);
    if ($action==='store') { $scheduleController->store(10,20); } else { $scheduleController->$action(10,20,$slot2); }
    msCheck(str_contains(serialize($scheduleController->rendered),'Invalid security token') && $before===serialize([$scheduleRepo->records,$scheduleRepo->marks]),$action.' CSRF denies writes');
}
echo "PASS: $scheduleChecks database-free schedule checks.\n";

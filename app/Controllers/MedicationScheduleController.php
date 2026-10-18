<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Session;
use App\Core\Logger;
use App\Services\AuthService;
use App\Services\MedicationScheduleService;
use RuntimeException;
use Throwable;

class MedicationScheduleController extends Controller
{
    public function __construct(private AuthService $auth, private Session $session, private MedicationScheduleService $service) {}
    private function authenticate(): void
    {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        if (!$this->auth->check() || $this->auth->user()===null) { redirect('/login'); }
    }
    private function csrf(): void
    {
        $token=$_POST['_csrf_token']??null;
        if (!is_string($token) || !$this->session->verifyCsrfToken($token)) { throw new RuntimeException('Invalid security token. Please try again.'); }
    }
    private function base(int $profileId): string { return '/account/health-records/'.$profileId.'/medicines'; }
    private function renderSchedule(int $profileId, int $medicineId, ?int $slotId=null, ?string $error=null): void
    {
        $profile=$this->service->profile($profileId); $medicine=$this->service->medicine($profileId,$medicineId);
        $slot=$slotId===null?null:$this->service->slot($profileId,$medicineId,$slotId);
        $this->view('account/medication-schedule', ['title'=>'Medication Schedule','profile'=>$profile,'medicine'=>$medicine,
            'slots'=>$this->service->slots($profileId,$medicineId),'slot'=>$slot,'error'=>$error,
            'form'=>$error!==null?array_intersect_key($_POST,array_flip(['local_time','timezone','starts_on','ends_on','is_active'])):[]]);
    }
    public function index(int $profileId, int $medicineId): void
    {
        $this->authenticate();
        try { $this->renderSchedule($profileId,$medicineId); } catch (Throwable $e) { $this->error($e); }
    }
    public function edit(int $profileId, int $medicineId, int $slotId): void
    {
        $this->authenticate();
        try { $this->renderSchedule($profileId,$medicineId,$slotId); } catch (Throwable $e) { $this->error($e); }
    }
    public function store(int $profileId, int $medicineId): void { $this->save($profileId,$medicineId); }
    public function update(int $profileId, int $medicineId, int $slotId): void { $this->save($profileId,$medicineId,$slotId); }
    private function save(int $profileId, int $medicineId, ?int $slotId=null): void
    {
        $this->authenticate();
        try { $this->csrf(); $this->service->save($profileId,$medicineId,$_POST,$slotId); }
        catch (Throwable $e) {
            if ($this->safe($e)) {
                try { http_response_code(400); $this->renderSchedule($profileId,$medicineId,$slotId,$e->getMessage()); }
                catch (Throwable $readError) { $this->error($readError); }
            } else { $this->error($e); }
            return;
        }
        redirect($this->base($profileId).'/'.$medicineId.'/schedule?saved=1');
    }
    public function remove(int $profileId, int $medicineId, int $slotId): void
    {
        $this->authenticate();
        try {
            $this->csrf();
            if (($_POST['confirm_remove']??null)!=='1') { throw new RuntimeException('Confirm schedule removal before continuing.'); }
            $this->service->remove($profileId,$medicineId,$slotId);
        } catch (Throwable $e) { $this->error($e); return; }
        redirect($this->base($profileId).'/'.$medicineId.'/schedule?removed=1');
    }
    public function dueToday(int $profileId): void
    {
        $this->authenticate();
        try { $this->view('account/medication-due-today',['title'=>'Due Today','profile'=>$this->service->profile($profileId),'occurrences'=>$this->service->dueToday($profileId)]); }
        catch (Throwable $e) { $this->error($e); }
    }
    public function mark(int $profileId, int $medicineId, int $slotId): void
    {
        $this->authenticate();
        try { $this->csrf(); $this->service->mark($profileId,$medicineId,$slotId,$_POST['scheduled_on']??null,$_POST['state']??null); }
        catch (Throwable $e) { $this->error($e); return; }
        redirect($this->base($profileId).'/due-today?recorded=1');
    }
    public function history(int $profileId): void
    {
        $this->authenticate();
        try {
            $page=filter_var($_GET['page']??1,FILTER_VALIDATE_INT)?:1;
            $this->view('account/medication-history',['title'=>'Medication History','profile'=>$this->service->profile($profileId),'listing'=>$this->service->history($profileId,$page)]);
        } catch (Throwable $e) { $this->error($e); }
    }
    private function safe(Throwable $e): bool
    {
        return get_class($e)===RuntimeException::class && in_array($e->getMessage(),[
            'Schedule not found.','Medicine not found.','Health profile not found.','You do not have permission to modify this health record.',
            'Invalid schedule input.','Enter a valid schedule date.','Enter a valid time in HH:MM format.','Choose a valid timezone.',
            'Schedule end date cannot precede its start date.','New or replacement schedules must start today or later.',
            'Choose a valid schedule status.','Only current medicines can be scheduled.','Active times for a medicine must use the same timezone.',
            'An active schedule already uses this time.','Choose Taken or Skipped.',
            'This occurrence is no longer available for today. Reload Due Today.','Invalid security token. Please try again.',
            'Confirm schedule removal before continuing.'],true);
    }
    private function error(Throwable $e): void
    {
        http_response_code($this->safe($e)?400:500);
        if (!$this->safe($e)) { Logger::error('Medication schedule request failed ('.get_class($e).').'); }
        $this->view('account/medication-schedule-error',['title'=>'Medication Schedule','message'=>$this->safe($e)?$e->getMessage():'Unable to load or save the medication schedule. Please try again.']);
    }
}

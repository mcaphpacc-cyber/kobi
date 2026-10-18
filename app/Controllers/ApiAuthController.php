<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\PublicApiResponse;
use App\Services\ApiAuthException;
use App\Services\ApiAuthGuard;
use App\Services\ApiAuthService;
use App\Services\ApiIdentityService;
use App\Services\ApiHealthProfileService;
use App\Services\ApiHealthMedicineService;
use App\Services\ApiMedicalSummaryService;
use App\Services\ApiHealthConditionService;
use App\Services\ApiMedicationScheduleService;
use App\Services\ApiSavedSymptomCheckService;

class ApiAuthController extends Controller
{
    public function __construct(private Request $request,private ApiAuthService $service,private ApiAuthGuard $guard,private ApiIdentityService $identity,private ApiHealthProfileService $profiles,private ApiHealthMedicineService $medicines,private ApiMedicalSummaryService $summary,private ApiHealthConditionService $conditions,private ApiMedicationScheduleService $schedules,private ApiSavedSymptomCheckService $savedChecks) {}

    private function input(array $allowed, array $required): array
    {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
        if ($this->request->allGet()!==[]) { throw new ApiAuthException(400,'BAD_REQUEST','Query parameters are not accepted.'); }
        $data=$this->request->authJson();
        if (array_diff(array_keys($data),$allowed)!==[]) { throw new ApiAuthException(422,'VALIDATION_ERROR','Invalid request fields.'); }
        foreach ($required as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || $data[$field]==='') { throw new ApiAuthException(422,'VALIDATION_ERROR','Required fields are missing or invalid.'); }
        }
        foreach ($data as $field=>$value) {
            $max=in_array($field,['password','current_password'],true)?1024:($field==='email'?255:($field==='device_label'?320:47));
            if (($value===null && $field==='device_label') || ($field==='device_label' && $value==='')) { $data[$field]=null;continue; }
            if (!is_string($value) || strlen($value)>$max || preg_match('/[\x00-\x1f\x7f]/',$value)) { throw new ApiAuthException(422,'VALIDATION_ERROR','Invalid request fields.'); }
            if ($field==='device_label' && mb_strlen($value,'UTF-8')>80) { throw new ApiAuthException(422,'VALIDATION_ERROR','Device label is too long.'); }
        }
        if (isset($data['email']) && !filter_var(trim($data['email']),FILTER_VALIDATE_EMAIL)) { throw new ApiAuthException(422,'VALIDATION_ERROR','Invalid email format.'); }
        return $data;
    }
    private function bearer(bool $logout): string
    { return $this->admittedBearer($logout)['token']; }

    /** Keep the existing extraction and admission path shared; resolve only once. */
    private function admittedBearer(bool $logout): array
    {
        $primary=$_SERVER['HTTP_AUTHORIZATION']??null;
        $redirect=$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??null;
        if ($primary!==null && $redirect!==null && $primary!==$redirect) { throw ApiAuthException::denied(); }
        $token=$this->guard->token(is_string($primary??$redirect)?($primary??$redirect):null);
        return ['token'=>$token,'principal'=>$this->guard->resolve($token,$logout)];
    }
    private function respond(callable $action, bool $paginated = false): void
    {
        try {
            $result = $action();
            PublicApiResponse::success($paginated ? $result['data'] : $result, $paginated ? $result['meta'] : []);
        }
        catch (ApiAuthException $e) {
            if ($e->status===401) { header('WWW-Authenticate: Bearer'); }
            if ($e->retryAfter>0) { header('Retry-After: '.$e->retryAfter); }
            PublicApiResponse::error($e->status,$e->errorCode,$e->getMessage());
        }
    }
    public function login(): void
    { $this->respond(function():array { $d=$this->input(['email','password','device_label'],['email','password']);return $this->service->login($d['email'],$d['password'],$d['device_label']??null); }); }
    public function refresh(): void
    { $this->respond(function():array { $d=$this->input(['refresh_token'],['refresh_token']);return $this->service->refresh($d['refresh_token']); }); }
    public function logout(): void
    { $this->respond(function():array { $this->input([],[]);$this->service->logout($this->bearer(true));return ['revoked'=>true]; }); }
    public function logoutAll(): void
    { $this->respond(function():array { $d=$this->input(['current_password'],['current_password']);$this->service->logoutAll($this->bearer(true),$d['current_password']);return ['revoked'=>true]; }); }

    public function me(): void
    {
        $this->respond(function():array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            if ($this->request->allGet()!==[]) { throw new ApiAuthException(400,'BAD_REQUEST','Query parameters are not accepted.'); }
            return $this->identity->forPrincipal($admitted['principal']);
        });
    }

    private function profileRead(?string $profileId): void
    {
        $this->respond(function()use($profileId):array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            if ($this->request->allGet()!==[]) { throw new ApiAuthException(400,'BAD_REQUEST','Query parameters are not accepted.'); }
            return $profileId===null ? $this->profiles->accessible($admitted['principal']) : $this->profiles->detail($admitted['principal'],$profileId);
        });
    }

    public function healthProfiles(): void { $this->profileRead(null); }
    public function healthProfile(string $profileId): void { $this->profileRead($profileId); }

    private function medicationRead(string $profileId,?string $medicationId): void
    {
        $this->respond(function()use($profileId,$medicationId):array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            if ($this->request->allGet()!==[]) { throw new ApiAuthException(400,'BAD_REQUEST','Query parameters are not accepted.'); }
            return $medicationId===null ? $this->medicines->collection($admitted['principal'],$profileId)
                : $this->medicines->detail($admitted['principal'],$profileId,$medicationId);
        });
    }

    public function medications(string $profileId): void { $this->medicationRead($profileId,null); }
    public function medication(string $profileId,string $medicationId): void { $this->medicationRead($profileId,$medicationId); }

    public function medicalSummary(string $profileId): void
    {
        $this->respond(function()use($profileId):array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            if ($this->request->allGet()!==[]) { throw new ApiAuthException(400,'BAD_REQUEST','Query parameters are not accepted.'); }
            return $this->summary->detail($admitted['principal'],$profileId);
        });
    }

    private function conditionRead(string $profileId, ?string $conditionId): void
    {
        $this->respond(function()use($profileId,$conditionId):array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            return $conditionId===null
                ? $this->conditions->collection($admitted['principal'],$profileId,$this->request->allGet())
                : $this->conditions->detail($admitted['principal'],$profileId,$conditionId,$this->request->allGet());
        }, $conditionId===null);
    }

    public function conditions(string $profileId): void { $this->conditionRead($profileId,null); }
    public function condition(string $profileId,string $conditionId): void { $this->conditionRead($profileId,$conditionId); }

    private function scheduleRead(string $profileId, bool $dueToday): void
    {
        $this->respond(function()use($profileId,$dueToday):array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            return $this->schedules->collection($admitted['principal'],$profileId,$this->request->allGet(),$dueToday);
        },true);
    }
    public function medicationSchedules(string $profileId): void { $this->scheduleRead($profileId,false); }
    public function medicationDueToday(string $profileId): void { $this->scheduleRead($profileId,true); }

    public function medicationCheckoff(string $profileId, string $scheduleId): void
    {
        $this->respond(function()use($profileId,$scheduleId):array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            if ($this->request->allGet()!==[]) { throw new ApiAuthException(400,'BAD_REQUEST','Query parameters are not accepted.'); }
            return $this->schedules->checkoff($admitted['principal'],$profileId,$scheduleId,$this->request->authJson());
        });
    }

    private function savedCheckRead(?string $savedCheckId): void
    {
        $this->respond(function()use($savedCheckId):array {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { throw new ApiAuthException(400,'BAD_REQUEST','HTTPS is required.'); }
            $admitted=$this->admittedBearer(false);
            return $savedCheckId===null
                ? $this->savedChecks->collection($admitted['principal'],$this->request->allGet())
                : $this->savedChecks->detail($admitted['principal'],$savedCheckId,$this->request->allGet());
        },$savedCheckId===null);
    }
    public function savedSymptomChecks(): void { $this->savedCheckRead(null); }
    public function savedSymptomCheck(string $savedCheckId): void { $this->savedCheckRead($savedCheckId); }
}

<?php
declare(strict_types=1);
namespace App\Core {
    class Logger { public static array $messages=[]; public static function error(string $message): void { self::$messages[]=$message; } }
}
namespace {
    if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
    spl_autoload_register(function(string $class): void {
        if (str_starts_with($class,'App\\')) { $file=dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php'; if (is_file($file)) { require_once $file; } }
    });
    class MobileRedirect extends RuntimeException {}
    function redirect(string $path): never { throw new MobileRedirect($path); }
    function e(?string $value): string { return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
    function url(string $path): string { return '/kobi/public'.$path; }
    function csrfField(): string { return '<input type="hidden" name="_csrf_token" value="fixture-csrf">'; }
    $mobileChecks=0;
    function mobileCheck(bool $ok,string $label): void { global $mobileChecks; if (!$ok) { throw new LogicException($label); } ++$mobileChecks; }
    function mobileDenied(callable $action): void { try { $action(); } catch (RuntimeException $e) { mobileCheck(true,'validation/auth rejection'); return; } throw new LogicException('Expected mobile rejection.'); }
    class MobileMemoryUsers extends App\Repositories\UserRepository {
        public array $rows=[1=>['id'=>1,'name'=>'Synthetic user','email'=>'synthetic@example.invalid','status'=>'active','mobile_number'=>null],2=>['id'=>2,'status'=>'active','mobile_number'=>null]];
        public int $writes=0; public ?Throwable $failure=null;
        public function __construct() {}
        public function findById(int $id): ?array { return $this->rows[$id]??null; }
        public function updateMobileNumber(int $userId,?string $mobileNumber): void { if ($this->failure) { throw $this->failure; } ++$this->writes; $this->rows[$userId]['mobile_number']=$mobileNumber; }
    }
    class MobileMemorySession extends App\Core\Session {
        public ?int $uid=1;
        public function get(string $key,mixed $default=null): mixed { return $key==='auth_user_id'?$this->uid:$default; }
        public function has(string $key): bool { return $key==='auth_user_id'&&$this->uid!==null; }
        public function verifyCsrfToken(?string $token): bool { return $token==='fixture-csrf'; }
    }
    class MobileIdentityFixture extends App\Services\UserAuthIdentityService {
        public function __construct() {}
        public function getForUser(int $userId): array { return []; }
        public function hasPassword(int $userId): bool { return true; }
    }
    class MobileAccountFixture extends App\Controllers\AccountController {
        public array $rendered=[];
        protected function view(string $view,array $data=[]): void { $this->rendered=[$view,$data]; }
    }
    function mobileSet(object $object,string $class,string $field,mixed $value): void { (new ReflectionProperty($class,$field))->setValue($object,$value); }
    $mobileRepo=new MobileMemoryUsers();$mobileSession=new MobileMemorySession();
    $mobileAuth=(new ReflectionClass(App\Services\AuthService::class))->newInstanceWithoutConstructor();
    mobileSet($mobileAuth,App\Services\AuthService::class,'userRepository',$mobileRepo); mobileSet($mobileAuth,App\Services\AuthService::class,'session',$mobileSession);
    foreach ([['',null],['   ',null],['+1 (202) 555-0100','+12025550100'],['  +91 99999-00000  ','+919999900000'],['+12','+12'],['+123456789012345','+123456789012345']] as [$input,$expected]) {
        $mobileAuth->updateMobileNumber($input);mobileCheck($mobileRepo->rows[1]['mobile_number']===$expected,'normalize/store');
        $mobileAuth->updateMobileNumber($input);mobileCheck($mobileRepo->rows[1]['mobile_number']===$expected,'repeated save');
    }
    foreach (['1234567890','0012025550100','+012345','+1','++12025550100','+1234567890123456','+12abc','+1.202.555.0100','+12025550100x1','+１２３','+12<script>','+12'.str_repeat(' ',63),['+12'],null,123] as $invalid) {
        $before=$mobileRepo->rows;mobileDenied(fn()=>$mobileAuth->updateMobileNumber($invalid));mobileCheck($before===$mobileRepo->rows,'invalid input writes nothing');
    }
    $mobileRepo->rows[2]['mobile_number']='+12025550100'; $other=$mobileRepo->rows[2];
    $mobileAuth->updateMobileNumber('+12025550100');mobileCheck($mobileRepo->rows[2]===$other,'duplicates permitted without identity transfer');
    $mobileController=(new ReflectionClass(MobileAccountFixture::class))->newInstanceWithoutConstructor();
    mobileSet($mobileController,App\Controllers\AccountController::class,'authService',$mobileAuth);
    mobileSet($mobileController,App\Controllers\AccountController::class,'session',$mobileSession);
    mobileSet($mobileController,App\Controllers\AccountController::class,'userAuthIdentityService',new MobileIdentityFixture());
    foreach ([null,'invalid',[]] as $token) {
        $_POST=['mobile_number'=>'+12025550101'];if ($token!==null) { $_POST['_csrf_token']=$token; }
        $before=$mobileRepo->rows;$mobileController->updateMobileNumber();
        mobileCheck($before===$mobileRepo->rows,'CSRF denies mutation');mobileCheck($mobileController->rendered[1]['mobileError']==='Invalid security token. Please try again.','safe CSRF message');
    }
    $_POST=['_csrf_token'=>'fixture-csrf','mobile_number'=>'+1 (202) 555-0101','user_id'=>2,'profile_id'=>999];
    try {$mobileController->updateMobileNumber();throw new LogicException('Expected redirect');}catch(MobileRedirect $e){mobileCheck($e->getMessage()==='/account?mobile_updated=1#profile','valid CSRF redirect');}
    mobileCheck($mobileRepo->rows[1]['mobile_number']==='+12025550101' && $mobileRepo->rows[2]===$other,'posted IDs ignored');
    $_POST=['_csrf_token'=>'fixture-csrf','mobile_number'=>'<script>alert(1)</script>'];$mobileController->updateMobileNumber();
    $user=$mobileRepo->rows[1];extract($mobileController->rendered[1],EXTR_SKIP);ob_start();require dirname(__DIR__).'/app/Views/account/mobile-number.php';$html=ob_get_clean();
    mobileCheck(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>'),'retained invalid input escaped');
    mobileCheck(str_contains($html,'role="alert"') && str_contains($html,'fixture-csrf'),'visible error and CSRF form');
    $mobileRepo->failure=new PDOException('PRIVATE INTERNAL SQL DETAIL');$mobileController->updateMobileNumber();
    // Invalid input still uses validation; exercise the repository error with valid input.
    $_POST=['_csrf_token'=>'fixture-csrf','mobile_number'=>'+12025550101'];$mobileController->updateMobileNumber();
    mobileCheck(!str_contains(serialize($mobileController->rendered),'PRIVATE INTERNAL') && count(App\Core\Logger::$messages)===1,'internal failure hidden and logged without content');$mobileRepo->failure=null;
    $mobileAuth->updateMobileNumber('');mobileCheck($mobileRepo->rows[1]['mobile_number']===null,'clear to NULL');
    $mobileSession->uid=null;mobileDenied(fn()=>$mobileAuth->updateMobileNumber('+12'));
    try {$mobileController->updateMobileNumber();throw new LogicException('Expected login redirect');}catch(MobileRedirect $e){mobileCheck($e->getMessage()==='/login','anonymous controller protection');}
    $mobileSession->uid=1;$mobileRepo->rows[1]['status']='inactive';$before=$mobileRepo->writes;
    mobileDenied(fn()=>$mobileAuth->updateMobileNumber('+12'));mobileCheck($mobileRepo->writes===$before,'inactive account cannot write');
    $share=(new ReflectionClass(App\Services\MedicalSummaryShareService::class))->newInstanceWithoutConstructor();
    $projection=(new ReflectionMethod($share,'buildPublicSnapshot'))->invoke($share,['profile'=>['full_name'=>'Synthetic','mobile_number'=>'+12025550101']]);
    mobileCheck(!str_contains(json_encode($projection),'mobile_number')&&!str_contains(json_encode($projection),'+12025550101'),'summary public projection excludes account phone');
    $route=file_get_contents(dirname(__DIR__).'/routes/web.php');
    mobileCheck(substr_count($route,"'/account/profile/mobile'")===1 && str_contains($route,"post('/account/profile/mobile'"),'only private POST mobile route');
    echo "PASS: $mobileChecks database-free mobile checks.\n";
}

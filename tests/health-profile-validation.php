<?php
declare(strict_types=1);

// Database-free service/controller/view checks; diagnostic logs stay in memory.
namespace App\Core {
    final class Logger {
        public static array $messages = [];
        public static function error(string $message): void { self::$messages[] = $message; }
    }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        }
    });
    function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function url(string $path = ''): string { return 'https://kobi.example.invalid' . $path; }
    function csrfField(): string { return '<input type="hidden" name="_csrf_token" value="fixture-csrf">'; }
    $checks = 0;
    function profileCheck(bool $ok, string $label): void {
        global $checks;
        if (!$ok) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
        ++$checks;
    }
    $redirectCheck = null;
    function redirect(string $path): never {
        global $redirectCheck;
        profileCheck(is_callable($redirectCheck), 'expected redirect');
        $redirectCheck($path);
        echo "PASS redirect scenario\n";
        exit(0);
    }
    class ProfileValidationAuth extends \App\Services\AuthService {
        public bool $active = true;
        public function __construct() {}
        public function id(): ?int { return $this->active ? 1 : null; }
        public function check(): bool { return $this->active; }
    }
    class ProfileValidationSession extends \App\Core\Session {
        public function __construct() {}
        public function get(string $key, mixed $default = null): mixed {
            return $key === '_csrf_token' ? 'fixture-csrf' : $default;
        }
    }
    class ProfileValidationRepository extends \App\Repositories\HealthProfileRepository {
        public int $writes = 0;
        public array $last = [];
        public string $role = 'owner';
        public bool $accessible = true;
        public ?\Throwable $failure = null;
        public function __construct() {}
        public function findAccessible(int $profileId, int $userId): ?array {
            return $this->accessible && $profileId === 10 && $userId === 1
                ? ['id' => 10, 'full_name' => 'Synthetic profile', 'role' => $this->role] : null;
        }
        public function createProfileWithOwner(string $type, string $name, ?string $dob, string $gender, int $userId): int {
            if ($this->failure) { throw $this->failure; }
            ++$this->writes; $this->last = compact('type', 'name', 'dob', 'gender', 'userId'); return 10;
        }
        public function updateProfile(int $profileId, int $userId, string $name, ?string $dob, string $gender): bool {
            if ($this->failure) { throw $this->failure; }
            ++$this->writes; $this->last = compact('profileId', 'userId', 'name', 'dob', 'gender'); return true;
        }
    }
    class ProfileValidationController extends \App\Controllers\AccountController {
        public array $rendered = [];
        protected function view(string $view, array $data = []): void { $this->rendered = [$view, $data]; }
    }
    function profileFixture(): array {
        $repository = new ProfileValidationRepository(); $auth = new ProfileValidationAuth();
        $service = new \App\Services\HealthProfileService($repository, $auth);
        $dependencies = [\App\Services\AuthService::class => $auth,
            \App\Services\HealthProfileService::class => $service, \App\Core\Session::class => new ProfileValidationSession()];
        $reflection = new \ReflectionClass(ProfileValidationController::class); $arguments = [];
        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $type = $parameter->getType()->getName();
            $arguments[] = $dependencies[$type] ?? (new \ReflectionClass($type))->newInstanceWithoutConstructor();
        }
        return [$repository, $auth, $service, $reflection->newInstanceArgs($arguments)];
    }
    function rejectedProfile(callable $action, string $message): void {
        try { $action(); } catch (\RuntimeException $exception) {
            profileCheck($exception->getMessage() === $message, $message); return;
        }
        profileCheck(false, 'validation must reject');
    }
    function profileRenderView(array $data): string {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require dirname(__DIR__) . '/app/Views/account/health-record-create.php';
            return ob_get_contents();
        } finally { ob_end_clean(); }
    }
    $today = new \DateTimeImmutable('today'); $date = $today->format('Y-m-d');
    $future = $today->modify('+1 day')->format('Y-m-d');
    $_POST = ['_csrf_token' => 'fixture-csrf', 'full_name' => 'Synthetic profile', 'date_of_birth' => $date, 'gender' => 'female'];

    if (isset($argv[1])) {
        [$repository, $auth, $service, $controller] = profileFixture();
        $scenario = $argv[1];
        $redirectCheck = static function (string $path) use ($repository, $scenario, $date): void {
            $expected = $scenario === 'anonymous' ? '/login' : ($scenario === 'create-success' ? '/account/health-records' : '/account/health-records/10');
            profileCheck($path === $expected, 'success/authorization destination');
            profileCheck($repository->writes === (in_array($scenario, ['create-success', 'update-success'], true) ? 1 : 0), 'memory persistence count');
            if ($repository->writes) { profileCheck($repository->last['dob'] === $date, 'successful DOB persisted unchanged'); }
        };
        if ($scenario === 'anonymous') { $auth->active = false; }
        if ($scenario === 'viewer') { $repository->role = 'viewer'; }
        if (in_array($scenario, ['create-success', 'anonymous'], true)) { $controller->storeHealthRecord(); }
        else { $controller->updateHealthRecord(10); }
        profileCheck(false, 'expected controller redirect');
    }

    $cases = [
        ['2000-02-29', null], [$date, null], [$today->modify('-1 day')->format('Y-m-d'), null],
        [null, null], ['', null], [' 2000-02-29 ', null],
        [$future, 'Date of birth cannot be in the future.'], ['2026-02-30', 'Invalid date format.'],
        ['not-a-date', 'Invalid date format.'], ['2026-2-01', 'Invalid date format.'],
        ["2000-01-\0", 'Invalid date format.']
    ];
    foreach (['create', 'edit'] as $operation) {
        foreach ($cases as [$dob, $error]) {
            [$repository, $auth, $service] = profileFixture();
            $action = static function () use ($service, $dob, $operation): void {
                if ($operation === 'create') { $service->createFamilyProfile('Synthetic', $dob, 'female'); }
                else { $service->updateProfile(10, 'Synthetic', $dob, 'female'); }
            };
            if ($error) { rejectedProfile($action, $error); profileCheck($repository->writes === 0, 'invalid DOB never persists'); }
            else { $action(); profileCheck($repository->last['dob'] === (trim($dob ?? '') ?: null), 'valid DOB normalized'); }
        }
    }
    [$repository, $auth, $service, $controller] = profileFixture();
    $repository->accessible = false;
    rejectedProfile(fn() => $service->updateProfile(10, 'Synthetic', $date, 'female'), 'Health profile not found.');
    profileCheck($repository->writes === 0, 'inaccessible profile never persists');
    $repository->accessible = true; $repository->role = 'viewer';
    rejectedProfile(fn() => $service->updateProfile(10, 'Synthetic', $date, 'female'), 'You do not have permission to edit this health profile.');
    profileCheck($repository->writes === 0, 'viewer cannot edit');
    $repository->role = 'editor'; $service->updateProfile(10, 'Synthetic', $date, 'female');
    profileCheck($repository->writes === 1, 'editor still permitted');

    foreach (['create', 'edit'] as $operation) {
        [$repository, $auth, $service, $controller] = profileFixture();
        $_POST['date_of_birth'] = $future;
        if ($operation === 'create') { $controller->storeHealthRecord(); } else { $controller->updateHealthRecord(10); }
        profileCheck($controller->rendered[1]['error'] === 'Date of birth cannot be in the future.', 'DOB error reaches view');
        $_POST['_csrf_token'] = 'invalid';
        if ($operation === 'create') { $controller->storeHealthRecord(); } else { $controller->updateHealthRecord(10); }
        profileCheck($controller->rendered[1]['error'] === 'Invalid security token. Please try again.' && $repository->writes === 0, 'CSRF rejection unchanged');
        $_POST['_csrf_token'] = 'fixture-csrf'; $_POST['date_of_birth'] = $date;
        $repository->failure = new \PDOException('SYNTHETIC_PRIVATE_SQL_DETAIL');
        if ($operation === 'create') { $controller->storeHealthRecord(); } else { $controller->updateHealthRecord(10); }
        profileCheck(!str_contains($controller->rendered[1]['error'], 'SYNTHETIC_PRIVATE'), 'internal exception hidden');
    }
    [$repository, $auth, $service, $controller] = profileFixture();
    $_POST = ['_csrf_token' => 'fixture-csrf', 'full_name' => 'Synthetic <script>alert(1)</script>', 'date_of_birth' => $future,
        'gender' => 'female', 'password' => 'PRIVATE', 'secret' => 'PRIVATE'];
    $controller->storeHealthRecord(); $data = $controller->rendered[1];
    profileCheck(array_keys($data['old']) === ['full_name', 'date_of_birth', 'gender'], 'only safe form fields retained');
    profileCheck($data['old']['full_name'] === $_POST['full_name'] && $data['old']['date_of_birth'] === $future && $data['old']['gender'] === 'female', 'submitted values retained');
    $html = profileRenderView($data);
    profileCheck(str_contains($html, 'role="alert"') && str_contains($html, $data['error']), 'visible error alert');
    profileCheck(str_contains($html, e($_POST['full_name'])) && !str_contains($html, '<script>'), 'retained values escaped');
    profileCheck(str_contains($html, 'value="' . $future . '"') && str_contains($html, 'value="female" selected'), 'DOB/gender retained in form');
    profileCheck(str_contains($html, 'name="_csrf_token" value="fixture-csrf"'), 'fresh CSRF field rendered');
    foreach (['create-success', 'update-success', 'anonymous', 'viewer'] as $scenario) {
        $process = proc_open([PHP_BINARY, __FILE__, $scenario], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        profileCheck(is_resource($process), 'scenario process');
        $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        profileCheck(proc_close($process) === 0 && $errors === '' && str_contains($output, 'PASS redirect scenario'), $scenario);
    }
    echo "{$checks} database-free health-profile checks and 4 redirect scenarios passed.\n";
}

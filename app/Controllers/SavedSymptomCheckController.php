<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Session;
use App\Core\Logger;
use App\Services\AuthService;
use App\Services\SavedSymptomCheckService;
use RuntimeException;
use Throwable;

class SavedSymptomCheckController extends Controller
{
    public function __construct(private AuthService $auth, private Session $session, private SavedSymptomCheckService $service) {}

    private function authenticate(): void
    {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        if (!$this->auth->check() || $this->auth->user() === null) { redirect('/login'); }
    }

    private function csrf(): void
    {
        $token = $_POST['_csrf_token'] ?? null;
        if (!is_string($token) || !$this->session->verifyCsrfToken($token)) { throw new RuntimeException('Invalid security token. Please try again.'); }
    }

    public function index(): void
    {
        $this->authenticate();
        try {
            $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
            $this->view('account/saved-symptom-checks', ['title' => 'Saved Symptom Checks', 'listing' => $this->service->page($page)]);
        } catch (Throwable $exception) { $this->error($exception); }
    }

    public function show(int $checkId): void
    {
        $this->authenticate();
        try { $this->view('account/saved-symptom-check', ['title' => 'Previous Symptom Check', 'check' => $this->service->get($checkId)]); }
        catch (Throwable $exception) { $this->error($exception); }
    }

    public function store(): void
    {
        $this->authenticate();
        try {
            $this->csrf();
            $key = $_POST['check_key'] ?? '';
            if (!is_string($key)) { throw new RuntimeException('Run a new check before saving.'); }
            $id = $this->service->save($key);
        } catch (Throwable $exception) { $this->error($exception); return; }
        redirect('/account/saved-symptom-checks/' . $id . '?saved=1');
    }

    public function delete(int $checkId): void
    {
        $this->authenticate();
        try {
            $this->csrf();
            if (($_POST['confirm_delete'] ?? null) !== '1') { throw new RuntimeException('Confirm deletion before continuing.'); }
            $this->service->delete($checkId);
        } catch (Throwable $exception) { $this->error($exception); return; }
        redirect('/account/saved-symptom-checks?deleted=1');
    }

    private function error(Throwable $exception): void
    {
        $safe = ['Run a new check before saving.', 'Saved symptom check not found.', 'Invalid security token. Please try again.',
            'Confirm deletion before continuing.', 'This saved symptom check is unavailable. You can delete it from your saved list.'];
        $message = 'Unable to load or save this symptom check. Please try again.';
        if (get_class($exception) === RuntimeException::class && in_array($exception->getMessage(), $safe, true)) {
            $message = $exception->getMessage();
            http_response_code($message === 'Saved symptom check not found.' ? 404 : 400);
        } else {
            http_response_code(500);
            Logger::error('Saved symptom check request failed (' . get_class($exception) . ').');
        }
        $this->view('account/saved-symptom-check-error', ['title' => 'Saved Symptom Checks', 'message' => $message]);
    }
}

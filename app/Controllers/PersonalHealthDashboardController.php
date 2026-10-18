<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\HealthDashboardService;

class PersonalHealthDashboardController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private HealthDashboardService $healthDashboardService
    ) {
    }

    public function index(): void
    {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        if (!$this->authService->check() || $this->authService->user() === null) {
            redirect('/login');
        }

        $this->view('account/health-dashboard', [
            'title' => 'Personal Health Dashboard',
            'dashboard' => $this->healthDashboardService->getDashboard($_GET['profile_id'] ?? null),
        ]);
    }
}

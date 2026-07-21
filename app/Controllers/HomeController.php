<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\HomeService;

class HomeController extends Controller
{
    public function __construct(
        private HomeService $service
    ) {
    }

    public function index(): void
    {
        $dashboard = $this->service->getDashboardData();

        $this->view('home/index', [
            'title'     => 'KOBI',
            'dashboard' => $dashboard,
        ]);
    }

    public function searchSuggestions(): void
    {
        $query = trim($_GET['q'] ?? '');

        if (strlen($query) < 2) {

            $this->json([]);

            return;

        }

        $results =
            $this->service
                ->searchSuggestions($query);

        $this->json($results);
    }
}
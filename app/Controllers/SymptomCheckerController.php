<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SymptomCheckerService;

class SymptomCheckerController extends Controller
{
    public function __construct(
        private readonly SymptomCheckerService $service
    ) {
    }

    public function index(): void
    {
        $popularSymptoms = $this->service->getPopularSymptoms();

        $this->view('symptom-checker.index', [
            'title' => 'Symptom Checker',
            'popularSymptoms' => $popularSymptoms,
        ]);
    }

    public function results(): void
    {
        $selectedSymptoms = $_POST['symptoms'] ?? [];

        $results = $this->service->checkSymptoms($selectedSymptoms);

        $this->view('symptom-checker.results', [
            'title' => 'Possible Conditions',
            'results' => $results,
            'selectedSymptoms' => $selectedSymptoms,
        ]);
    }
}
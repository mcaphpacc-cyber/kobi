<?php

declare(strict_types=1);

namespace App\Controllers;

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

        $this->view('symptom-checker/index', [
            'title' => 'Symptom Checker',
            'popularSymptoms' => $popularSymptoms,
        ]);
    }

    public function results(): void
    {
        $selectedSymptoms = $_POST['symptoms'] ?? [];

        $results = $this->service->checkSymptoms($selectedSymptoms);

        $this->view('symptom-checker/results', [
            'title' => 'Possible Conditions',
            'results' => $results,
            'selectedSymptoms' => $selectedSymptoms,
        ]);
    }

    public function search(): void
    {
        $keyword = trim($_GET['q'] ?? '');

        if ($keyword === '') {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $results = $this->service->search($keyword);

        header('Content-Type: application/json');

        echo json_encode($results);
    }

    public function match(): void
    {
        $ids = $_GET['symptoms'] ?? '';

        if ($ids === '') {

            header('Content-Type: application/json');

            echo json_encode([]);

            return;
        }

        $symptomIds = array_map(
            'intval',
            explode(',', $ids)
        );

        $this->service->recordSearch($symptomIds);

        $results = $this->service->match($symptomIds);

        header('Content-Type: application/json');

        echo json_encode($results);
    }
}
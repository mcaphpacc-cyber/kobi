<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DiseaseService;

class DiseaseController extends Controller
{
    public function __construct(
        private DiseaseService $service
    ) {
    }

    public function index(): void
    {
        $language = config('locale');

        $diseases = $this->service->getAll($language);

        $this->view('disease/index', [
            'title' => 'Diseases',
            'diseases' => $diseases
        ]);
    }
}
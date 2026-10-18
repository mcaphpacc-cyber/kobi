<?php

declare(strict_types=1);

namespace App\Controllers;

class SubstanceController extends Controller
{
    public function index(): void
    {
        try {
            $query = \App\Services\PublicSearchService::normalize($_GET['q'] ?? '');
        } catch (\InvalidArgumentException) {
            $query = '';
        }
        $this->view('substances/index', [
            'title' => 'Medicines — Substance-name search',
            'publicSubstanceSearch' => true,
            'query' => $query,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DiseaseService;

class ComparisonController extends Controller
{
    private const DEFAULT_LANGUAGE = 'en';

    public function __construct(
        private DiseaseService $service
    ) {
    }

    /**
     * Compare selector page.
     */
    public function index(): void
    {
        $leftSlug = trim(
            (string) ($_GET['left'] ?? '')
        );

        $leftDisease = null;

        if ($leftSlug !== '') {

            $leftDisease = $this->service->getKnowledgeBySlug(
                $leftSlug,
                self::DEFAULT_LANGUAGE
            );

        }

        $this->view(
            'comparison/index',
            [
                'title' => 'Compare Diseases',
                'leftDisease' => $leftDisease,
            ]
        );
    }

    /**
     * Comparison result page.
     */
    public function result(): void
    {
        $leftSlug = trim(
            (string) ($_GET['d1'] ?? '')
        );

        $rightSlug = trim(
            (string) ($_GET['d2'] ?? '')
        );

        if (
            $leftSlug === '' ||
            $rightSlug === ''
        ) {
            redirect('/compare');

            return;
        }

        $comparison = $this->service->getComparison(
            $leftSlug,
            $rightSlug,
            self::DEFAULT_LANGUAGE
        );

        if (!$comparison) {
            abort(404);
        }

        $this->view(
            'comparison/result',
            [
                'title' => 'Disease Comparison',
                'comparison' => $comparison,
            ]
        );
    }
}
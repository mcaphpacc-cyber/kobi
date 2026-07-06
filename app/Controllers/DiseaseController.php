<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DiseaseService;



class DiseaseController extends Controller
{
    private const DEFAULT_LANGUAGE = 'en';
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

    public function show(
        string $slug
    ): void
    {
        $knowledge = $this->service
            ->getKnowledgeBySlug(
                $slug,
                self::DEFAULT_LANGUAGE
            );

        if (!$knowledge)
        {
            abort(404);
        }

        $this->view(

            'disease/show',

            [

                'title' =>

                    $knowledge['disease']['disease_en'],

                'knowledge' => $knowledge

            ]

        );
    }
}
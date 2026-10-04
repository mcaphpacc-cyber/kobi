<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Disease\DiseaseAdminService;
use RuntimeException;

class DiseaseAdminController extends Controller
{
    public function __construct(
        private DiseaseAdminService $service,
        private AuthService $authService
    ) {
    }

    public function edit(
        int $diseaseId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $disease =
            $this->service
                ->getDisease($diseaseId);

        if (!$disease)
        {
            abort(
                404,
                'Disease not found.'
            );
        }

        $this->view(
            'admin/disease-edit',
            [
                'title' =>
                    'Edit Disease - ' .
                    $disease['disease_en'],

                'disease' => $disease
            ]
        );
    }

    public function update(
        int $diseaseId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->service
                ->updateSeverityAndUrgency(
                    $diseaseId,
                    $_POST
                );

            redirect(
                '/admin/diseases/' .
                $diseaseId .
                '/edit?saved=1'
            );
        }
        catch (RuntimeException $exception)
        {
            $disease =
                $this->service
                    ->getDisease($diseaseId);

            $this->view(
                'admin/disease-edit',
                [
                    'title' =>
                        'Edit Disease - ' .
                        ($disease['disease_en'] ?? ''),

                    'disease' => $disease,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    private function verifyCsrf(): void
    {
        $token =
            $_POST['_csrf_token'] ?? '';

        if (
            !$this->session
                ->verifyCsrfToken($token)
        ) {
            throw new RuntimeException(
                'Invalid security token. Please try again.'
            );
        }
    }
}
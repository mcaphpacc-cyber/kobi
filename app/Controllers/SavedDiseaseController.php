<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\AuthService;
use App\Services\SavedDiseaseService;
use RuntimeException;

class SavedDiseaseController extends Controller
{
    public function __construct(
        private SavedDiseaseService $service,
        private AuthService $authService,
        private Session $session
    ) {
    }

    public function save(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $diseaseId = (int) (
                $_POST['disease_id'] ?? 0
            );

            if ($diseaseId <= 0)
            {
                throw new RuntimeException(
                    'Invalid disease.'
                );
            }

            $this->service->save(
                $diseaseId
            );

            $this->redirectBack();
        }
        catch (RuntimeException $exception)
        {
            abort(400, $exception->getMessage());
        }
    }

    public function remove(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $diseaseId = (int) (
                $_POST['disease_id'] ?? 0
            );

            if ($diseaseId <= 0)
            {
                throw new RuntimeException(
                    'Invalid disease.'
                );
            }

            $this->service->remove(
                $diseaseId
            );

            $this->redirectBack();
        }
        catch (RuntimeException $exception)
        {
            abort(400, $exception->getMessage());
        }
    }

    private function verifyCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? '';

        if (
            !$this->session->verifyCsrfToken(
                $token
            )
        ) {
            throw new RuntimeException(
                'Invalid security token. Please try again.'
            );
        }
    }

    private function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referer !== '') {

            $parts = parse_url($referer);

            $path = $parts['path'] ?? '';

            $basePath = parse_url(
                url('/'),
                PHP_URL_PATH
            );

            $basePath = rtrim(
                $basePath ?? '/',
                '/'
            );

            if (
                $path !== '' &&
                $basePath !== '' &&
                str_starts_with(
                    $path,
                    $basePath
                )
            ) {
                $path = substr(
                    $path,
                    strlen($basePath)
                );
            }

            $path = '/' . ltrim(
                $path,
                '/'
            );

            if (!empty($parts['query'])) {
                $path .= '?' . $parts['query'];
            }

            redirect($path);
        }

        redirect('/diseases');
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\AuthService;
use RuntimeException;
use App\Services\SavedDiseaseService;
use App\Services\TreatmentPreferenceService;

class AccountController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private Session $session,
        private SavedDiseaseService $savedDiseaseService,
        private TreatmentPreferenceService $treatmentPreferenceService
    ) {
    }

    public function index(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $user = $this->authService->user();

        $this->view(
            'account/index',
            [
                'title' => 'My Account',
                'user'  => $user
            ]
        );
    }

    public function savedDiseases(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $savedDiseases =
            $this->savedDiseaseService
                ->getSavedDiseases();

        $this->view(
            'account/saved-diseases',
            [
                'title' => 'Saved Diseases',
                'savedDiseases' => $savedDiseases
            ]
        );
    }

    public function updateProfile(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->authService->updateProfile(
                $_POST['name'] ?? '',
                $_POST['email'] ?? ''
            );

            redirect('/account?profile_updated=1');

        }
        catch (RuntimeException $exception)
        {
            $user = $this->authService->user();

            $this->view(
                'account/index',
                [
                    'title' => 'My Account',
                    'user'  => $user,
                    'error' => $exception->getMessage()
                ]
            );
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

    public function changePassword(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->authService->changePassword(
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? '',
                $_POST['new_password_confirmation'] ?? ''
            );

            redirect('/account?password_updated=1');

        }
        catch (RuntimeException $exception)
        {
            $user = $this->authService->user();

            $this->view(
                'account/index',
                [
                    'title' => 'My Account',
                    'user'  => $user,
                    'passwordError' => $exception->getMessage()
                ]
            );
        }
    }

    public function treatmentPreferences(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $treatmentSystems =
            $this->treatmentPreferenceService
                ->getTreatmentSystems();

        $preferences =
            $this->treatmentPreferenceService
                ->getPreferences();

        $this->view(
            'account/treatment-preferences',
            [
                'title' => 'Treatment Preferences',
                'treatmentSystems' => $treatmentSystems,
                'preferences' => $preferences
            ]
        );
    }

    public function saveTreatmentPreferences(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $this->verifyCsrf();

        $order =
            $_POST['treatment_system_ids'] ?? [];

        if (!is_array($order))
        {
            $order = [];
        }

        $this->treatmentPreferenceService
            ->savePreferences($order);

        redirect('/account/treatment-preferences');
    }

    public function resetTreatmentPreferences(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $this->verifyCsrf();

        $this->treatmentPreferenceService
            ->resetPreferences();

        redirect('/account/treatment-preferences');
    }
}
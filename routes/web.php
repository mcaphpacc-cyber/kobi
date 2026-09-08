<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\DiseaseController;
use App\Controllers\DiscoveryController;
use App\Controllers\SymptomCheckerController;
use App\Controllers\ComparisonController;
use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\SavedDiseaseController;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

$this->router()->get(
    '/',
    HomeController::class,
    'index'
);


$this->router()->get(
    '/diseases',
    DiseaseController::class,
    'index'
);

$this->router()->get(
    '/symptom-checker',
    SymptomCheckerController::class,
    'index'
);

$this->router()->get(
    '/api/symptoms',
    SymptomCheckerController::class,
    'search'
);

$this->router()->get(
    '/api/symptom-checker',
    SymptomCheckerController::class,
    'match'
);

$this->router->get(
    '/disease/{slug}',
    DiseaseController::class, 'show'
);

$this->router()->get(
    '/api/diseases/search',
    DiseaseController::class,
    'search'
);

$this->router()->get(
    '/compare',
    ComparisonController::class,
    'index'
);

$this->router()->get(
    '/compare/result',
    ComparisonController::class,
    'result'
);

$this->router()->get(
    '/api/search/suggestions',
    HomeController::class,
    'searchSuggestions'
);

$this->router()->get(
    '/discovery',
    DiscoveryController::class,
    'index'
);

$this->router()->get(
    '/body-parts',
    BodyPartController::class,
    'index'
);

$this->router()->get(
    '/body-parts/{slug}',
    BodyPartController::class,
    'show'
);

// Authentication
$this->router()->get(
    '/register',
    AuthController::class,
    'register'
);

$this->router()->post(
    '/register',
    AuthController::class,
    'store'
);

$this->router()->get(
    '/login',
    AuthController::class,
    'login'
);

$this->router()->post(
    '/login',
    AuthController::class,
    'authenticate'
);

$this->router()->post(
    '/logout',
    AuthController::class,
    'logout'
);

// Account
$this->router()->get(
    '/account',
    AccountController::class,
    'index'
);

$this->router()->post(
    '/account/profile',
    AccountController::class,
    'updateProfile'
);

$this->router()->post(
    '/account/password',
    AccountController::class,
    'changePassword'
);

// Saved Diseases
$this->router()->post(
    '/account/saved-diseases/save',
    SavedDiseaseController::class,
    'save'
);

$this->router()->post(
    '/account/saved-diseases/remove',
    SavedDiseaseController::class,
    'remove'
);

$this->router()->get(
    '/account/saved-diseases',
    AccountController::class,
    'savedDiseases'
);

$this->router()->get(
    '/account/treatment-preferences',
    AccountController::class,
    'treatmentPreferences'
);

$this->router()->post(
    '/account/treatment-preferences',
    AccountController::class,
    'saveTreatmentPreferences'
);

$this->router()->post(
    '/account/treatment-preferences/reset',
    AccountController::class,
    'resetTreatmentPreferences'
);

$this->router()->get(
    '/account/health-records',
    AccountController::class,
    'healthRecords'
);

$this->router()->get(
    '/account/health-records/create',
    AccountController::class,
    'createHealthRecord'
);

$this->router()->post(
    '/account/health-records',
    AccountController::class,
    'storeHealthRecord'
);

$this->router()->get(
    '/account/health-records/{id}/edit',
    AccountController::class,
    'editHealthRecord'
);

$this->router()->post(
    '/account/health-records/{id}',
    AccountController::class,
    'updateHealthRecord'
);

$this->router()->get(
    '/account/health-records/{id}',
    AccountController::class,
    'healthRecord'
);

$this->router()->get(
    '/account/health-records/{profileId}/conditions',
    AccountController::class,
    'healthConditions'
);

$this->router()->get(
    '/account/health-records/{profileId}/conditions/create',
    AccountController::class,
    'createHealthCondition'
);

$this->router()->post(
    '/account/health-records/{profileId}/conditions',
    AccountController::class,
    'storeHealthCondition'
);

$this->router()->get(
    '/account/health-records/{profileId}/conditions/{conditionId}/edit',
    AccountController::class,
    'editHealthCondition'
);

$this->router()->post(
    '/account/health-records/{profileId}/conditions/{conditionId}/update',
    AccountController::class,
    'updateHealthCondition'
);

$this->router()->post(
    '/account/health-records/{profileId}/conditions/{conditionId}/delete',
    AccountController::class,
    'deleteHealthCondition'
);

// Health Record - Medicines
$this->router()->get(
    '/account/health-records/{profileId}/medicines',
    AccountController::class,
    'healthMedicines'
);

$this->router()->get(
    '/account/health-records/{profileId}/medicines/create',
    AccountController::class,
    'createHealthMedicine'
);

$this->router()->post(
    '/account/health-records/{profileId}/medicines',
    AccountController::class,
    'storeHealthMedicine'
);

$this->router()->get(
    '/account/health-records/{profileId}/medicines/{medicineId}/edit',
    AccountController::class,
    'editHealthMedicine'
);

$this->router()->post(
    '/account/health-records/{profileId}/medicines/{medicineId}/update',
    AccountController::class,
    'updateHealthMedicine'
);

$this->router()->post(
    '/account/health-records/{profileId}/medicines/{medicineId}/delete',
    AccountController::class,
    'deleteHealthMedicine'
);

// Health Record - Allergies
$this->router()->get(
    '/account/health-records/{profileId}/allergies',
    AccountController::class,
    'healthAllergies'
);

$this->router()->get(
    '/account/health-records/{profileId}/allergies/create',
    AccountController::class,
    'createHealthAllergy'
);

$this->router()->post(
    '/account/health-records/{profileId}/allergies',
    AccountController::class,
    'storeHealthAllergy'
);

$this->router()->get(
    '/account/health-records/{profileId}/allergies/{allergyId}/edit',
    AccountController::class,
    'editHealthAllergy'
);

$this->router()->post(
    '/account/health-records/{profileId}/allergies/{allergyId}/update',
    AccountController::class,
    'updateHealthAllergy'
);

$this->router()->post(
    '/account/health-records/{profileId}/allergies/{allergyId}/delete',
    AccountController::class,
    'deleteHealthAllergy'
);

// Health Record - Procedures
$this->router()->get(
    '/account/health-records/{profileId}/procedures',
    AccountController::class,
    'healthProcedures'
);

$this->router()->get(
    '/account/health-records/{profileId}/procedures/create',
    AccountController::class,
    'createHealthProcedure'
);

$this->router()->post(
    '/account/health-records/{profileId}/procedures',
    AccountController::class,
    'storeHealthProcedure'
);

$this->router()->get(
    '/account/health-records/{profileId}/procedures/{procedureId}/edit',
    AccountController::class,
    'editHealthProcedure'
);

$this->router()->post(
    '/account/health-records/{profileId}/procedures/{procedureId}/update',
    AccountController::class,
    'updateHealthProcedure'
);

$this->router()->post(
    '/account/health-records/{profileId}/procedures/{procedureId}/delete',
    AccountController::class,
    'deleteHealthProcedure'
);

// Health Record - Medical Timeline
$this->router()->get(
    '/account/health-records/{profileId}/timeline',
    AccountController::class,
    'healthTimeline'
);

// Health Record - Medical Summary
$this->router()->get(
    '/account/health-records/{profileId}/medical-summary',
    AccountController::class,
    'medicalSummary'
);

// Health Record - Medical Summary Sharing

$this->router()->get(
    '/account/health-records/{profileId}/medical-summary/share',
    AccountController::class,
    'createMedicalSummaryShare'
);

$this->router()->post(
    '/account/health-records/{profileId}/medical-summary/share',
    AccountController::class,
    'storeMedicalSummaryShare'
);

$this->router()->get(
    '/account/health-records/{profileId}/medical-summary/shares',
    AccountController::class,
    'medicalSummaryShares'
);

$this->router()->post(
    '/account/health-records/{profileId}/medical-summary/shares/{shareId}/revoke',
    AccountController::class,
    'revokeMedicalSummaryShare'
);

// Public Medical Summary Share

$this->router()->get(
    '/shared/medical-summary/{token}',
    AccountController::class,
    'sharedMedicalSummary'
);
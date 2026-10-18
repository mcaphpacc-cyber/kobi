<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\DiseaseController;
use App\Controllers\DiscoveryController;
use App\Controllers\SymptomCheckerController;
use App\Controllers\ComparisonController;
use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\PersonalHealthDashboardController;
use App\Controllers\SavedSymptomCheckController;
use App\Controllers\MedicationScheduleController;

$this->router()->post('/account/profile/mobile', AccountController::class, 'updateMobileNumber');

use App\Controllers\SavedDiseaseController;
use App\Controllers\HealthInsuranceController;
use App\Controllers\GoogleAuthController;
use App\Controllers\TreatmentAdminController;
use App\Controllers\TreatmentReviewController;
use App\Controllers\MedicineOwnerBootstrapController;
use App\Controllers\SubstanceController;
use App\Controllers\PublicSearchController;
use App\Controllers\PublicDiseaseApiController;
use App\Controllers\PublicSubstanceApiController;
use App\Controllers\ApiAuthController;

$this->router()->post('/api/v1/auth/login', ApiAuthController::class, 'login');
$this->router()->post('/api/v1/auth/refresh', ApiAuthController::class, 'refresh');
$this->router()->post('/api/v1/auth/logout', ApiAuthController::class, 'logout');
$this->router()->post('/api/v1/auth/logout-all', ApiAuthController::class, 'logoutAll');
$this->router()->get('/api/v1/me', ApiAuthController::class, 'me');
$this->router()->get('/api/v1/health-profiles', ApiAuthController::class, 'healthProfiles');
$this->router()->get('/api/v1/health-profiles/{profileId}', ApiAuthController::class, 'healthProfile');
$this->router()->get('/api/v1/health-profiles/{profileId}/medications', ApiAuthController::class, 'medications');
$this->router()->get('/api/v1/health-profiles/{profileId}/medications/{medicationId}', ApiAuthController::class, 'medication');
$this->router()->get('/api/v1/health-profiles/{profileId}/medical-summary', ApiAuthController::class, 'medicalSummary');
$this->router()->get('/api/v1/health-profiles/{profileId}/conditions', ApiAuthController::class, 'conditions');
$this->router()->get('/api/v1/health-profiles/{profileId}/conditions/{conditionId}', ApiAuthController::class, 'condition');
$this->router()->get('/api/v1/health-profiles/{profileId}/medication-schedules/due-today', ApiAuthController::class, 'medicationDueToday');
$this->router()->get('/api/v1/health-profiles/{profileId}/medication-schedules', ApiAuthController::class, 'medicationSchedules');
$this->router()->post('/api/v1/health-profiles/{profileId}/medication-schedules/{scheduleId}/checkoff', ApiAuthController::class, 'medicationCheckoff');
$this->router()->get('/api/v1/saved-symptom-checks', ApiAuthController::class, 'savedSymptomChecks');
$this->router()->get('/api/v1/saved-symptom-checks/{savedCheckId}', ApiAuthController::class, 'savedSymptomCheck');

$this->router()->get('/api/v1/diseases', PublicDiseaseApiController::class, 'index');
$this->router()->get('/api/v1/diseases/search', PublicDiseaseApiController::class, 'search');
$this->router()->get('/api/v1/diseases/{slug}', PublicDiseaseApiController::class, 'show');
$this->router()->get('/api/v1/substances/search', PublicSubstanceApiController::class, 'search');

$this->router()->get('/search', PublicSearchController::class, 'index');

$this->router()->get('/medicines', SubstanceController::class, 'index');

// Personal Medication schedules: profile authorization is rechecked by the service.
$this->router()->get('/account/health-records/{profileId}/medicines/due-today', MedicationScheduleController::class, 'dueToday');
$this->router()->get('/account/health-records/{profileId}/medicines/history', MedicationScheduleController::class, 'history');
$this->router()->get('/account/health-records/{profileId}/medicines/{medicineId}/schedule', MedicationScheduleController::class, 'index');
$this->router()->post('/account/health-records/{profileId}/medicines/{medicineId}/schedule', MedicationScheduleController::class, 'store');
$this->router()->get('/account/health-records/{profileId}/medicines/{medicineId}/schedule/{slotId}/edit', MedicationScheduleController::class, 'edit');
$this->router()->post('/account/health-records/{profileId}/medicines/{medicineId}/schedule/{slotId}/update', MedicationScheduleController::class, 'update');
$this->router()->post('/account/health-records/{profileId}/medicines/{medicineId}/schedule/{slotId}/remove', MedicationScheduleController::class, 'remove');
$this->router()->post('/account/health-records/{profileId}/medicines/{medicineId}/schedule/{slotId}/mark', MedicationScheduleController::class, 'mark');

// Fixed owner only; GET is read-only, POST uses the existing protected service.
$this->router()->get('/admin/medicine/owner-bootstrap', MedicineOwnerBootstrapController::class, 'show');
$this->router()->post('/admin/medicine/owner-bootstrap', MedicineOwnerBootstrapController::class, 'store');

// Internal review decisions never publish content.
$this->router()->get('/admin/treatments/review', TreatmentReviewController::class, 'index');
$this->router()->get('/admin/treatments/{protocolId}/review', TreatmentReviewController::class, 'show');
$this->router()->post('/admin/treatments/{protocolId}/approve', TreatmentReviewController::class, 'approve');
$this->router()->post('/admin/treatments/{protocolId}/changes-required', TreatmentReviewController::class, 'changesRequired');
$this->router()->post('/admin/treatments/{protocolId}/risk-eligibility', TreatmentReviewController::class, 'riskEligibility');

// Internal treatment editorial routes; every action checks scoped authorization.
$this->router()->get('/admin/treatments', TreatmentAdminController::class, 'index');
$this->router()->get('/admin/treatments/operations', TreatmentAdminController::class, 'operations');
$this->router()->get('/admin/treatments/{protocolId}/operations', TreatmentAdminController::class, 'operation');
$this->router()->post('/admin/treatments/{protocolId}/withdraw', TreatmentAdminController::class, 'withdraw');
$this->router()->post('/admin/treatments/{protocolId}/reset-review', TreatmentAdminController::class, 'resetReview');
$this->router()->get('/admin/treatments/create', TreatmentAdminController::class, 'create');
$this->router()->post('/admin/treatments/create', TreatmentAdminController::class, 'store');
$this->router()->get('/admin/treatments/{protocolId}/items/create', TreatmentAdminController::class, 'createItem');
$this->router()->post('/admin/treatments/{protocolId}/items/create', TreatmentAdminController::class, 'storeItem');
$this->router()->get('/admin/treatments/{protocolId}/edit', TreatmentAdminController::class, 'edit');
$this->router()->post('/admin/treatments/{protocolId}/save', TreatmentAdminController::class, 'save');
$this->router()->post('/admin/treatments/{protocolId}/submit', TreatmentAdminController::class, 'submit');
$this->router()->post('/admin/treatments/{protocolId}/references', TreatmentAdminController::class, 'reference');
$this->router()->get('/admin/treatment-sources/{systemId}', TreatmentAdminController::class, 'sources');
$this->router()->get('/admin/treatment-sources/{systemId}/create', TreatmentAdminController::class, 'createSource');
$this->router()->post('/admin/treatment-sources/{systemId}/create', TreatmentAdminController::class, 'storeSource');
$this->router()->get('/admin/treatment-sources/{systemId}/{sourceId}', TreatmentAdminController::class, 'source');
$this->router()->post('/admin/treatment-sources/{systemId}/{sourceId}/verify', TreatmentAdminController::class, 'verifySource');
$this->router()->post('/admin/treatment-sources/{systemId}/{sourceId}/withdraw', TreatmentAdminController::class, 'withdrawSource');

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
    '/api/substances/search',
    AccountController::class,
    'searchSubstances'
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
    '/verify-email',
    AuthController::class,
    'verifyEmail'
);

$this->router()->get(
    '/verify-email/resend',
    AuthController::class,
    'resendVerification'
);

$this->router()->post(
    '/verify-email/resend',
    AuthController::class,
    'resendVerificationSubmit'
);

$this->router()->get(
    '/forgot-password',
    AuthController::class,
    'forgotPassword'
);

$this->router()->post(
    '/forgot-password',
    AuthController::class,
    'sendPasswordReset'
);

$this->router()->get(
    '/reset-password',
    AuthController::class,
    'resetPassword'
);

$this->router()->post(
    '/reset-password',
    AuthController::class,
    'resetPasswordSubmit'
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

$this->router()->get(
    '/auth/google',
    GoogleAuthController::class,
    'redirect'
);

$this->router()->get(
    '/auth/google/callback',
    GoogleAuthController::class,
    'callback'
);

$this->router()->get(
    '/account/google/link',
    GoogleAuthController::class,
    'link'
);

$this->router()->post(
    '/account/authentication/unlink',
    AccountController::class,
    'unlinkAuthentication'
);

$this->router()->post(
    '/logout',
    AuthController::class,
    'logout'
);

// Account
$this->router()->get('/account/saved-symptom-checks', SavedSymptomCheckController::class, 'index');
$this->router()->post('/account/saved-symptom-checks', SavedSymptomCheckController::class, 'store');
$this->router()->get('/account/saved-symptom-checks/{checkId}', SavedSymptomCheckController::class, 'show');
$this->router()->post('/account/saved-symptom-checks/{checkId}/delete', SavedSymptomCheckController::class, 'delete');

$this->router()->get(
    '/account/health-dashboard',
    PersonalHealthDashboardController::class,
    'index'
);

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
    '/account/health-records/{profileId}/access',
    AccountController::class,
    'healthRecordAccess'
);

$this->router()->post(
    '/account/health-records/{profileId}/access/invite',
    AccountController::class,
    'storeHealthRecordInvitation'
);

$this->router()->get(
    '/shared/health-profile-invitation/{token}',
    AccountController::class,
    'healthProfileInvitation'
);

$this->router()->post(
    '/shared/health-profile-invitation/{token}/accept',
    AccountController::class,
    'acceptHealthProfileInvitation'
);

$this->router()->post(
    '/account/health-records/{profileId}/access/members/{memberUserId}/role',
    AccountController::class,
    'updateHealthRecordMemberRole'
);

$this->router()->post(
    '/account/health-records/{profileId}/access/members/{memberUserId}/revoke',
    AccountController::class,
    'revokeHealthRecordMember'
);

$this->router()->post(
    '/account/health-records/{profileId}/access/members/{memberUserId}/reactivate',
    AccountController::class,
    'reactivateHealthRecordMember'
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

$this->router()->get(
    '/account/health-records/{profileId}/documents',
    AccountController::class,
    'healthDocuments'
);

$this->router()->get(
    '/account/health-records/{profileId}/documents/create',
    AccountController::class,
    'createHealthDocument'
);

$this->router()->post(
    '/account/health-records/{profileId}/documents',
    AccountController::class,
    'storeHealthDocument'
);

$this->router()->get(
    '/account/health-records/{profileId}/documents/{documentId}/edit',
    AccountController::class,
    'editHealthDocument'
);

$this->router()->post(
    '/account/health-records/{profileId}/documents/{documentId}/update',
    AccountController::class,
    'updateHealthDocument'
);

$this->router()->get(
    '/account/health-records/{profileId}/documents/{documentId}/file',
    AccountController::class,
    'healthDocumentFile'
);

$this->router()->post(
    '/account/health-records/{profileId}/documents/{documentId}/delete',
    AccountController::class,
    'deleteHealthDocument'
);


/*
|--------------------------------------------------------------------------
| Health Record - Insurance
|--------------------------------------------------------------------------
*/

$this->router()->get(
    '/account/health-records/{profileId}/insurance',
    HealthInsuranceController::class,
    'index'
);

$this->router()->get(
    '/account/health-records/{profileId}/insurance/create',
    HealthInsuranceController::class,
    'create'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance',
    HealthInsuranceController::class,
    'store'
);

$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}',
    HealthInsuranceController::class,
    'show'
);

$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}/edit',
    HealthInsuranceController::class,
    'edit'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/update',
    HealthInsuranceController::class,
    'update'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/delete',
    HealthInsuranceController::class,
    'delete'
);

$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}/members/create',
    HealthInsuranceController::class,
    'createMember'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/members',
    HealthInsuranceController::class,
    'storeMember'
);

$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}/members/{memberId}/edit',
    HealthInsuranceController::class,
    'editMember'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/members/{memberId}/update',
    HealthInsuranceController::class,
    'updateMember'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/members/{memberId}/delete',
    HealthInsuranceController::class,
    'deleteMember'
);

$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}/contacts/create',
    HealthInsuranceController::class,
    'createContact'
);

$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}/contacts/{contactId}/edit',
    HealthInsuranceController::class,
    'editContact'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/contacts/{contactId}/update',
    HealthInsuranceController::class,
    'updateContact'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/contacts/{contactId}/delete',
    HealthInsuranceController::class,
    'deleteContact'
);

$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/contacts',
    HealthInsuranceController::class,
    'storeContact'
);

/*
|--------------------------------------------------------------------------
| Health Insurance - Linked Documents
|--------------------------------------------------------------------------
*/

// Link document form
$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}/documents/create',
    HealthInsuranceController::class,
    'createDocument'
);

// Store document link
$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/documents/store',
    HealthInsuranceController::class,
    'storeDocument'
);

// Edit linked document
$this->router()->get(
    '/account/health-records/{profileId}/insurance/{policyId}/documents/{linkId}/edit',
    HealthInsuranceController::class,
    'editDocument'
);

// Update linked document
$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/documents/{linkId}/update',
    HealthInsuranceController::class,
    'updateDocument'
);

// Unlink document from insurance policy
$this->router()->post(
    '/account/health-records/{profileId}/insurance/{policyId}/documents/{linkId}/unlink',
    HealthInsuranceController::class,
    'unlinkDocument'
);

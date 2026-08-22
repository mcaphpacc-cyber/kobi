<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\DiseaseController;
use App\Controllers\DiscoveryController;
use App\Controllers\SymptomCheckerController;
use App\Controllers\ComparisonController;
use App\Controllers\AuthController;

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
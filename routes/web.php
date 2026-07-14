<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\DiseaseController;
use App\Controllers\SymptomCheckerController;
use App\Controllers\ComparisonController;

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
$router->get('/', HomeController::class, 'index');

$router->get('/disease', DiseaseController::class, 'index');

$router->get('/disease/{slug}', DiseaseController::class, 'show');

$router->post('/search', SearchController::class, 'search');
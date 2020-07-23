<?php
use App\Controllers\Consultor\ConsultorController;

// use App\Kernel\App;
// $app = new App();
$app->get('/consultor', ConsultorController::class.':loadForm');

$app->post('/buscar', ConsultorController::class.':buscar');

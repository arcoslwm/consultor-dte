<?php
use App\Controllers\Consultor\ConsultorController;

$app->get('/consultor', ConsultorController::class.':loadForm')->setName('consultor');

$app->post('/buscar', ConsultorController::class.':buscar')->setName('busqueda');

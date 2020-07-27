<?php
use App\Controllers\Consultor\ConsultorController;

$app->get('/consultor', ConsultorController::class.':loadForm')->setName('consultor');

$app->post('/buscar', ConsultorController::class.':buscar')->setName('busqueda');

// try {
//     if(gobierno.getAprobacion()<10) {
//         throw new \InvalidGobException("Este gobierno debe abdicar", 1);
//     }
// }
// catch (\InvalidGobException $ige) {
//     if($ige->exceptionCode == 1){
//         pueblo.exigirRenuncia(new Caceroleo(new CronTab("00 21 * * *")));
//     }
// }

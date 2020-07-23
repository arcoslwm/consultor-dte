<?php namespace App\Controllers\Consultor;

use App\Kernel\ControllerAbstract;
use Psr\Http\Message\ServerRequestInterface;
//dev
use Monolog\Logger;
use Slim\Http\Request;

class ConsultorController extends ControllerAbstract
{

    /**
     * [Carga el formulario de consulta ]
     * @return [twig view]
     */
    public function loadForm()
    {
        $log = $this->getService('logger');
        $log->info("ConsultorController loadForm");
        return $this->render('Consultor/form.twig');
    }

    public function buscar() {
        $log = $this->getService('logger');
        $req = $this->getRequest();

        // $req = new Request($method, $uri, $headers, $cookies, $serverParams, $body [$uploadedFiles]);

        // $log->info("ConsultorController buscar: ".$req->getMethod());
        if($req->isPost()!==true){
            $log->warn('ConsultorController buscar metodo no es post');
        }
        /**
         * validar metodo? xhr y post
         * validar inputs
         * consultar WS
         * devolver respuesta
         * (ver doc slim para response y json.)
         * sin doc o  cargar pdf de alguna manera
         */
        // echo print_r($req->getParsedBody());
        // die();

        $log->debug("ConsultorController buscar: parsedBodyparam: ".$req->getParsedBodyParam('txFolio' ,null));
        $log->debug("ConsultorController buscar: parsedBodyparam: ".$req->getParsedBodyParam('txMonto' ,null));

        return $this->render('Consultor/form.twig');

    }
}

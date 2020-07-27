<?php namespace App\Controllers\Consultor;

use App\Kernel\ControllerAbstract;
use App\Domain\Dte;

//dev
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

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

        $log->debug("ConsultorController buscar... ");

        if($req->isXhr()===false){
            $log->warn('request no es Xhr');
            $res = $this->getResponse()->withJson(['message'=>'Error','details'=>['bad request']],400);
            return $res;
        }
        // agregar validacion csrf
        $dte = new Dte(
                $req->getParsedBodyParam('slTipoDoc' ,null),
                $req->getParsedBodyParam('txFolio' ,null),
                $req->getParsedBodyParam('txMonto' ,null),
                $req->getParsedBodyParam('dtFecha' ,null)
        );

        if($dte->isValid()===false){
            $log->warn('Form con errores: '.print_r($dte->getErrors(),true));

            $res = $this->getResponse()->withJson(['message'=>'Error de validación','details'=>$dte->getErrors()],400);
            return $res;
        }
        // $req = new Request($method, $uri, $headers, $cookies, $serverParams, $body [$uploadedFiles]);

        /**
         * consultar WS  dentro de un trycatch
         * devolver respuesta
         * sin doc o  cargar pdf de alguna manera
         */
        $log->debug("ConsultorController buscar return ");
        $res = $this->getResponse()->withJson(['status'=>'ok','data'=>'urlPDF']);

        return $res;
    }
}

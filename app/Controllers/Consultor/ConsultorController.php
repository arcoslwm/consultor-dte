<?php namespace App\Controllers\Consultor;

use App\Kernel\ControllerAbstract;
use App\Domain\Dte;

//dev
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * [ConsultorController description]
 * 
 * @author Luis Arcos <arcos.lwm@gmail.com>
 */
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

    /**
     * valida datos y realiza la búsqueda del documento.
     * @return
     */
    public function buscar() {

        $log = $this->getService('logger');
        $req = $this->getRequest();

        $log->debug("ConsultorController buscar... ");

        if($req->isXhr()===false){

            $log->warn('request no es Xhr');
            $res = $this->getResponse()->withJson(
                ['message'=>'La solicitud no ha podido ser procesada','details'=>['bad request']],
                400
            );
            return $res;
        }

        $dte = new Dte(
                $req->getParsedBodyParam('slTipoDoc' ,null),
                $req->getParsedBodyParam('txFolio' ,null),
                $req->getParsedBodyParam('txMonto' ,null),
                $req->getParsedBodyParam('dtFecha' ,null)
        );

        if($dte->isValid()===false){
            $log->warn('Form con errores: '.print_r($dte->getErrors(),true));

            $res = $this->getResponse()->withJson(
                ['message'=>'Error de validación', 'details'=>$dte->getErrors()],
                400
            );
            return $res;
        }

        /**
         * consultar WS  dentro de un trycatch
         * devolver respuesta
         * sin doc o  cargar pdf de alguna manera
         */

        //dev testing
        if (rand(0, 10)>5) {
            $log->debug("ConsultorController busqueda CON resultado...");
            $res = $this->getResponse()->withJson([
                'message'=>'ok',
                'data'=>[
                    'folio'=>$dte->getFolio(),
                    'fecha'=>$dte->getFecha(),
                    'url'=>'http://biblioteca.clacso.edu.ar/ar/libros/osal/osal4/analisis.pdf'
                ]
            ]);
        }
        else {
            $log->debug("ConsultorController busqueda SIN resultado...");
            $res = $this->getResponse()->withJson([
                'message'=>'No se encuentra documento asociado a los datos'
            ]);
        }

        return $res;
    }
}

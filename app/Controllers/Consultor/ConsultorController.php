<?php namespace App\Controllers\Consultor;

use \SoapClient;
use \Exception;

use Slim\Http\Response;
use Slim\Http\StatusCode;
use Slim\Http\Stream;

use App\Domain\Dte;
use App\Utils\ElapsedTime;
use App\Kernel\ControllerAbstract;

/**
 *
 * @author Luis Arcos <arcos.lwm@gmail.com>
 */
class ConsultorController extends ControllerAbstract
{
    /**
     * Carga formulario de consulta
     * @return twig view
     */
    public function loadForm()
    {
        $log = $this->getService('logger');
        $log->info("ConsultorController loadForm");
        return $this->render(
            'Consultor/form.twig',
            ['tiposDoc'=>[
                    'boleta'=>Dte::TIPO_BOLETA,
                    'exenta'=>Dte::TIPO_BOLETA_EXENTA
                ]
            ]
        );
    }

    /**
     * valida datos y realiza la búsqueda del documento.
     * @return
     */
    public function buscar() {

        $log = $this->getService('logger');
        $req = $this->getRequest();

        $log->debug("ConsultorController buscar...");

        if($req->isXhr()===false){

            $log->warn('request no es Xhr');

            return $this->getResponse()->withJson(
                ['message'=>'La solicitud no ha podido ser procesada','details'=>['bad request']],
                StatusCode::HTTP_BAD_REQUEST
            );
        }

        $dte = new Dte(
                $req->getParsedBodyParam('slTipoDoc' ,null),
                $req->getParsedBodyParam('txFolio' ,null),
                $req->getParsedBodyParam('txMonto' ,null),
                $req->getParsedBodyParam('dtFecha' ,null)
        );

        if($dte->isValidInputs()===false){
            $log->warn('Form con errores: '.print_r($dte->getInputErrors(),true));

            return $this->getResponse()->withJson(
                ['message'=>'Error de validación', 'details'=>$dte->getInputErrors()],
                StatusCode::HTTP_BAD_REQUEST
            );
        }

        try {
            $etWs = new ElapsedTime();
            $sClient = new SoapClient(env('WSDL_URL', null), [ "trace" => true ] );
            // $log->debug("sc getFuntions: ".print_r($sc->__getFunctions(),true));

            $log->debug(" WSParams: ".print_r( $dte->getWSParams() , true ));

            $dte->setDoc( $sClient->get_pdf( $dte->getWSParams() ) );

            $log->debug("WS respuesta en aprox. : ". $etWs->getElapsedTime().' seg');
        }
        catch (Exception $e) {
            $log->error("Exception Soap: ".$e->getMessage());
            $log->error("Exception Soap traza: ".$e->getTraceAsString());
            if($e->getCode()===Dte::ERROR_FORMATO_RESP_WS){
                $log->debug(" WSResponse: ".print_r( $dte->getWsResponse() , true ));
            }

            return $this->getResponse()->withJson(
                ['message'=>'SoapFault', 'details'=>''],
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }


        if($dte->wsRequestIsSuccess()===false){
            $log->debug("ConsultorController respuesta WS: ".$dte->getWsErrorMsg());
            $log->debug("ConsultorController busqueda SIN resultado!");

            return $this->getResponse()->withJson([
                    'message'=>'No se encuentra documento asociado a los datos'
            ]);
        }

        try {
            $et = new ElapsedTime();
            $dte->createPdf();
        }
        catch (Exception $e) {
            $log->error("Exception createPdf: ".$e->getMessage());
            $log->error("Exception createPdf traza: ".$e->getTraceAsString());

            return $this->getResponse()->withJson(
                ['message'=>'createPdf', 'details'=>''],
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $log->debug("archivo: ".$dte->getNombreArchivo().".pdf  creado en: ". $et->getElapsedTime());

        $log->debug("ConsultorController busqueda CON resultado...");
        return $this->getResponse()->withJson([
            'message'=>'ok',
            'data'=>[
                'folio'=>$dte->getFolio(),
                'fecha'=>$dte->getFecha(),
                'url'=> $this->getContainer()
                                ->get('router')
                                ->pathFor(
                                    'descarga',
                                    ['fileName' => $dte->getNombreArchivo()]
                                )
            ]
        ]);
    }

    public function descargar($fileName)
    {
        $log = $this->getService('logger');

        $log->info("ConsultorController:descargar: ".$fileName);

        $path = storage_path() .'/'.$fileName.'.'.Dte::FILE_EXTENSION;
        $fh = fopen($path, "rb");

        if ($fh === false) {
             $log->warning("ConsultorController:descargar NOT FOUND: ".$fileName);

             return $this->getView()->render(
                 $this->getResponse()->withStatus(StatusCode::HTTP_NOT_FOUND),
                 '404.twig',
                 [
                     'message' => 'No se ha encotrado el documento solicitado.',
                     'ruta' => $this->getContainer()->get('router')->pathFor('consultor')
                 ]
             );
        }

        $finfo    = new \finfo(FILEINFO_MIME);
        $stream = new Stream($fh);

        return $this->getResponse()
                            ->withHeader('Content-Disposition', 'attachment; filename='.$fileName.'.'.Dte::FILE_EXTENSION.';')
                            ->withHeader('Content-Type', $finfo->file($path))
                            ->withHeader('Content-Length', (string) filesize($path))
                            ->withBody($stream);
    }
}

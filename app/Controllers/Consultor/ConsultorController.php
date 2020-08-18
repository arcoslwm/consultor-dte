<?php namespace App\Controllers\Consultor;

use App\Kernel\ControllerAbstract;
use App\Domain\Dte;

use App\Utils\ElapsedTime;

//dev
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

use Slim\Http\Stream;

use \SoapClient;
use \SoapFault;

/**
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

        $log->debug("ConsultorController buscar... ");

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

        if($dte->isValid()===false){
            $log->warn('Form con errores: '.print_r($dte->getErrors(),true));

            return $this->getResponse()->withJson(
                ['message'=>'Error de validación', 'details'=>$dte->getErrors()],
                StatusCode::HTTP_BAD_REQUEST
            );
        }

        // TODO:
        /**
         * consultar WS verdadero dentro de trycatch
         * si viene pdf en base 64 convertir.
         * si hay mensaje de error... analizar y devolver respuesta
         */

        try {
            $sc = new SoapClient(env('WSDL_URL', null), [ "trace" => true ] );
            // $log->debug("sc getFuntions: ".print_r($sc->__getFunctions(),true));

            $wsRes = $sc->ResolveIP( [ "ipAddress" => "181.74.136.95", "licenseKey" => "0" ] );

            $log->debug("resultado: ".print_r($wsRes,true));
        }
        catch (SoapFault $e) {
            $log->error("SoapFault: ".$e->getMessage());
            $log->error("SoapFault traza: ".$e->getTraceAsString());

            return $this->getResponse()->withJson(
                ['message'=>'SoapFault', 'details'=>''],
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        //DEV: $pdfContent vendra del WS
        $pathPdfB64 = storage_path() . '/cvPdfB64.txt';
        $fpPdfB64 = fopen($pathPdfB64 , "r");
        $pdfContent = fread($fpPdfB64 , filesize($pathPdfB64));
        fclose ($fpPdfB64);
        //fin $pdfContent

        //medir tiempo
        $et = new ElapsedTime();

        $fNombre = uniqid('dte-');
        $pdfTemp = fopen(storage_path() .'/'.$fNombre.'.'.Dte::FILE_EXTENSION, "w");
        fwrite ($pdfTemp,base64_decode ($pdfContent));
        fclose ($pdfTemp);

        $log->debug("archivo: ".$fNombre.".pdf  creado en: ". $et->getElapsedTime());

        //dev testing
        if (rand(0, 10)>5) {
            $log->debug("ConsultorController busqueda CON resultado...");
            $res = $this->getResponse()->withJson([
                'message'=>'ok',
                'data'=>[
                    'folio'=>$dte->getFolio(),
                    'fecha'=>$dte->getFecha(),
                    'url'=> $this->getContainer()
                                    ->get('router')
                                    ->pathFor(
                                        'descarga',
                                        ['fileName' => $fNombre]
                                    )
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

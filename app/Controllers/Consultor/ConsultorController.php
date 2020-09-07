<?php namespace App\Controllers\Consultor;

use \SoapClient;
use \Exception;

use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Http\StatusCode;
use Slim\Http\Stream;

use App\Domain\Dte;
use App\Utils\ElapsedTime;
use App\Kernel\ControllerAbstract;

use App\Domain\Crypt;

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
        $log->info("ConsultorController:loadForm");
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
     *
     * valida datos, realiza la busqueda del documento, crea el archivo pdf para descargar
     * y retorna los argumentos para su descarga.
     *
     */
    public function buscar() {

        $log = $this->getService('logger');
        $req = $this->getRequest();
        // $log->info("ConsultorController:buscar", $req->getParams());

        if($req->isXhr()===false){

            $log->warn('request no es Xhr');

            return $this->getResponse()->withJson(
                ['message'=>'La solicitud no ha podido ser procesada','details'=>['bad request']],
                StatusCode::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->time->start('recaptcha');
            $verifyResponse = $this->verifyRecaptcha(
                $req->getParsedBodyParam('g-recaptcha-response' ,null)
            );
            $log->info("verifyRecaptcha respuesta en: ". $this->time->end('recaptcha').' seg');
        }
        catch (\Exception $e) {
            $log->info("verifyRecaptcha Exception: ". $this->time->end('recaptcha').' seg');
            $log->error("Exception verifyRecaptcha: ".$e->getMessage());
            $log->error("Exception verifyRecaptcha traza: ".$e->getTraceAsString());

            return $this->getResponse()->withJson(
                ['message'=>'verifyRecaptchaFault', 'details'=>''],
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if( $verifyResponse['success']===false ){
            $log->warn('recaptcha no validado: ', $verifyResponse['error-codes']);

            return $this->getResponse()->withJson([
                    'message'=>'Error de validación',
                    'details'=>['recaptcha'=>'El recaptcha no es válido']
                ],
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
            $log->warn('Form con errores: ',$dte->getInputErrors());

            return $this->getResponse()->withJson(
                ['message'=>'Error de validación', 'details'=>$dte->getInputErrors()],
                StatusCode::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->time->start('soapCall');
            $sClient = new SoapClient(env('WSDL_URL', null), [ "trace" => true ] );
            // $log->debug("sc getFuntions: ".print_r($sc->__getFunctions(),true));

            $log->debug(" WSParams: " , $dte->getWSParams());

            $dte->setDoc( $sClient->get_pdf( $dte->getWSParams() ) );

            $log->info("WS respuesta en: ". $this->time->end('soapCall').' seg');
        }
        catch (Exception $e) {
            $log->info("WS Exception en aprox. : ". $this->time->end('soapCall').' seg');
            $log->error("Exception Soap: ".$e->getMessage());
            $log->error("Exception Soap traza: ".$e->getTraceAsString());
            if($e->getCode()===Dte::ERROR_FORMATO_RESP_WS){
                $log->error(" WSResponse: ".print_r( $dte->getWsResponse() , true ));
            }

            return $this->getResponse()->withJson(
                ['message'=>'SoapFault', 'details'=>''],
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }


        if($dte->wsRequestIsSuccess()===false){
            $log->warn("ConsultorController respuesta WS: ".$dte->getWsErrorMsg());
            if(!empty($dte->getWsResponse())){
                $log->warn(" WSResponse: ".print_r( $dte->getWsResponse() , true ));
            }
            $log->debug("ConsultorController busqueda SIN resultado!");

            return $this->getResponse()->withJson([
                    'message'=>'No se encuentra documento asociado a los datos'
            ]);
        }

        try {
            $this->time->start('pdf');
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
        $log->info("archivo: ".$dte->getNombreArchivo().".pdf  creado en: ". $this->time->end('pdf'));

        $argDescarga = Crypt::AES_Encode($dte->getNombreArchivo());

        $log->debug("argDescarga: ".$argDescarga);
        $log->debug("ConsultorController busqueda CON resultado OK en". $this->time->end());
        return $this->getResponse()->withJson([
            'message'=>'ok',
            'data'=>[
                'folio'=>$dte->getFolio(),
                'fecha'=>$dte->getFecha(),
                'url'=> $this->getContainer()
                                ->get('router')
                                ->pathFor(
                                    'descarga',
                                    ['fileName' => $argDescarga ]
                                )
            ]
        ]);
    }

    /**
     * Genera la descarga del archivo PDF en base al argumento recibido.
     * @param  string $fileName encriptado
     * @return mixed           archivo pdf en caso de exito 404 en caso contrario
     */
    public function descargar($fileName)
    {
        $log = $this->getService('logger');

        $log->info("ConsultorController:descargar: arg: ".$fileName);
        $fileName = Crypt::AES_Decode($fileName);

        if ($fileName === false) {
            $log->warning("ConsultorController: error en descifrar nombre de archivo");

            return $this->getView()->render(
                $this->getResponse()->withStatus(StatusCode::HTTP_NOT_FOUND),
                '404.twig',
                [
                    'message' => 'No se ha encotrado el documento solicitado.',
                    'ruta' => $this->getContainer()->get('router')->pathFor('consultor')
                ]
            );
        }

        $log->info("ConsultorController:descargar: archivo: ".$fileName);
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

        $log->info("ConsultorController:descargar fin en aprox ".$this->time->end()." seg.");
        return $this->getResponse()
                            ->withHeader('Content-Disposition', 'attachment; filename='.$fileName.'.'.Dte::FILE_EXTENSION.';')
                            ->withHeader('Content-Type', $finfo->file($path))
                            ->withHeader('Content-Length', (string) filesize($path))
                            ->withBody($stream);
    }

    /**
     * Realiza la verificacion/validacion  en la api de google del token enviado por google en el form.
     *
     * @param  string $gRecaptchaResponse token
     * @return array
     */
    private function verifyRecaptcha($gRecaptchaResponse){
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $data = [
            'secret' => env('RECAPTCHA_SECRET_KEY', ''),
            'response' => $gRecaptchaResponse
        ];

        $ch = curl_init();
        if ($ch === false) {
            throw new Exception( 'No se ha podido iniciar curl ');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception( 'Fallo curl_exec');
        }
        curl_close($ch);
        $arrResponse = json_decode($response, true);

        return $arrResponse;
    }
}

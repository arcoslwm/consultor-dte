<?php namespace App\Domain;

use \Exception;
use \stdClass;

use App\Utils\Date;

/**
 * Documento tributario electronico
 *
 * recoge datos de formulario de busqueda para un documento tributario electronico (boleta)
 * contiene logica de validacion de estos y  errores.<br>
 * encapsula tambien la logica de consulta al WS para recuperar el PDF asociado.<br>
 * tambien la creacion de un archivo PDF en el servidor.<br>
 *
 *  @author arcoslwm <arcos.lwm@gmail.com>
 */
class Dte
{
    const RUT_FAMAE="000000";

    const TIPO_BOLETA=39;
    const TIPO_BOLETA_EXENTA=41;

    const FILE_EXTENSION = 'pdf';

    const ERROR_VALIDACION_INPUTS=-1;

    const ERROR_FORMATO_RESP_WS = -10;

    const ERROR_PDF_FILE_CREATE = -20;

    private $tipo;
    private $folio;
    private $monto;
    private $fecha;

    /**
     * errores de validacion
     * @var array
     */
    private $inputErrors;

    /**
     * parametros para consumir WS
     * @var array
     */
    private $wsParams;

    private $wsResponse;
    private $wsRequestSuccess=false;
    private $wsErrorMsg="";
    private $wsNombreDoc="";

    /**
     * contenido del pdf decodificado.
     */
    private $pdfDocFile;

    private $nombreArchivo;

    function __construct($tipo, $folio, $monto, $fecha)
    {
        $this->tipo = $tipo;
        $this->folio = $folio;
        $this->monto = $monto;
        $this->fecha = Date::customCreateFromFormat($fecha, 'Y-m-d');
    }

    /**
     * retorna true si todas las propiedades de la boleta son validas
     * @return boolean
     */
    function isValidInputs()
    {
        $valid = true;
        //tipo
        if(!in_array($this->tipo,[self::TIPO_BOLETA,self::TIPO_BOLETA_EXENTA])){

            $this->inputErrors['tipoDoc']='No se reconoce el tipo de documento';
            $valid = false;
        }

        //folio
        if (preg_match('/^[0-9]+$/', $this->folio)!==1)// "/^[1-9][0-9]*$/"
        {
            $this->inputErrors['folio']='El folio no es válido';
            $valid = false;
        }

        //monto
        if (preg_match('/^[0-9]+$/', $this->monto)!==1)// "/^[1-9][0-9]*$/"
        {
            $this->inputErrors['monto']='El monto no es válido';
            $valid = false;
        }

        // fecha
        if($this->fecha===false){
            $this->inputErrors['fecha']='La fecha no es válida';
            $valid = false;
        }

        return $valid;
    }

    function getInputErrors(){
        return $this->inputErrors;
    }

    function getFolio(){
        return $this->folio;
    }

    function getTipo(){
        return $this->tipo;
    }

    /**
     * retorna un string con la fecha en el formato especificado
     *
     * @param  string $format ej: 'd/m/Y'
     * @return string
     */
    function getFecha($format='d/m/Y'){
        return $this->fecha->format($format);
    }

    /**
     * crea y/o retorna un array con la estructura de paramteros para consumir WS soap.
     *
     * @return array parameteros
     */
    function getWSParams()
    {

        if($this->isValidInputs()===false){
            throw new Exception( 'Error de validacion', self::ERROR_VALIDACION_INPUTS );
        }

        if(is_array($this->wsParams)){
            return $this->wsParams;
        }
        //monto es opcional(?)
        //fecha es opcional y no está validada
        $this->wsParams = [
            "rutt" => self::RUT_FAMAE , "folio" => (string)$this->folio ,
            "doc" => $this->tipo , "monto" => $this->monto ,
            "fecha" => $this->getFecha('Y-m-d')
        ];
        return $this->wsParams;
    }

    /**
     * setea el contenido del documento y la info de este desde la respuesta del WS
     * arroja exeception si el formato de respuesta no es reconocido.
     * @param stdClass $wsResponse respuesta WS
     */
    function setDoc($wsResponse){

        if( property_exists($wsResponse, 'get_pdfResult') &&
            property_exists($wsResponse->get_pdfResult, 'string') &&
            is_array($wsResponse->get_pdfResult->string)
        )
        {
            if( !empty($wsResponse->get_pdfResult->string[1]) )
            {//contenido del PDF en base64
                $this->pdfDocFile= base64_decode($wsResponse->get_pdfResult->string[1]);
                if( $this->pdfDocFile!==false ){

                    $this->wsRequestSuccess = true;
                    $this->wsNombreDoc = $wsResponse->get_pdfResult->string[0];
                }
                else{
                    $this->wsResponse = $wsResponse;
                }
            }
            else if ( !empty($wsResponse->get_pdfResult->string[2]) )
            {//string con detalle de error
                $this->wsErrorMsg = $wsResponse->get_pdfResult->string[2];
            }
        }
        else{
            $this->wsResponse = $wsResponse;
            throw new Exception( 'Error en formato respuesta WS', self::ERROR_FORMATO_RESP_WS );
        }
    }

    function wsRequestIsSuccess(){
        return $this->wsRequestSuccess;
    }

    function getWsResponse(){
        return $this->wsResponse;
    }

    function getWsErrorMsg(){
        return $this->wsErrorMsg;
    }

    function getNombreArchivo(){
        return $this->nombreArchivo;
    }

    protected function setNombreArchivo(){

        $this->nombreArchivo = 'dte-'.self::RUT_FAMAE.'-'.(string)$this->tipo.'-'. (string)$this->folio;
    }

    /**
     * crea un documento pdf en el sistema local con el contenido de la propiedad $this->pdfDocFile
     */
    function createPdf()
    {

        $this->setNombreArchivo();
        $pdfTemp = fopen(storage_path() .'/'.$this->nombreArchivo.'.'.self::FILE_EXTENSION, "w");
        if ($pdfTemp === false) {
            throw new Exception( 'No se ha podido crear/abrir el archivo: '.$this->nombreArchivo, self::ERROR_PDF_FILE_CREATE );
        }

        if (fwrite($pdfTemp, $this->pdfDocFile) === false) {
            throw new Exception( 'No se ha podido escribir el archivo: '.$this->nombreArchivo, self::ERROR_HTML_FILE_WRITE );
        }
        if (fclose($pdfTemp) === false) {
            throw new Exception( 'No se ha podido cerrar el archivo: '.$this->nombreArchivo, self::ERROR_HTML_FILE_CLOSE );
        }
    }
}

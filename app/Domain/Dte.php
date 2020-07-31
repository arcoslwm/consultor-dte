<?php namespace App\Domain;

use App\Utils\Date;

/**
 * Representa los datos del formutlario de busqueda para un documento tributario electronico (boleta)
 * contiene logica de validación de estos,y los errores.
 *
 *  @author Luis Arcos <arcos.lwm@gmail.com>
 */
class Dte
{

    const TIPO_BOLETA=1;
    const TIPO_BOLETA_EXENTA=2;

    private $tipo;
    private $folio;
    private $monto;
    private $fecha;

    /**
     * errores de validacion
     * @var array
     */
    private $errors;

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
    function isValid()
    {
        $valid = true;
        //tipo
        if(!in_array($this->tipo,[self::TIPO_BOLETA,self::TIPO_BOLETA_EXENTA])){

            $this->errors['tipoDoc']='No se reconoce el tipo de documento';
            $valid = false;
        }

        //folio
        if (preg_match('/^[0-9]+$/', $this->folio)!==1)// "/^[1-9][0-9]*$/"
        {
            $this->errors['folio']='El folio no es válido';
            $valid = false;
        }

        //monto
        if (preg_match('/^[0-9]+$/', $this->monto)!==1)// "/^[1-9][0-9]*$/"
        {
            $this->errors['monto']='El monto no es válido';
            $valid = false;
        }

        // fecha
        if($this->fecha===false){
            $this->errors['fecha']='La fecha no es válida';
            $valid = false;
        }

        return $valid;
    }

    function getErrors(){
        return $this->errors;
    }

    function getFolio(){
        return $this->folio;
    }

    function getFecha($format='d/m/Y'){
        return $this->fecha->format($format);
    }
}

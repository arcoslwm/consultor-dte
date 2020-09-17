<?php namespace App\Utils;


/**
 * provee una forma de medir tiempos
 *
 * (emula el funcionamiento de console.time() y console.timeEnd() de  JavaScript)
 *
 * @author arcoslwm <arcos.lwm@gmail.com>
 */
class ElapsedTime{

    /**
     *
     * @var array
     */
    private $time;

    /**
     * precision que usa la funcion round para retornar los valores de los tiempos medidos
     *
     * @var int
     */
    private $precision;

    function __construct($label='default' , $precision=5){
        $this->precision=$precision;
        $this->start($label);
    }

    /**
     * guarda con una etiqueta el inicio de una medición de tiempo.
     *
     * @param  string $label
     *
     */
    public function start($label){
        $this->time[$label] = microtime(true);
    }

    /**
     * recibe una etiqueta y retorna el tiempo transcurrido desde que esa etiqueta fue iniciada.
     *
     * @param  string $label
     * @return float       tiempo transcurrido desde el inicio de la etiqueta recibida.
     */
    public function end($label='default'){
        return round( microtime(true) - $this->time[$label], $this->precision) ;
    }
}

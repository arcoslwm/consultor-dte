<?php namespace App\Utils;


/**
 * provee una forma de medir tiempos
 *
 * @author arcoslwm <arcos.lwm@gmail.com>
 */
class ElapsedTime{

    /**
     * @var float
     */
    private $startTime;

    function __construct(){
        $this->startTime = microtime(true);
    }

    /**
     * retorna el tiempo transcurrido desde la creación del objeto.
     * @return float
     */
    public function getElapsedTime(){
        return  microtime(true) - $this->startTime;
    }
}

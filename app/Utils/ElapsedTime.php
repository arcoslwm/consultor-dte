<?php namespace App\Utils;


/**
 * provee una forma de medir tiempos
 *
 * @author arcoslwm <arcos.lwm@gmail.com>
 */
class ElapsedTime{

    // TODO: modificar para que lso tiempos se inicio y fin se guarden en arrays asociativos.
    //recibir la clave del un elemento del array y guardar tempo y para temrinar, recbir la clve neuvamente.

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

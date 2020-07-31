<?php namespace App\Utils;

use \DateTime;

/**
 * Clase con metodos estaticos para creación y validación de fechas.
 *
 * @author Luis Arcos <arcos.lwm@gmail.com>
 */
class Date {

    /**
     * Retorna TRUE si la fecha es valida
     * ( resuelve el problema que causa que la funcion DateTime::createFromFormat
     *  devuelva fechas validas con fechas como :  DateTime::createFromFormat('d/m/Y H:i:s', '32/12/2019 16:38:08');
     * que retorna una fecha: '01/01/2020 16:38:08')
     *
     * problema descrito en:
     * https://www.php.net/manual/es/datetime.createfromformat.php#115270
     *
     * resuelto en:
     * https://www.php.net/manual/en/function.checkdate.php#113205
     *
     *
     * @param string $date
     * @param string $format
     * @return boolean
     */
    public static function customValidate($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && ($d->format($format) == $date);
    }

    /**
     * Retorna un objeto date time o false si no es un string con fecha valida.
     * ( resuelve el problema que causa que la funcion DateTime::createFromFormat
     *  devuelva fechas validas con fechas como :  DateTime::createFromFormat('d/m/Y H:i:s', '32/12/2019 16:38:08');
     * que retorna una fecha: '01/01/2020 16:38:08')
     *
     *
     *
     * problema descrito en:
     * https://www.php.net/manual/es/datetime.createfromformat.php#115270
     *
     * resuelto en:
     * https://www.php.net/manual/en/function.checkdate.php#113205
     *
     *
     * @param string $date
     * @param string $format
     * @return DateTime o False
     */
    public static function customCreateFromFormat($date, $format = 'Y-m-d H:i:s') {

        $d = DateTime::createFromFormat($format, $date);

        if ($d && ($d->format($format) == $date)) {
            return $d;
        }
        return false;
    }
}

<?php namespace App\Domain;

/**
 *
 * @author Luis Arcos
 */
class Crypt {

    const PASSWORD = "Z3nt1.2020";

    static function AES_Encode($string) {

        return urlencode( base64_encode( openssl_encrypt( $string, "aes-256-cbc", self::PASSWORD , true, str_repeat(chr(0), 16) ) ) );
    }

    static function AES_Decode($string) {

        return openssl_decrypt(base64_decode( urldecode( $string) ), "aes-256-cbc", self::PASSWORD , true, str_repeat(chr(0), 16));
    }

}

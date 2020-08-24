<?php namespace App\Domain;


class Crypt {

    const METHOD = "aes-256-cbc";

    static function AES_Encode($string) {

        return urlencode(
            self::base64url_encode(
                openssl_encrypt(
                    $string,
                    self::METHOD ,
                    env('ENCRYPT_PASSWORD', null) ,
                    true,
                    str_repeat(chr(0), 16)
                )
            )
        );
    }

    static function AES_Decode($string) {

        return openssl_decrypt(
                self::base64url_decode( $string ),
                self::METHOD ,
                env('ENCRYPT_PASSWORD', null),
                true,
                str_repeat(chr(0), 16)
        );
    }

    static function base64url_encode( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '=');
    }

    static function base64url_decode( $data ){
        return base64_decode(
            strtr( $data, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $data )) % 4 )
        );
    }
}

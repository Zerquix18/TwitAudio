<?php
/**
 * TwitAudio Crypt for crypting and decrypting
 * It uses Sodium which is an extension of PHP.
 * 
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/
namespace application;

class TACrypt
{
    /**
     * This is the key used to crypt/decrypt the data
     * It's supossed to be in binary
     * But it's in hex for readability.
     * @access private
    **/
    private $key =
    'a2a2c923a00a8f693fe72a429b226133d1605c1ff21abcb526eebdc6f3504a77';

    /**
     * Just translates $this->key to binary.
     */
    public function __construct()
    {
        $this->key = hex2bin($this->key);
    }
    /**
     * Cryps the data
     * @param  string $string duh!
     * @return string The string crypted :)
     * @access public
    **/
    public function crypt($string)
    {
        $nonce = \Sodium\randombytes_buf(
            \Sodium\CRYPTO_SECRETBOX_NONCEBYTES
        );
        // place the nonce BEFORE the crypted data
        $cipher  = $nonce;
        $cipher .= \Sodium\crypto_secretbox(
                        $string,
                        $nonce,
                        $this->key
                    );
        return $cipher;
    }
    /**
     * Crypts data and returns it in base64
     * @param string $string duh! x2
     * @return string
     * @access public
    **/
    public function crypt64($string)
    {
        return base64_encode($this->crypt($string));
    }
    /**
     * Decrypts the data
     * @param  string $string duh! x3
     * @return string
     * @access public
    **/
    public function decrypt($string)
    {
        // take the nonce from the start
        $nonce = mb_substr(
            $string,
            0,
            \Sodium\CRYPTO_SECRETBOX_NONCEBYTES,
            '8bit'
        );
        // take the crypted data without the nonce
        $cipher_text = mb_substr(
            $string,
            \Sodium\CRYPTO_SECRETBOX_NONCEBYTES,
            null,
            '8bit'
        );
        // hakuna matata
        $plain = \Sodium\crypto_secretbox_open(
            $cipher_text,
            $nonce,
            $this->key
        );
        return $plain;
    }
    /**
     * Decrypts the data and returns it in base64
     * @param  string $string (omg rly?)
     * @return string
     * @access public
    **/
    public function decrypt64($string)
    {
        return $this->decrypt(base64_decode($string));
    }
}
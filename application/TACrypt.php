<?php
/**
* TwitAudio Crypt for crypting and decrypting
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright (c) 2015 Luis A. Martínez
*
**/
namespace application;

class TACrypt {
	/**
	* This is the key used to crypt/decrypt the data
	* It's supossed to be in binary
	* But it's in hex...
	* DON'T TOUCH THIS SHIT
	* @access private
	**/
	private $key =
	'a2a2c923a00a8f693fe72a429b226133d1605c1ff21abcb526eebdc6f3504a77';

	public function __construct() {
		$this->key = hex2bin( $this->key );
	}
	/**
	* Cryps data
	* @param $string string (yes omg)
	* @return string
	* @access public
	**/
	public function crypt( $string ) {
		$nonce = \Sodium\randombytes_buf(
			\Sodium\CRYPTO_SECRETBOX_NONCEBYTES
		);
		// place the nonce BEFORE the crypted data
		$cipher = $nonce .
			\Sodium\crypto_secretbox(
				$string,
				$nonce,
				$this->key
			);
		return $cipher;
	}
	/**
	* Crypts data and return it in base64
	* @param $string string (omg rly?)
	* @return string
	* @access public
	**/
	public function crypt64( $string ) {
		return base64_encode( $this->crypt( $string ) );
	}
	/**
	* Decrypts data
	* @param $string string (yes omg)
	* @return string
	* @access public
	**/
	public function decrypt( $string ) {
		// take the nonce from the start
		$nonce = mb_substr(
			$string,
			0,
			\Sodium\CRYPTO_SECRETBOX_NONCEBYTES,
			'8bit'
		);
		// take the crypted data without the nonce
		$ciphertext = mb_substr(
			$string,
			\Sodium\CRYPTO_SECRETBOX_NONCEBYTES,
			null,
			'8bit'
		);
		// hakuna matata
		$plain = \Sodium\crypto_secretbox_open(
			$ciphertext,
			$nonce,
			$this->key
		);
		return $plain;
	}
	/**
	* Decrypts data and return it in base64
	* @param $string string (omg rly?)
	* @return string
	* @access public
	**/
	public function decrypt64( $string ) {
		return $this->decrypt( base64_decode( $string ) );
	}
}
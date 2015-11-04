<?php
/**
* TwitAudio Crypt for crypting and decrypting
* @author Zerquix18
*
**/
class TACrypt {

	private $key = 'a2a2c923a00a8f693fe72a429b226133d1605c1ff21abcb526eebdc6f3504a77';

	public function __construct() {
		$this->key = hex2bin($this->key);
	}
	public function crypt( $string ) {
		$nonce = \Sodium\randombytes_buf(
			\Sodium\CRYPTO_SECRETBOX_NONCEBYTES
		);
		$cipher = $nonce .
			\Sodium\crypto_secretbox(
				$string,
				$nonce,
				$this->key
			);
		return $cipher;
	}
	public function crypt64( $string ) {
		return base64_encode( $this->crypt($string) );
	}
	public function decrypt( $string ) {
		$nonce = mb_substr(
			$string,
			0,
			\Sodium\CRYPTO_SECRETBOX_NONCEBYTES,
			'8bit'
		);
		$ciphertext = mb_substr(
			$string,
			\Sodium\CRYPTO_SECRETBOX_NONCEBYTES,
			null,
			'8bit'
		);
		$plain = \Sodium\crypto_secretbox_open(
			$ciphertext,
			$nonce,
			$this->key
		);
		return $plain;
	}
	public function decrypt64( $string ) {
		return $this->decrypt( base64_decode( $string) );
	}
}
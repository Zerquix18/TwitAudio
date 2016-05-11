<?php
/**
* This class has 2 static methods
* To get and set config in runtime
* The global $_CONFIG is set in index.php
*
**/
class Config {
	/**
	* Returns $key from $_CONFIG
	* @param $key string
	* @return mixed
	**/
	public static function get( $key ) {
		global $_CONFIG;
		if( ! array_key_exists($key, $_CONFIG) ) {
			return null;
		}
		return $_CONFIG[$key];
	}
	/**
	* Sets $key and $value to $_CONFIG
	* @param $key string
	* @param $value mixed
	* @return void
	**/
	public static function set( $key, $value ) {
		global $_CONFIG;
		$_CONFIG[$key] = $value;
	}
}
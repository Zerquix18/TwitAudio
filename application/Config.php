<?php
/**
 * This class has 2 static methods
 * To get and set config in runtime
 * The global $_CONFIG is set in index.php
 *
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/
class Config {
	/**
	 * Returns $key from $_CONFIG
	 * @param  string $key
	 * @return mixed  The key of $_CONFIG or NULL if it does not exist.
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
	 * @param string $key
	 * @param mixed  $value
	**/
	public static function set( $key, $value ) {
		global $_CONFIG;
		$_CONFIG[$key] = $value;
	}
}
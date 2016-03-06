<?php
/**
* HTTP Class
* Handles the HTTP data
* for inputs and outputs
* and more!
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/

namespace application;

class HTTP {

	/** getting parameters **/
	public static function get( $param ) {
		if( ! isset($_GET[ $param ] ) || ! is_string( $_GET[ $param ] ) )
			return FALSE;
		return trim( $_GET[ $param ] );
	}
	public static function post( $param ) {
		if( ! isset($_POST[ $param ] ) || ! is_string( $_POST[ $param ] ) )
			return FALSE;
		return trim( $_POST[ $param ] );
	}
	/** sanitizing **/
	public static function sanitize_pageNumber( $pageNumber ) {
		if( ! is_numeric($pageNumber) )
			return false;
		if( (int) $pageNumber < 0 )
			return false;
		return (int) $pageNumber;
	}
	public static function XSSprotect( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES, 'utf-8');
	}
	public static function sanitize( $str ) {
		if( mb_strlen( $str, 'utf8' ) < 1 )
			return '';
		$str = self::XSSprotect( $str );
		$str = str_replace( array( chr( 10 ), chr( 13 ) ), '' , $str );
		$str = preg_replace(
			'/https?:\/\/[\w\-\.!~#?&=+%;:\*\'"(),\/]+/u',
			'<a href="$0" target="_blank" rel="nofollow">$0</a>',
			$str
		);
    	$str = preg_replace_callback(
    		'~([@])([^\s#@!\"\$\%&\'\(\)\*\+\,\-./\:\;\<\=\>?\[/\/\/\\]\^\`\{\|\}\~]+)~',
    		function($m) {
    			/** @todo remove hashtags from source **/
    			$dir = $m[1] == "#" ? "search/?q=%23" : "audios/";
    			return '<a href="' . url() . $dir . $m[2] . '">' . $m[0] . '</a>';
    		},
       		$str
       	);
		return $str;
	}
	/** JSON exits **/
	public static function Result( array $options ) {
		if( ! array_key_exists('success', $options)
			|| ! is_bool( $options['success']) )
			return false;

		exit( json_encode( $options ) );
	}
	/** misc! **/
	public static function redirect( $url, $status = 302 ) {
		header('Location: ' . $url, true, $status);
		exit;
	}
}
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
	/**
	* Returns the GET parameter $param
	* @return string
	**/
	public static function get( $param ) {
		if( ! isset( $_GET[ $param ] ) || ! is_string( $_GET[ $param ] ) ) {
			return '';
		}
		return trim( $_GET[ $param ] );
	}
	/**
	* Returns the POST parameter $param
	* @return string
	**/
	public static function post( $param ) {
		if( ! isset( $_POST[ $param ] ) || ! is_string( $_POST[ $param ] ) ) {
			return '';
		}
		return trim( $_POST[ $param ] );
	}
	/**
	* Returns the page number validated
	* $page_number may be the page number
	* sent through AJAX
	* @return integer
	**/
	public static function sanitize_page_number( $page_number ) {
		if( ! ctype_digit($page_number) ) {
			return 0;
		}
		if( (int) $page_number <= 0 ) {
			return 0;
		}
		return (int) $page_number;
	}
	/**
	* Protects a string from XSS
	* @return string
	**/
	public static function xss_protect( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES, 'utf-8');
	}
	/**
	* Sanitizes a string
	* and prepares it
	* @return string
	**/
	public static function sanitize( $str ) {
		if( mb_strlen( $str, 'utf8' ) < 1 ) {
			return '';
		}
		$str = self::xss_protect( $str );
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
	/**
	* Exits a JSON string
	* from the given array
	* @return void
	**/
	public static function result( array $options ) {
		if(    ! array_key_exists('success', $options)
			|| ! is_bool($options['success'])
			) {
			return;
		}
		exit( json_encode( $options ) );
	}
	/**
	* Performs an HTTP redirect
	* @return void
	**/
	public static function redirect( $url, $status = 302 ) {
		header('Location: ' . $url, true, $status);
		exit;
	}
}
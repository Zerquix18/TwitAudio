<?php
/**
 * HTTP Class
 * Includes functions related to requests
 * for inputs and outputs
 * and more!
 *
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/

namespace application;

class HTTP {
	/**
	* Returns the GET parameter $param
	* Something like $_GET[$param] but it will check that it exists
	* and that it is a string, not an array. So we can avoid
	* param[]=something.
	* 
	* @return string The GET param or empty if it does not exist 
	*                                       or it is not valid.
	**/
	public static function get( $param ) {
		if( ! isset( $_GET[ $param ] ) || ! is_string( $_GET[ $param ] ) ) {
			return '';
		}
		return trim( $_GET[ $param ] );
	}
	/**
	* Returns the POST parameter $param
	* Something like $_POST[$param] but it will check that it exists
	* and that it is a string, not an array. So we can avoid
	* param[]=something.
	* 
	* @return string The POST param or empty if it does not exist 
	*                                       or it is not valid.
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
	 * sent through AJAX.
	 * 
	 * @return integer The page number or 0 if it is not valid.
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
	 * Sanitizes a string of HTML tags.
	 * @return string
	**/
	public static function xss_protect( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES, 'utf-8');
	}
	/**
	 * Sanitizes a string for the audios description.
	 * Protects it from XSS, adds links for http|https
	 * And add links for @mentions.
	 * @return string
	**/
	public static function sanitize( $str ) {
		if( mb_strlen($str, 'utf8') < 1 ) {
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
	 * from the given array $options
	 * It is used to return things in AJAX requests so it
	 * MUST contain the key "success".
	 * 
	 * @param array $options The list of keys to be exit as JSON
	**/
	public static function result( array $options ) {
		if(    ! array_key_exists('success', $options)
			|| ! is_bool($options['success'])
			) {
			trigger_error('HTTP::result must have the key success');
			exit(); // exit but with nothing
		}
		$result = json_encode(
			$options,
			JSON_UNESCAPED_UNICODE |
			JSON_UNESCAPED_SLASHES |
			JSON_UNESCAPED_UNICODE
		);
		exit($result);
	}
	/**
	 * Performs an HTTP redirect
	 *
	 * @param string  $url The URL to redirect to
	 * @param integer $status The HTTP redirect status.
	 *                        If the status is 302 then it is for a temporary
	 *                        redirect. If it is 301 then it will be for a
	 *                        permanent redirect.
	**/
	public static function redirect( $url, $status = 302 ) {
		header( sprintf('Location: %s', $url), true, $status);
		exit;
	}
}
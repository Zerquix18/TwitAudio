<?php
/**
*
* Exception for the Mobile API / AJAX requests
* It will return the error for Mobile or AJAX requests
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/

namespace application;

use \application\HTTP;

class MobileAJAXException extends \Exception {

	public function __construct($message, array $options = array() ) {
		/**
		* 'show_in_web' will determine if we shall return
		* the error message of the exception OR we will just
		* return 'There was a problem while processing your
		* request'. 
		* 'error_code' will determine the error code in the mobile
		* side.
		**/
		$default_options = array(
				'show_in_web'	=> false,
				'error_code'	=> 0
			);
		$this->options = array_merge($default_options, $options);
		parent::__construct($message, $this->options['error_code'], null);
	}

	/**
	*
	* Will exit the result, send the param via
	* In the MobileAJAXController just send $this->via
	* @return void
	**/
	public function print_result( $via ) {
		if( 'mob' == $via )
			HTTP::result( array(
					'success'  => false,
					'response' => $this->message,
					'error_code' => $this->code
				)
			);
		elseif( 'ajax' == $via && $this->options['show_in_web'] )
			HTTP::result( array(
					'success'  => false,
					'response' => $this->message,
				)
			);
		elseif( 'ajax' == $via && $GLOBALS['_CONFIG']['display_errors'])
			HTTP::result( array(
					'success'  => false,
					'response' => $this->message,
				)
			);
		else
			HTTP::result( array(
					'success'  => false,
					'response' => //↓
					__('There was a problem while processing your request'),
				)
			);
			
	}
}
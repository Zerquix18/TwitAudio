<?php
/**
*
* Exception for the Mobile API / AJAX requests
* It will return the error for Mobile or AJAX requests
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/

namespace application\exceptions;

class MobileAJAXException extends \Exception {
	/**
	 * Saves the options
	 * @var array
	 */
	public $options;

	/**
	 * @param string $message The exception message
	 * @param array $options  Options for behavior
	 */
	public function __construct( $message, array $options = array() ) {
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
}
<?php
/**
*
* The login exception
* Once caught, it must redirect to the home URL
* and return the error.
*
**/
namespace application\exceptions;

class LoginException extends \Exception {

	public $redirect_to;

	public function __construct( $message, $redirect_to = '' ) {
		global $_CONFIG;
		if( $_CONFIG['display_errors'] ) {
			// are we in production? no.
			$_SESSION['login_error'] = $message;
		} else {
			$_SESSION['login_error'] =
			'There was a problem while login you in. Please, try again.';
		}
		$this->redirect_to = $redirect_to ?: url();
	}
}
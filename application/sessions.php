<?php
/**
* Handle session related data
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
**/

session_name('ta_session');

if( ! is_mobile() ) {
	if( ! isset($_COOKIE['ta_session']) ) {
		session_id( generate_id('session') );
	}

	if(		! isset($_COOKIE['ta_session'])
		||  preg_match(
				"/^(ta-)[\w]{29}+$/",
				$_COOKIE['ta_session']
			)
		) {
		// if cookie isn't valid,
		// PHP will throw a unavoidable warning
		// when calling session_start();
		session_start();
	}
}

// just a helper:

function _is_logged() {
	global $db;
	if( ! isset($_COOKIE['ta_session']) ) {
		return 0;
	}
	$session = $db->query(
		"SELECT user_id FROM sessions
		 WHERE sess_id = ? AND is_mobile = '0'",
		session_id()
	);
	return $session->nums > 0 ? (int) $session->user_id : 0;
}

$just_1_query = _is_logged();

function is_logged() {
	if( defined('IS_LOGGED_MOBILE') ) {
		return constant('IS_LOGGED_MOBILE');
	}
	return $GLOBALS['just_1_query']; // such a pro, thats me
}

/**
* Checks authorization header in the mobile side
* @return void
**/

function check_authorization() {
	global $_USER, $db;
	$TACrypt = new \application\TACrypt();
	$headers = apache_request_headers();
	if( empty($headers['Authorization']) ) {
		\application\HTTP::result( array(
				'success'  => false,
				'response' => 'Authorization required',
			)
		);
	}
	$authorization = $TACrypt->decrypt64($headers['Authorization']);
	if( ! $authorization ) {
		\application\HTTP::result( array(
				'success'  => false,
				'response' => 'Invalid authorization',
			)
		);
	}
	$session = $db->query(
			'SELECT user_id FROM sessions
			 WHERE sess_id = ? AND is_mobile = \'1\'',
			$authorization
	);
	if( $session->nums === 0 ) {
		\application\HTTP::result( array(
				'success'  => false,
				'response' => 'Invalid authorization'
			)
		);
	}
	// for global use
	$_USER = $db->query(
		'SELECT * FROM users WHERE id = ?',
		$session->user_id
	);
	define('IS_LOGGED_MOBILE', true);
	// now the user is logged
	session_cache_limiter('public');
	session_cache_expire(30);
	session_id($authorization);
	session_start();
}

function session_logout() {
	global $db;
	$db->query(
		'DELETE FROM sessions WHERE sess_id = ?',
		session_id()
	);
	session_destroy();
	setcookie('ta_session', '', time() - 3600);
}
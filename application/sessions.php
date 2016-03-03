<?php
/**
* Handle session related data
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
**/

session_name('ta_session');

if( ! isset($_COOKIE['ta_session']) )
	session_id( generate_id_for('session') );

if( ! isset($_COOKIE['ta_session']) ||  
		preg_match(
			"/^(ta-)[\w]{29}+$/",
			$_COOKIE['ta_session']
		)
	) {
	// if cookie isn't valid,
	// PHP will throw a unavoidable warning
	// when calling session_start(); {
	session_start();
}

// just a helper:

function _is_logged() {
	global $db;
	if( ! isset($_COOKIE['ta_session']) )
		return 0;
	$session = $db->query(
		"SELECT user_id FROM sessions
		 WHERE sess_id = ? AND is_mobile = '0'",
		session_id()
	);
	return $session->nums > 0 ?(int) $session->user_id : 0;
}

$just_1_query = _is_logged();

function is_logged() {
	return $GLOBALS['just_1_query']; // such a pro, thats me
}

/** mobile **/

function checkAuthorization() {
	global $_USER, $db;
	$TACrypt = new \application\TACrypt();
	$headers = apache_request_headers();
	if( empty($headers['Authorization']) )
		return \application\HTTP::MobileResult(
				__('Authorization required'),
				false //success
			);
	$authorization = @$TACrypt->decrypt64( $headers['authorization'] );

	if( ! $authorization )
		return \application\HTTP::MobileResult(
				__('Invalid authorization'),
				false //success
			);

	$session = $db->query(
			'SELECT user_id FROM sessions
			 WHERE sess_id = ? AND is_mobile = \'1\'',
			$authorization
	);
	if( $session->nums === 0 )
		return \application\HTTP::MobileResult(
			__('Invalid authorization'),
			false //success
		);
	// for global use
	$_USER = $db->query(
		'SELECT * FROM users WHERE id = ?',
		$session->user_id
	); // now the user is logged
	session_id( $authorization );
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
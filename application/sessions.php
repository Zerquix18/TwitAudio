<?php
/**
 * Handle session related data
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/

/**
 * Returns if the user is logged or not.
 * The constant IS_LOGGED_MOBILE is set by
 * check_authorization()
 * The constant IS_LOGGED is set by
 * session_init()
 *
 * @see  _is_logged for the global+
 * @see  check_authorization for the constant
 * @return integer
 */
function is_logged() {
	if( defined('IS_LOGGED_MOBILE') ) {
		return constant('IS_LOGGED_MOBILE');
	}
	return constant('IS_LOGGED');
}

/**
 * Checks the authorization header for the mobile side
**/

function check_authorization() {
	global $_USER;
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
	$session = db()->query(
			"SELECT user_id FROM sessions
			 WHERE id = ? AND is_mobile = '1'",
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
	$_USER = db()->query(
		'SELECT * FROM users WHERE id = ?',
		$session->user_id
	);
	define('IS_LOGGED_MOBILE', (int) $_USER->id);
	// now the user is logged
	session_cache_limiter('public');
	session_cache_expire(30);
	session_id($authorization);
	session_start();
}
/**
 * Logouts the user
 */
function session_logout() {
	$delete = db()->query(
				'DELETE FROM sessions WHERE id = ?',
				session_id()
			);
	if( ! $delete ) {
		throw new \DBException('DELETE session error');
	}
	session_destroy();
	setcookie('ta_session', '', time() - 3600);
}
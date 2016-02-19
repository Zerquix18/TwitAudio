<?php
/**
* This is the loader of the mobile API
* Loads fundamental functions
* And that... you know.
* @author Zerquix18 <zerquix18@hotmail.com>
**/
//We expect to only return JSON
header('Content-type: application/json; charset=utf-8');
// require the whole system
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
// require th class for crypting/decrypting
require $_SERVER['DOCUMENT_ROOT'] . '/mob/class.tacrypt.php';
$TACrypt = new TACrypt;
/**
* Will return a JSON string when an error ocurred
* @param mixed $response (probably a string with the error)
* @param int $error_code
**/
function result_error($response, $error_code = null ) {
	$result = array(
		'success' 	=> false,
		'error' 		=> $response,
		'error_code' 	=> $error_code,
	);
	exit(json_encode($result));
}
/**
* Will return a JSON string when everything was okay
* @param mixed $response
* @param array $extra (extra things to add)
**/
function result_success( $response = null, $extra = null ) {
	$result = array(
		'success'   => true,
		'response' => $response
	);
	if( null !== $extra )
		$result += $extra;
	exit(json_encode($result));
}
/**
* Checks that the header Authorization
* contains the session ID of a mobile session
* and it's valid and crypted
* @return sess_id (used to destroy the sess)
**/
function checkAuthorization() {
	global $TACrypt, $db, $_USER;
	$h = apache_request_headers();
	$authorization = trim($h['Authorization']);
	if( ! isset($h['Authorization']) || empty($authorization) )
		result_error(
			__('Authorization required'),
			1
		);
	// decrypts authorization
	$authorization = @$TACrypt->decrypt64( $authorization );
	if( ! $authorization )
		result_error(
			__('Invalid authorization'),
			2
		);
	// checks in the db for the decrypted result
	$session = $db->query(
		'SELECT user_id FROM sessions
		WHERE sess_id = ? AND is_mobile = \'1\'',
		$authorization
	);
	if( $session->nums === 0 )
		result_error(
				__("There was an error"),
				3
			);
	// for global use
	$_USER = $db->query(
		'SELECT * FROM users WHERE id = ?',
		$session->user_id
	); // now the user is logged
	$GLOBALS['sess_id'] = $authorization;
	session_id( $authorization );
	session_start();
}
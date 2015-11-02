<?php
error_reporting(E_ALL);
require '../load.php';
require 'class.tacrypt.php';
$ta = new TACrypt;
function result_error($response, $error_code = null ) {
	$r = array(
		'success' => false,
		'error' => $response,
		'error_code' => $error_code,
	);
	exit(json_encode($r));
}
function result_success( $response = null, $extra = null ) {
	$r = array(
		'success' => true,
		'response' => $response
	);
	if( null !== $extra )
		$r += $extra;
	exit(json_encode($r));
}
function checkAuthorization() {
	global $ta, $db;
	$h = apache_request_headers();
	if( ! isset($h['Authorization']) )
		result_error(
			__('Authorization required'),
			1
		);
	// decrypts authorization
	$authorization = @$ta->decrypt64( $h['Authorization'] );
	if( empty( trim($authorization) ) )
		result_error(
			__('Invalid authorization'),
			2
		);
	// checks in the db for the decrypted result
	$x = $db->query(
		'SELECT user_id FROM sessions
		WHERE sess_key = ?',
		$authorization
	);
	if( $x->nums === 0 )
		result_error(
				__('Authorization does not exist in database'),
				3
			);
	// it exists in the database. it's done.
	return $x->user_id;
}
function generate_sess_key() {
	global $db;
	while(
		( $x = $db->query(
				"SELECT * FROM sessions WHERE sess_key = ?",
				$key = md5(uniqid().rand(1,100))
			)
		) && $x->nums > 0
	);
	return $key;
}
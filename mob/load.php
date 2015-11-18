<?php
error_reporting(E_ALL);
header('Content-type: application/json; charset=utf-8');
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mob/class.tacrypt.php';
$ta = new TACrypt;
function result_error($response, $error_code = null ) {
	$r = array(
		'success' 	=> false,
		'error' 		=> $response,
		'error_code' 	=> $error_code,
	);
	if( isset($GLOBALS['sess_key']) )
		$r['sess_key'] = $GLOBALS['sess_key'];
	exit(json_encode($r));
}
function result_success( $response = null, $extra = null ) {
	$r = array(
		'success'   => true,
		'response' => $response
	);
	if( null !== $extra )
		$r += $extra;
	if( isset($GLOBALS['sess_key']) )
		$r['sess_key'] = $GLOBALS['sess_key'];
	exit(json_encode($r));
}
function checkAuthorization() {
	global $ta, $db, $_USER;
	$h = apache_request_headers();
	if( ! isset($h['Authorization']) || empty(trim($h['Authorization'])) )
		result_error(
			__('Authorization required'),
			1
		);
	// decrypts authorization
	$authorization = @$ta->decrypt64( $h['Authorization'] );
	if( ! $authorization )
		result_error(
			__('Invalid authorization'),
			2
		);
	// checks in the db for the decrypted result
	$x = $db->query(
		'SELECT user_id,sess_id FROM sessions
		WHERE sess_key = ?',
		$authorization
	);
	if( $x->nums === 0 )
		result_error(
				__('Authorization does not exist in database'),
				3
			);
	// it exists ... replace it
	$db->update('sessions', array(
			'sess_key' => $sk = generate_sess_key()
		)
	)->where('sess_id', $x->sess_id)->_();
	// for global use
	$_USER = $db->query(
		'SELECT * FROM users WHERE id = ?',
		$x->user_id
	);
	$GLOBALS['sess_key'] = $sk;
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
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

('POST' !== getenv('REQUEST_METHOD') ) and exit();

if( ! is_logged() )
	_result( __("Authentication required."), false);

if( ! validate_args( $_POST['start'], $_POST['end'], $_POST['id'] ) )
	_result( __('Request malformed.'), false );

if( ! array_key_exists($_POST['id'], $_SESSION) )
	result( __('Request malformed.'), false );

if( is_numeric($_POST['start']) ) {
	$start = (int) $_POST['start'];
	if( $start < 0 || $start > 120 )
		_result( __('Request malformed.'), false );
}else{
	$start = $_POST['start'];
	if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $start) )
		_result( __('Request malformed.'), false );
	$lel = explode(":", $start);
	if( (int) $lel[0] > 2 || (int) $lel[1] > 60 )
		_result( __('Request malformed.'), false );
	if( (int) $lel[0] == 2 && (int) $lel[1] > 0 )
		_result( __('Request malformed'), false );
	$start = ( (int) $lel[0] * 60 ) + (int) $lel[1]; // in seconds
}

if( is_numeric($_POST['start']) ) {
	$start = (int) $_POST['start'];
	if( $start < 0 || $start > 120 )
		_result( __('Request malformed.'), false );
}else{
	$end = $_POST['end'];
	if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $end) )
		_result( __('Request malformed.'), false );
	$lel = explode(":", $end);
	if( (int) $lel[0] > 2 || (int) $lel[1] > 60 )
		_result( __('Request malformed.'), false );
	if( (int) $lel[0] == 2 && (int) $lel[1] > 0 )
		_result( __('Request malformed'), false );
	$end = ( (int) $lel[0] * 60 ) + (int) $lel[1]; // in seconds
}
if( $start >= $end )
	result( __('Request malformed.') );

$id = $_POST['id'];
$a = new Audio($_SESSION[$id]['tmp_url'], true);
$n = $a->cut( $start, $end );
$_SESSION[$id]['tmp_url'] = $n;
$_SESSION[$id]['duration'] = floor($a->info['playtime_seconds']);
_result( true, true,
	array(
		'id' => $id,
		'tmp_url' =>
		url() . INC . TMP . last( explode('/', $a->audio) ) 
		)
	);
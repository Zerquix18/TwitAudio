<?php
/**
* AJAX cut file
* This cuts the audio sent
* This file should be only be requested in an AJAX request
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/

require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/assets/class.audio.php';

if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;

if( ! is_logged() )
	_result( __("Authentication required."), false);

/**
* @var string ['start'] (must be a number or nn:nn)
* @var string ['end'] (must be a number or nn:nn)
* @var string ['id'] the temporary id of the post
* saved in $_SESSION	
**/
if( ! validate_args( $_POST['start'], $_POST['end'], $_POST['id'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( ! array_key_exists($_POST['id'], $_SESSION) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( is_numeric($_POST['start']) ) {
	$start = (int) $_POST['start'];
}else{ // if not a number, translate it to a number
	$start = $_POST['start'];
	if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $start) )
		_result(
			__('There was an error while processing your request.'),
			false
		);
	$lel = explode(":", $start);
	$start = ( (int) $lel[0] * 60 ) + (int) $lel[1]; // in seconds
}

if( is_numeric($_POST['end']) ) {
	$end = (int) $_POST['end'];
}else{
	$end = $_POST['end'];
	if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $end) )
		_result(
			__('There was an error while processing your request.'),
			false
		);
	$lel = explode(":", $end);
	$end = ( (int) $lel[0] * 60 ) + (int) $lel[1]; // in seconds
}
$difference = $end-$start;

if( ($start >= $end) ||
	$difference > get_user_limit('audio_duration') ||
	$difference < 1 )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$id = $_POST['id'];
$audio = new Audio($_SESSION[$id]['tmp_url'], array(
		'validate'	=> 	false,
		'decrease_bitrate' =>   false,
		'max_duration'	 => 	get_user_limit('audio_duration'),
	)
);
$new_audio = $audio->cut( $start, $end );

if( ! $new_audio )
	_result( $audio->error, false );

$_SESSION[$id]['tmp_url'] = $new_audio;
$_SESSION[$id]['duration'] = floor($audio->info['playtime_seconds']);
$_SESSION[$id]['effects'] = apply_audio_effects(
		$audio->audio,
		get_available_effects()
	);
_result( true, true,
	array(
		'id' => $id,
		'tmp_url' =>
		url() . 'assets/tmp/' . last( explode('/', $audio->audio) ) 
		)
	);
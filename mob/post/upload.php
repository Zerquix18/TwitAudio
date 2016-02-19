<?php
/**
* Mobile API upload file
* This file is to upload the audios
* And should only be requested
* through the API.
* @author Zerquix18
* @copyright (c) 2016 Luis A. Martínez
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/assets/class.audio.php';
checkAuthorization();

if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;

 // will be changed later when we support files
$is_voice = true;

if( empty($_FILES['up_file']['name'])
	|| is_array($_FILES['up_file']['name'] )
	|| (
		isset($_FILES['up_file']["error"] )
		&& $_FILES['up_file']["error"] != 0
		)
	)
	result_error( __('There was an error while processing audio') );
$file_size = ( ( $_FILES['up_file']['size'] / 1024) / 1024);
if( $file_size <= 0 || $file_size > 20 )
	result_error(
		__("The file size is invalid. The maximum size is 20mb"),
		null
	);
move_uploaded_file(
		$_FILES['up_file']['tmp_name'],
		$file = $_SERVER['DOCUMENT_ROOT'] .
			'/assets/tmp/' . uniqid() . '.mp3'
	) or result_error(
		__('There was an error while processing audio')
	);
// $file is a temporary file... just to validate 
$audio = new Audio( $file, array(
		'validate'	=>	true,
		'max_duration'  =>	get_user_limit('audio_duration'),
		'is_voice'	=>	$is_voice,
		'decrease_bitrate' =>   ! is_paid_user(),
	)
);
if( $audio->error )
	result_error( $audio->error, $audio->error_code );
// ------------- audio processed ---------
$id = uniqid();

$_SESSION[$id] = array(
		'tmp_url'  => $audio->audio,
		'is_voice' => $is_voice,
		'duration' => floor( $audio->info['playtime_seconds'])
	);
$_SESSION[$id]['effects'] = apply_audio_effects(
		$audio->audio,
		get_available_effects()
	);
result_success( null, array(
		'id'		=>	$id,
		'tmp_url'	=>	$audio->audio
	)
);
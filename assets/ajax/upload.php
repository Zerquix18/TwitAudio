<?php
/**
* AJAX upload file
* This uploads an audio and stores it in the tmp/ dir
* The result will be an id which is stores in $_SESSION
* and it's an array with all the info of the temporary audio
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/assets/class.audio.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit;

if( !is_logged() )
	_result( __("Authentication required."), false);
/**
* @var $_POST['is_voice'] string 'true' or 'false'
**/
if( ! array_key_exists('is_voice', $_POST)
	|| ! is_string($_POST['is_voice'])
	|| ! in_array($_POST['is_voice'], array('true', 'false'), true )
	)
	_result(
		__('There was an error while processing your request.'),
		false
	);

$is_voice = 'true' === $_POST['is_voice'];

if( $is_voice ): // validation if it's voice
	$_POST['bin'] = substr(
			$_POST['bin'],
			strpos($_POST['bin'], ",") + 1
		);
	// tries to validate if it's a valid b64
	if( !( $bin = base64_decode($_POST['bin'], true ) )
		|| base64_encode($bin) !== $_POST['bin'] )
		_result(__('There was an error while processing the file...'), false );
	// save the result in a file
	file_put_contents(
		$file = $_SERVER['DOCUMENT_ROOT'] .
			'/assets/tmp/' . uniqid() . '.mp3',
		$bin
	);
else: // validation for a normal file, which is not voice
	if( empty($_FILES['up_file'])
		|| is_array($_FILES['up_file']['name'] )
		|| ( isset($_FILES['up_file']["error"] ) &&
			$_FILES['up_file']["error"] != 0
			)
		)
		_result( __('There was an error while processing the file...'), false );
	$format = last( explode('.', $_FILES['up_file']['name']) );
	// $format is just for a quick validation
	// the real way to find the format
	// is by reading the content inside
	if( ! in_array(
		strtolower($format),
		array("mp3", "m4a", "aac", "ogg", "wav")
		) )
		_result( __("The format of the uploaded audio is not allowed"), false );
	// file in megabytes
	//todo: restrict this for the premium version
	$fs = ( ( $_FILES['up_file']['size'] / 1024) / 1024);
	if( $fs <= 0 || $fs > 50 )
		 _result( __("The file size is invalid. The maximum size is 50mb"), false );
	move_uploaded_file(
		$_FILES['up_file']['tmp_name'],
		$file = $_SERVER['DOCUMENT_ROOT'] .
			'/assets/tmp/' . uniqid() . '.' . $format
	) or _result( __("There was an error while processing the file..."), false);
endif;
// now $file needs to be validated
$a = new Audio($file);
// 3 is the error when the file exceeds the 2m limit
if( $a->error && $a->error_code != 3 )
	_result($a->error, false);

$id = uniqid();

// saves some info
$_SESSION[$id] = array(
		'tmp_url' => $a->audio,
		'is_voice' => $is_voice,
		'duration' => floor( $a->info['playtime_seconds'])
	);
// if needs to cut
if( $a->error && $a->error_code == 3 )
	_result(
		$a->error,
		false,
		array( // tmp_url for preview
			'tmp_url' => url() . 'assets/tmp/'.
				last( explode('/', $a->audio) ),
			'id' => $id
			)
		);
// if not, then return success and the audio to preview
_result( true, true,
	array(
		'tmp_url' => url() . 'assets/tmp/' .
			last( explode('/', $a->audio) ),
		'id' => $id
		)
	);
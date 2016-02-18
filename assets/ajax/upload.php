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
* @var $_POST['is_voice'] string '1' or '0'
**/
if( ! validate_args( $_POST['is_voice'] )
	|| ! in_array($_POST['is_voice'], array('1', '0') )
	)
	_result(
		__('There was an error while processing your request.'),
		false
	);

$is_voice = (bool) $_POST['is_voice'];

if( $is_voice ):
	$_POST['bin'] = substr(
			$_POST['bin'],
			strpos($_POST['bin'], ",") + 1
		);
	// tries to validate if it's a valid b64
	if( !( $bin = base64_decode($_POST['bin'], true ) )
		|| base64_encode($bin) !== $_POST['bin'] )
		_result(__('There was an error while processing the file...'), false );

	file_put_contents(
		$file = $_SERVER['DOCUMENT_ROOT'] .
			'/assets/tmp/' . uniqid() . '.mp3',
		$bin
	);
else:
	if( empty($_FILES['up_file'])
		|| is_array($_FILES['up_file']['name'] )
		|| ( isset($_FILES['up_file']["error"] ) &&
			$_FILES['up_file']["error"] != 0
			)
		)
		_result(
			__('There was an error while processing the file...'),
			false
		);
	$format = last( explode('.', $_FILES['up_file']['name']) );
	// $format is just for a quick validation
	// the real way to find the format
	// is by reading the content inside
	if( ! in_array(
		strtolower($format),
		array("mp3", "m4a", "aac", "ogg", "wav")
		) )
		_result(__("The format of the uploaded audio is not allowed"), false );

	$file_size = ( ( $_FILES['up_file']['size'] / 1024) / 1024);
	$file_limit = get_user_limit('file_upload');
	if( $file_size > $file_limit )
		 _result( __("The file size is greater than your current limit's, $file_limit mb"), false );
	move_uploaded_file(
		$_FILES['up_file']['tmp_name'],
		$file = $_SERVER['DOCUMENT_ROOT'] .
			'/assets/tmp/' . uniqid() . '.' . $format
	) or _result( __("There was an error while processing the file..."), false);
endif;
// now $file needs to be validated
$audio = new Audio( $file, array(
		'validate'	=>	true,
		'max_duration'  =>	get_user_limit('audio_duration'),
		'is_voice'	=>	$is_voice,
		'decrease_bitrate' =>   ! is_paid_user(),
	)
);
// 3 is the error when the file exceeds the user limit
if( $audio->error && $audio->error_code != 3 )
	_result($audio->error, false);

$id = uniqid();

// saves some info
$_SESSION[$id] = array(
		'tmp_url' => $audio->audio,
		'is_voice' => $is_voice,
		'duration' => floor( $audio->info['playtime_seconds'])
	);
// if needs to cut
if( $audio->error && $audio->error_code == 3 )
	_result(
		$audio->error,
		false,
		array( // tmp_url for preview
			'tmp_url' => url() . 'assets/tmp/'.
				last( explode('/', $audio->audio) ),
			'id' => $id
			)
		);
// if not, then apply effects
$_SESSION[$id]['effects'] = apply_audio_effects(
		$audio->audio,
		get_available_effects()
	);
_result( true, true,
	array(
		'tmp_url' => url() . 'assets/tmp/' .
			last( explode('/', $audio->audio) ),
		'id' => $id
		)
	);
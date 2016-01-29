<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
require PATH . INC . 'class.audio.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit;

if( !is_logged() ) _result( __("Authentication required."), false);

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
	$_POST['bin'] = substr($_POST['bin'], strpos($_POST['bin'], ",") + 1);
	if( !( $bin = base64_decode($_POST['bin'], true ) )
	|| base64_encode($bin) !== $_POST['bin'] )
		_result( __("Request malformed."), false);
	// save the result in a file
	file_put_contents( $file = PATH . INC . TMP . uniqid() . '.mp3', $bin);
else: // validation for a normal file, which is not voice
	if( empty($_FILES['up_file'])
		|| is_array($_FILES['up_file']['name'] )
		|| ( isset($_FILES['up_file']["error"] ) &&
			$_FILES['up_file']["error"] != 0
			)
		)
		_result( __('There was an error while processing the file...'), false );
	$format = last( explode('.', $_FILES['up_file']['name']) );
	if( ! in_array(
		strtolower($format),
		array("mp3", "m4a", "aac", "ogg", "wav")
		) )
		_result( __("The format of the uploaded audio is not allowed"), false );
	$fs = ( ( $_FILES['up_file']['size'] / 1024) / 1024);
	if( $fs <= 0 || $fs > 50 )
		 _result( __("The file size is invalid. The maximum size is 50mb"), false );
	move_uploaded_file(
		$_FILES['up_file']['tmp_name'],
		$file = PATH . INC . TMP . uniqid() . '.' . $format
	) or _result( __("There was an error while processing the file..."), false);
endif;
// now $file needs to be validated
$a = new Audio($file);
if( $a->error && $a->error_code != 3 )
	_result($a->error, false);

$id = uniqid();

// saves some info
$_SESSION[$id] = array(
		'tmp_url' => $a->audio,
		'is_voice' => $is_voice,
		'duration' => floor( $a->info['playtime_seconds'])
	);
if( $a->error && $a->error_code == 3 )
	_result(
		$a->error,
		false,
		array(
			'tmp_url' => url() . INC . TMP .
				last( explode('/', $a->audio) ),
			'id' => $id
			)
		);
_result( true, true,
	array(
		'tmp_url' => url() . INC . TMP .
			last( explode('/', $a->audio) ),
		'id' => $id
		)
	);
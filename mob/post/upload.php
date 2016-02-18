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
$audio = new Audio( $file );
if( $audio->error )
	result_error( $audio->error, $audio->error_code );
// ------------- audio processed ---------
$description = validate_args( $_POST['description'] ) ?
	trim( $_POST['description'] )
:
	false;
$s_twitter = validate_args( $_POST['s_twitter'])
	&& in_array( $_POST['s_twitter'], array('1', '0') ) ?
		$_POST['s_twitter']
	:
		false;
if( ! ($description && $s_twitter) )
	result_error(
			__("There was an error")
		);
if( mb_strlen($description, 'utf-8') > 200 )
	result_error(
			__("The description can't be longer than 200 characters")
		);
while( file_exists(
	$_SERVER['DOCUMENT_ROOT'] . '/assets/audios/' .
	$new_name = substr( md5( uniqid() . rand(1,100) ), 0, 26 ) . '.mp3'
	)
);
// $n is now the new name
// look at this trick
rename( $file, $new_name ); //magic!

$db->insert("audios", array(
		$audio_id = generate_id(), // the id
		$_USER->id, // user id
		$new_name, // nameofthefile.mp3
		0, // reply_to 
		$description,
		0, // twitter id
		time(),
		0, // plays
		0, // favorites
		floor( $a->info['playtime_seconds'] ),
		$is_voice
	)
);

if( '1' == $send_to_twitter ) {
	// magic!
	$tweet = 'https://twitaudio.com/'. $audio_id;
	$tweet_length = strlen($tweet);
	if( strlen($description) > (140-$tweet_length) )
		$description = substr(
				$description,
				0,
				140-$tweet_length-4
			) . '...';
	$tweet = $description . ' ' . $tweet;
	if( $tweet_id = $twitter->tweet($tweet) )
		$db->update("audios", array(
				"tw_id" => $tweet_id
			)
		)->where("id", $audio_id)->_();
}

result_success( true, json_display_audio(
		$db->query(
				'SELECT * FROM audios
				WHERE id = ?',
				$audio_id
			)
	)
);
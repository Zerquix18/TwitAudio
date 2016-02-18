<?php
/**
* AJAX post file
* This file post the audio after it's uploaded and it's in the tmp dir
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit;

if( ! is_logged() )
	_result( __("Authentication required."), false);
/**
* @var $_POST['id'] string (the temporary id stored in $_SESSION)
* @var $_POST['description'] string (the description of the post)
* @var $_POST['s_twitter'] string numeric ( send to tw - 1 or 0)
**/
if( ! validate_args(
		$_POST['id'],
		$_POST['description'],
		$_POST['s_twitter'],
		$_POST['effect']
		)
	)
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( ! array_key_exists($_POST['id'], $_SESSION) )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( 'original' !== $_POST['effect'] &&
		! in_array(
			$_POST['effect'],
			get_available_effects()
		)
	) // go hack your mother !
	_result(
		__('There was an error while processing your request.'),
		false
	);

$id = $_POST['id'];
$description = trim($_POST['description']);
$send_to_twitter = $_POST['s_twitter'];

if( $_SESSION[$id]['duration'] > get_user_limit('audio_duration') )
	_result(
		__('There was an error while processing your request.'),
		false
	);
// can't overpass limits
if( mb_strlen($description, 'utf-8') > 200 )
	_result( __("The description can't be longer than 200 characters"), false );

if( ! in_array( $send_to_twitter, array('1', '0') ) )
	$send_to_twitter = '1';

// ok then

while( file_exists(
	$_SERVER['DOCUMENT_ROOT'] . '/assets/audios/' .
	$new_name = substr( md5( uniqid() . rand(1,100) ), 0, 26 ) . '.mp3'
	)
);

if( 'original' !== $_POST['effect'] )
	$tmp_url = $_SESSION[ $id ]['effects'][ $_POST['effect'] ]['filename'];
else
	$tmp_url = $_SESSION[ $id ]['tmp_url'];

rename(
	$tmp_url,
	$_SERVER['DOCUMENT_ROOT'] . '/assets/audios/' . $new_name
);
// get in bitch
$db->insert("audios", array(
		$audio_id = generate_id(),
		$_USER->id,
		$new_name, // nameofthefile.mp3
		0, // reply_to 
		$description,
		0, // twitter id
		time(),
		0, // plays
		0, // favorites
		(string) $_SESSION[$id]['duration'],
		(string) (int) $_SESSION[$id]['is_voice']
	)
);
clean_tmp( $_SESSION[$id] );
unset($_SESSION[$id]);

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

_result( __("Audio successfully posted!"), true);
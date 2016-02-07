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
if( ! validate_args( $_POST['id'], $_POST['description']) )
	_result(
		__('There was an error while processing your request.'),
		false
	);
// evaluated in this way because isset("0") returns false
if( ! array_key_exists('s_twitter', $_POST) )
	_result(
		__('There was an error while processing your request.'),
		false
	);
// check the id exists
if( ! array_key_exists($_POST['id'], $_SESSION) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$id = $_POST['id'];
// todo: replace this duration and make it dynamic
// with the premium version
if( $_SESSION[$id]['duration'] > 120 )
	_result(
		__('There was an error while processing your request.'),
		false
	);
$_POST['description'] = trim($_POST['description']);
// can't overpass limits
if( mb_strlen($_POST['description'], 'utf-8') > 200 )
	_result( __("The description can't be longer than 200 characters"), false );

#extract_hashtags( $_POST['description'] );

// ok then

while( file_exists( // never repeat a name!
	$_SERVER['DOCUMENT_ROOT'] . '/assets/audios/' .
	$n = substr( md5( uniqid() . rand(1,100) ), 0, 26 ) . '.mp3'
	)
);
// $n is now the new name

rename(
	$_SESSION[$id]['tmp_url'],
	$_SERVER['DOCUMENT_ROOT'] . '/assets/audios/' . $n
);
// get in bitch
$db->insert("audios", array(
		$a_id = generate_id(), // the id
		$_USER->id, // user id
		$n, // nameofthefile.mp3
		0, // reply_to 
		$db->real_escape($_POST['description']),
		0, // twitter id
		time(),
		0, // plays
		0, // favorites
		(string) $_SESSION[$id]['duration'],
		(string) (int) $_SESSION[$id]['is_voice']
	)
);

unset($_SESSION[$id]); #no longer needed, just may cause problems

if( $_POST['s_twitter'] === '1' ) {
	// Good luck understanding this.
	// Anyway I don't think this should be touched
	$tweet = 'https://twitaudio.com/'. $a_id;
	$len = strlen($tweet);
	$desc = $_POST['description'];
	if( strlen($desc) > (140-$len) )
		$desc = substr($desc, 0, 140-$len-4 ) . '...';
	$tweet = $desc . ' ' . $tweet;
	$x = $twitter->tweet($tweet);
	if( $x )
		$db->update("audios", array(
				"tw_id" => $x
			)
		)->where("id", $a_id)->_();
}

_result( __("Audio successfully posted!"), true);
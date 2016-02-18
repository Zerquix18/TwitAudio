<?php
/**
* AJAX reply file
* This file ads a reply to an audio
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
* @var $_POST['reply'] is the reply text
* @var $_POST['s_twitter'] (send to twitter) must be 1 or 0
* @var $_POST['id'] is the ID of the audio we'll add the reply to
*
**/
if( ! validate_args(
	@$_POST['reply'],
	@$_POST['id'])
	)
	_result(
		__("There was an error while processing your request."),
		false
	);
// // evaluated in this way because isset("0") returns false
if( ! array_key_exists('s_twitter', $_POST) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$id = $_POST['id'];
$send_to_twitter = $_POST['s_twitter'];
$reply = trim($_POST['reply']);

if( ! is_audio_id_valid( $id ) )
	_result(
		__("There was an error while processing your request."),
		false
	);

if( in_array($send_to_twitter, array("1", "0") ) )
	$send_to_twitter = '1';

if( empty($reply) )
	_result( __("The reply cannot be empty."), false);

if( mb_strlen($reply, 'utf-8') > 200 )
	_result( __("The reply cannot be longer than 200 characters") );

$audio = $db->query(
	"SELECT reply_to,tw_id,user FROM audios
	WHERE id = ? AND status = '1'",
	$id
);
if( 0 == $audio->nums )
	_result(
		__("The audio you're trying to reply was deleted or is no longer available"),
		false
	);
// if reply_to !== 0 is because it is a reply
// can you reply a reply? not yet
if( $audio->reply_to != '0' )
	_result( __("You cannot reply a reply."), false);

// everything ok
$db->insert("audios", array(
		$audio_id = generate_id(),
		$_USER->id,
		'', // audio.mp3 (not used here)
		$id, // reply_to
		$reply,
		0,
		time(),
		0,
		0,
		0,
		'0' // is_voice (the answer is no)
	)
);
if( '1' == $send_to_twitter ) {
	$tweet = ' - https://twitaudio.com/'. $audio_id;
	$tweet_length = strlen($tweet);
	$at = $db->query(
		"SELECT user FROM users WHERE id = ?",
		$audio->user
	);
	$at = $at->user;
	$reply = "@$at " . $reply;
	if( strlen($reply) > (140-$tweet_length) )
		$desc = substr($desc, 0, 140-$tweet_length-3) . '...';
	$tweet = $reply . $tweet;
	$in_reply_to = $audio->tw_id !== '' ? $audio->tw_id : '';
	if( $tweet_id = $twitter->tweet($tweet, $in_reply_to) )
		$db->update("audios", array(
				"tw_id" => $tweet_id
			)
		)->where("id", $audio_id)->_();
}


display_audio(
	$db->query("SELECT * FROM audios WHERE id = ?", $audio_id)
);
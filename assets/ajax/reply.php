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
	@$_POST['s_twitter'],
	@$_POST['id'])
	)
	_result( __("Request malformed."), false );

// is the audio id valid?
if( ! preg_match("/^[A-Za-z0-9]{6}$/", $_POST['id']) )
	_result( __("Request malformed."), false);
// // evaluated in this way because isset("0") returns false
if( ! array_key_exists('s_twitter', $_POST) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$_POST['reply'] = trim($_POST['reply']);
if( empty($_POST['reply']) )
	_result( __("The reply cannot be empty."), false);

if( mb_strlen($_POST['reply'], 'utf-8') > 200 )
	_result( __("The reply cannot have more than 200 characters") );
// does the audio exist?
$exists = $db->query(
	"SELECT reply_to,tw_id,user FROM audios WHERE id = ?",
	$_POST['id'] // 'id' is protected by regex
);
if( $exists->nums === 0 )
	_result(
		__("The audio you're trying to reply was deleted or is no longer available"),
		false
	);
// if reply_to !== 0 is because it is a reply
// can you reply a reply? not yet
if( $exists->reply_to != '0' )
	_result( __("You cannot reply a reply."), false);

// everything ok
$db->insert("audios", array(
		$a_id = generate_id(), // audio id
		$_USER->id, // user id
		'', // audio.mp3 (not used here)
		$_POST['id'], // reply_to
		$db->real_escape( $_POST['reply'] ),
		0,
		time(),
		0,
		0,
		0,
		'0' // is_voice (the answer is no)
	)
);
if( $_POST['s_twitter'] === '1' ) {
	// MUST know chinese to understand this
	$tweet = ' - https://twitaudio.com/'. $a_id;
	$len = strlen($tweet);
	$at = $db->query(
		"SELECT user FROM users WHERE id = ?",
		$exists->user
	);
	$at = $at->user;
	$len2 = strlen($at);
	$desc = "@$at ";
	$desc .= $_POST['reply'];
	if( strlen($desc) > (140-$len) )
		$desc = substr($desc, 0, 140-$len-3) . '...';
	$tweet = $desc . $tweet;
	$reply_to = $exists->tw_id !== '' ? $exists->tw_id : '';
	$x = $twitter->tweet($tweet, $reply_to);
	if( $x )
		$db->update("audios", array(
				"tw_id" => $x
			)
		)->where("id", $a_id)->_();
}

display_audio(
	$db->query("SELECT * FROM audios WHERE id = ?", $a_id)
);
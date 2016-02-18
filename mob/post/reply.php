<?php
/**
* Mobile API play file
* This inserts a reply fir the given 'id'
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
checkAuthorization();
if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;
if( ! validate_args(
	@$_POST['reply'],
	@$_POST['s_twitter'],
	@$_POST['id'])
	)
	result_error( __('Missing fields.'), 4);

$send_to_twitter = in_array($_POST['s_twitter'], array("1", "0") ) ?
		$_POST['s_twitter']
	:
		'1';

$reply = trim($_POST['reply']);

if( empty($reply) )
	result_error( __("The reply cannot be empty.") );
if( mb_strlen($reply, 'utf-8') > 200 )
	result_error( __("The reply cannot have more than 200 characters") );
$audio = $db->query(
	"SELECT id,reply_to,tw_id,user FROM audios WHERE id = ?",
	$_POST['id']
);
if( $audio->nums === 0 )
	result_error( __("The audio you're trying to reply does not exist.") );
if( $audio->reply_to != '0' )
	result_error( __("You cannot reply a reply.") );

// everything ok
$db->insert("audios", array(
		$audio_id = generate_id(),
		$_USER->id,
		'',
		$audio->id,
		$reply,
		0,
		time(),
		0,
		0,
		0,
		'0'
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
result_success( array(
		'id' => $audio_id
	)
);
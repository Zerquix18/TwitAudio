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
// send to twitter
$s_twitter = in_array($_POST['s_twitter'], array("1", "0") ) ?
		$_POST['s_twitter']
	:
		'0';
$reply = trim($_POST['reply']);
if( empty($reply) )
	result_error( __("The reply cannot be empty.") );
if( mb_strlen($reply, 'utf-8') > 200 )
	result_error( __("The reply cannot have more than 200 characters") );
$exists = $db->query(
	"SELECT id,reply_to,tw_id,user FROM audios WHERE id = ?",
	$db->real_escape($_POST['id'])
);
if( $exists->nums === 0 )
	result_error( __("The audio you're trying to reply does not exist.") );
if( $exists->reply_to != '0' )
	result_error( __("You cannot reply a reply.") );

// everything ok
$db->insert("audios", array(
		$a_id = generate_id(),
		$_USER->id,
		'',
		$exists->id,
		$db->real_escape( $reply ),
		0,
		time(),
		0,
		0,
		0,
		'0'
	)
);
if( $_POST['s_twitter'] === '1' ) {
	//hello...it'sme
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
result_success( array(
		'id' => $a_id
	)
);
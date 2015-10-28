<?php
require_once('../../load.php');

('POST' !== getenv('REQUEST_METHOD') ) and exit();

! is_logged() and _result( __("Authentication required."), false);

if( ! validate_args( @$_POST['comment'], @$_POST['s_twitter'], @$_POST['id']) )
	_result( __("Request malformed.") );

if( ! preg_match("/^[A-Za-z0-9]{6}$/", $_POST['id']) )
	_result( __("Request malformed."), false);
if( ! in_array($_POST['s_twitter'], array("1", "0") ) )
	_result( __("Request malformed"), false );
$_POST['comment'] = trim($_POST['comment']);
if( empty($_POST['comment']) )
	_result( __("The comment cannot be empty."), false);
if( mb_strlen($_POST['comment'], 'utf-8') > 200 )
	_result( __("The comment cannot have more than 200 characters") );
$exists = $db->query("SELECT reply_to,tw_id,user FROM audios WHERE id = ?", $_POST['id']); // 'id' is protected by regex
if( $exists->nums === 0 )
	_result( __("The audio you're trying to reply does not exist."), false );
if( $exists->reply_to != '0' )
	_result( __("You cannot reply a comment."), false);

// everything ok
$db->insert("audios", array(
		$a_id = generate_id(),
		$_USER->id,
		'',
		$_POST['id'],
		$db->real_escape( $_POST['comment'] ),
		0,
		time(),
		0,
		0,
		0
	)
);
if( $_POST['s_twitter'] === '1' ) {
	$tweet = ' - https://twitaudio.com/'. $_POST['id'];
	$len = strlen($tweet);
	$at = $db->query("SELECT user FROM users WHERE id = ?", $exists->user);
	$at = $at->user;
	$len2 = strlen($at);
	$desc = "@$at ";
	$desc .= $_POST['comment'];
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
display_comment($db->query("SELECT * FROM audios WHERE id = ?", $a_id));
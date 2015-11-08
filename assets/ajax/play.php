<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

('POST' !== getenv('REQUEST_METHOD') ) and exit();

if( ! validate_args( @$_POST['id'] ) )
	_result( __('Request malformed.'), false );

$id = $_POST['id'];

if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id ) )
	_result( __('Request malformed.'), false );

// does audio exist ?

$exists_audio = $db->query(
	"SELECT plays,reply_to FROM audios WHERE id = ?",
	$id
);
if( ! (int) $exists_audio->nums )
	_result( __("The audio you tried to play doesn't exist."), false );

if( $exists_audio->reply_to != '0' )
	_result( __("You cannot play a comment. LOL"), false);

$ip = getip();

// was played ?
$was_played = $db->query(
	"SELECT COUNT(*) AS size FROM plays
	WHERE user_ip = ?
	AND audio_id = ?",
	$ip,
	$id
);
$was_played = (int) $was_played->size;
if( $was_played )
	_result(null, false);
$db->query("UPDATE audios SET plays = plays+1 WHERE id = ?", $id);
$db->insert("plays", array(
		$ip,
		$id,
		time()
	)
);

_result(null, true, array('count' => (int) $exists_audio->plays + 1) );
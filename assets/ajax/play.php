<?php
/**
* AJAX play file
* This file logs the plays of an audio. Increases 1 per ip
* This file should be only be requested in an AJAX request
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit;
/**
* @var $_POST['id'] string (the audio id)
**/
if( ! validate_args( @$_POST['id'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$id = $_POST['id'];

if( ! is_audio_id_valid($id ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$audio = $db->query(
	"SELECT plays,reply_to FROM audios
	WHERE id = ? AND status = '1'",
	$id
);
if( 0 == $audio->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	);
// if reply_to !== 0 is because it is a reply
// can you play a reply?
if( $audio->reply_to != '0' )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$ip = getip();

$was_played = $db->query(
	"SELECT COUNT(*) AS size FROM plays
	WHERE user_ip = ?
	AND audio_id = ?",
	$ip,
	$id
);
$was_played = (int) $was_played->size;
if( $was_played ) // nothing to do
	_result(null, false);

$db->query("UPDATE audios SET plays = plays+1 WHERE id = ?", $id);
$db->insert("plays", array(
		$ip,
		$id,
		time()
	)
);

_result (null, true, array(
		'count' => (int) $audio->plays + 1
	)
);
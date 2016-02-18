<?php
/**
* Mobile API play file
* This register a play for the given 'id'
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
checkAuthorization();
if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;
if( ! validate_args( @$_POST['id'] ) )
	result_error( __('Missing fields.'), 4);

$id = $_POST['id'];

$audio = $db->query(
	"SELECT plays,reply_to FROM audios WHERE id = ?",
	$id
);

if( 0 == $audio->nums )
	result_error( __("The audio you tried to play doesn't exist.") );

if( $audio->reply_to != '0' )
	result_error( __("A reply is not playable") );

$ip = getip();

$was_played = $db->query(
	"SELECT COUNT(*) AS size FROM plays
	WHERE user_ip = ?
	AND audio_id = ?",
	$ip,
	$id
);
$was_played = (int) $was_played->size;
if( $was_played )
	result_success(false);
$db->query("UPDATE audios SET plays = plays+1 WHERE id = ?", $id);
$db->insert("plays", array(
		$ip,
		$id,
		time()
	)
);

result_success(true, array(
		'count' => (int) $audio->plays + 1
	)
);
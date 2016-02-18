<?php
/**
* AJAX delete file
* This deletes the audio sent in the param 'id'
* This file should be only be requested in an AJAX request
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if ('POST' !== getenv('REQUEST_METHOD') )
	exit;

if( ! is_logged() )
	_result( __("Authentication required."), false);

if( ! validate_args( @$_POST['id'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$id = $_POST['id'];

if( ! is_audio_id_valid( $id ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$audio = $db->query(
	"SELECT user,audio FROM audios
	WHERE id = ? AND status = '1'",
	$id
);

if( 0 == $audio->nums )
	_result(
		__("The audio you request was deleted or is no longer available."),
		false
	);
if( $audio->user !== $_USER->id ) // its not the author
	_result(
		__('There was an error while processing your request.'),
		false
	);

$db->query("DELETE FROM audios WHERE id = ?", $id);
$db->query("DELETE FROM favorites WHERE audio_id = ?", $id);
$db->query("DELETE FROM plays WHERE audio_id = ?", $id);
$db->query("DELETE FROM audios WHERE reply_to = ?", $id);
@unlink(
	$_SERVER['DOCUMENT_ROOT'] .
		'assets/audios/' . $audio->audio
	);

_result($id, true);
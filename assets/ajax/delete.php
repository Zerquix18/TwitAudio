<?php
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

if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

// does audio exist ?

$exists = $db->query(
	"SELECT user,audio FROM audios WHERE id = ?",
	$id // regex protected m8
);

if( $exists->nums === 0 )
	_result(
		__("The audio you request was deleted or is no longer available."),
		false
	);
if( $exists->user !== $_USER->id ) // its not the author
	_result(
		__('There was an error while processing your request.'),
		false
	);

$db->query("DELETE FROM audios WHERE id = ?", $id);
$db->query("DELETE FROM favorites WHERE audio_id = ?", $id);
$db->query("DELETE FROM plays WHERE audio_id = ?", $id);
$db->query("DELETE FROM audios WHERE reply_to = ?", $id);
@unlink( PATH . INC . 'audios/' . $exists->audio);

_result($id, true);
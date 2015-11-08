<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

('POST' !== getenv('REQUEST_METHOD') ) and exit();

if( ! is_logged() )
	_result( __("Authentication required."), false);

if( ! validate_args( @$_POST['id'] ) )
	_result( __('Request malformed.'), false );

$id = $_POST['id'];

if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id ) )
	_result( __('Request malformed.'), false );

// does audio exist ?

$exists = $db->query(
	"SELECT user,audio FROM audios WHERE id = ?",
	$id
);

if( $exists->nums === 0 )
	_result( __("That audio doesn't exist."), false);
if( $exists->user !== $_USER->id )
	_result( __("You are not the author of this audio."), false);

$db->query("DELETE FROM audios WHERE id = ?", $id);
$db->query("DELETE FROM favorites WHERE audio_id = ?", $id);
$db->query("DELETE FROM plays WHERE audio_id = ?", $id);
$db->query("DELETE FROM audios WHERE reply_to = ?", $id);
@unlink( PATH . INC . 'audios/' . $exists->audio);

_result($id, true);
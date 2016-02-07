<?php
/**
* Mobile API delete file
* This file deletes the audio requested in 'id'
* @author Zerquix18
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
checkAuthorization();
if( 'POST' !== getenv('REQUEST_METHOD') )
	exit; // did you read (post)/delete.php
if( ! validate_args( @$_POST['id'] ) )
	result_error( __('Missing fields.'), 4);
$id = $_POST['id'];

// does audio exist ?

$exists = $db->query(
	"SELECT user,audio FROM audios WHERE id = ?",
	$db->real_escape($id) // never trust anybody
);

if( $exists->nums === 0 ) // ups
	result_error( __("That audio doesn't exist."), 10);
if( $exists->user !== $_USER->id ) // ups x2
	result_error( __("You are not the author of this audio."), 11);
// so I'll say goodbye again
$db->query("DELETE FROM audios WHERE id = ?", $id);
$db->query("DELETE FROM favorites WHERE audio_id = ?", $id);
$db->query("DELETE FROM plays WHERE audio_id = ?", $id);
$db->query("DELETE FROM audios WHERE reply_to = ?", $id);
@unlink(
	$_SERVER['DOCUMENT_ROOT'] .
		'/assets/audios/' . $exists->audio
	);

result_success(); // ;) 
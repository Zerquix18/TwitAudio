<?php
/**
* AJAX favorite file
* This file should be only be requested in an AJAX request
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( 'POST' !== getenv('REQUEST_METHOD') )
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

$exists_audio = $db->query(
	"SELECT user,favorites FROM audios WHERE id = ?",
	$id
);
if( ! (int) $exists_audio->nums )
	_result(
		__("The audio you tried to favorite was deleted or is no longer available."),
		false
	);
if( ! can_listen( $exists_audio->user ) )
	_result( __("This user's audios are private."), false );

// already faved?

$favorited = $db->query(
	"SELECT COUNT(*) AS size FROM favorites
	WHERE audio_id = ?
	AND user_id = ?",
	$id,
	$_USER->id
);

$favorited = (int) $favorited->size;

$action = isset($_POST['action']) && in_array($_POST['action'],
	array(
		'fav', 'unfav' ) ) ? $_POST['action'] : false;
if( ! $action )
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( $favorited && 'unfav' == $action ) {
	$db->query(
		"UPDATE audios SET favorites = favorites-1 WHERE id = ?",
		$id
	);
	$db->query(
		"DELETE FROM favorites WHERE audio_id = ? AND user_id = ?",
		$id,
		$_USER->id
	);
}else if( ! $favorited && 'fav' == $action ) {
	$db->query(
		"UPDATE audios SET favorites = favorites+1 WHERE id = ?",
		$id
	);
	$db->insert("favorites", array(
			$_USER->id,
			$id,
			time()
		)
	);
}else
	_result( true, true, array(
			'count' => $exists_audio->favorites
		)
	);
$extra = array(
		'count' => $favorited ?
			(int) $exists_audio->favorites - 1
		:
			(int) $exists_audio->favorites + 1
	);
_result(true, true, $extra);
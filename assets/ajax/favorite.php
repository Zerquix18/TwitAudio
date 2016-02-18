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

if( ! is_audio_id_valid( $id ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$audio = $db->query(
	$l ="SELECT user,favorites FROM audios
	WHERE id = ? AND status = '1'",
	$id
);
if( 0 == $audio->nums )
	_result(
		__("The audio you tried to favorite was deleted or is no longer available."),
		false
	);
if( ! can_listen( $audio->user ) )
	_result( __("This user's audios are private."), false );

$is_favorited = $db->query(
	"SELECT COUNT(*) AS size FROM favorites
	WHERE audio_id = ?
	AND user_id = ?",
	$id,
	$_USER->id
);

$is_favorited = (int) $is_favorited->size;

$action = isset($_POST['action']) && in_array($_POST['action'],
	array(
		'fav', 'unfav' )
	) ? $_POST['action'] : false;
if( ! $action )
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( $is_favorited && 'unfav' == $action ) {
	$db->query(
		"UPDATE audios SET favorites = favorites-1 WHERE id = ?",
		$id
	);
	$db->query(
		"DELETE FROM favorites WHERE audio_id = ? AND user_id = ?",
		$id,
		$_USER->id
	);
}else if( ! $is_favorited && 'fav' == $action ) {
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
			'count' => $audio->favorites
		)
	);
$extra = array(
		'count' => $is_favorited ?
			(int) $audio->favorites - 1
		:
			(int) $audio->favorites + 1
	);
_result(true, true, $extra);
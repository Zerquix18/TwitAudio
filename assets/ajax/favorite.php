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

$exists_audio = $db->query(
	"SELECT user FROM audios WHERE id = ?",
	$id
);
if( ! (int) $exists_audio->nums )
	_result( __("The audio you tried to favorite doesn't exist."), false );
if( ! can_listen( $exists_audio->user ) )
	_result( __("You can't listen to this user's audios, so you can neither favorite them."), false );

// already faved?

$favorited = $db->query(
	"SELECT COUNT(*) AS size FROM favorites
	WHERE audio_id = ?
	AND user_id = ?",
	$id,
	$_USER->id
);

$favorited = (int) $favorited->size;

if( $favorited ) {
	$db->query(
		"UPDATE audios SET favorites = favorites-1 WHERE id = ?",
		$id
	);
	$db->query(
		"DELETE FROM favorites WHERE audio_id = ? AND user_id = ?",
		$id,
		$_USER->id
	);
}else{
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
}
$extra = array(
		'action' => $favorited ? 'favorited' : 'favorite',
		'count' => $favorited ? $favorited - 1 : $favorited + 1
	);
_result(true, true, $extra);
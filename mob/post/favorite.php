<?php
/**
* Mobile API favorite file
* This file (un)/favorites the audio requested in 'id'
* @author Zerquix18
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
checkAuthorization();
if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;
if( ! validate_args( @$_POST['id'] ) )
	result_error( __('Missing fields.'), 4);
$id = $_POST['id'];
$a = $db->query(
		'SELECT id,favorites FROM audios WHERE id = ?',
		$db->real_escape($_POST['id'])
	);
if( $a->nums === 0 )
	result_error(
			__("The audio you tried to favorite does not exist."),
			9
		);
// exists.
$id = $a->id;
//was faved?
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
	result_error(
		__('There was an error while processing your request.'),
		null
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
}elseif( ! $favorited && 'fav' == $action ) {
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
}else // no way 
	result_success( array(
		'count' => $exists_audio->favorites
		)
	);

result_success( null, array(
		'count'	=> $favorited ?
			(int) $exists_audio->favorites - 1
		:
			(int) $exists_audio->favorites + 1
	)
);
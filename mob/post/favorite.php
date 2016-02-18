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

$audio = $db->query(
		'SELECT id,favorites FROM audios WHERE id = ?',
		$_POST['id']
	);
if( $audio->nums === 0 )
	result_error(
			__("The audio you tried to favorite does not exist."),
			9
		);

$id = $audio->id;

$is_favorited = $db->query(
	"SELECT COUNT(*) AS size FROM favorites
	WHERE audio_id = ?
	AND user_id = ?",
	$id,
	$_USER->id
);
$is_favorited = (int) $favorited->size;

$action = isset($_POST['action']) && in_array($_POST['action'],
	array(
		'fav', 'unfav' ) ) ? $_POST['action'] : false;
if( ! $action )
	result_error(
		__('There was an error while processing your request.'),
		null
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
}elseif( ! $is_favorited && 'fav' == $action ) {
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
		'count' => $audio->favorites
		)
	);

result_success( null, array(
		'count'	=> $is_favorited ?
			(int) $audio->favorites - 1
		:
			(int) $audio->favorites + 1
	)
);
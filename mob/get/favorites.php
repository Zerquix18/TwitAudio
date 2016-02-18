<?php
/**
* Mobile API favorites file
* This file loads the favorites of an user in the app
* and should only be requested in from the app.
* But we know it won't be that way.
* @author Zerquix18
*
**/
// this file is basically a copy-paste of 'audios.php'
// so if you want to understand it, go to that file
// and don't bother
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mob/functions.php';
checkAuthorization();
header("Cache-Control: private, max-age=900");
$is_the_logged_user = validate_args( $_GET['user'] )
	&& strcasecmp($_GET['user'], $_USER->user) == 0;
if( ! $is_the_logged_user ):
	$user = $db->query(
		'SELECT id FROM users WHERE user = ?',
		$_GET['user']
	);
	if( $user->nums == 0 )
		result_error( __('The user does not exist.'), 7);
	$id = $user->id;
	if( ! can_listen($id) ):
		result_error(
				__('No permissions'),
				8
			);
	endif;
else:
	$id = $_USER->id;
endif;
$query = 'SELECT * FROM audios
	WHERE id IN (
			SELECT audio_id FROM favorites
			WHERE user_id = ?
		)
	ORDER BY time DESC';
$count = $db->query(
	'SELECT COUNT(*) AS size FROM audios
	WHERE id IN (
		SELECT audio_id FROM favorites
		WHERE user_id = ?
		)',
	$id
);
$count = (int) $count->size;
if(  0 == $count )
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
$total_page = ceil( $count / 10 );
$page =validate_args($_GET['p']) ? sanitize_pageNumber( $_GET['p'] ) : 1;
if( $page > $total_audios )
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
//
$query .= ' LIMIT '. ($page-1) * 10 . ',10';
$audios = $db->query($query, $id);
$result = array();
$result['audios'] = array();
while( $audio = $audios->r->fetch_array() )
	$result['audios'][] = json_display_audio($audio);
$result['p'] = $page;
$result['load_more'] = ($page < $total_audios);
result_success( null, $result );
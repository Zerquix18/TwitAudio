<?php
/**
* Mobile API audios file
* This file loads the audios of an user in the app
* and should only be requested in from the app.
* But we know it won't be that way.
* @author Zerquix18
*
**/
// get my backpack
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mob/functions.php';
// check that Authorization header was sent and valid
checkAuthorization();
// allow cache
header('Cache-Control: public, max-age=900');

$is_the_logged_user = validate_args( $_GET['user'] )
	&& strcasecmp($_GET['user'], $_USER->user) == 0;
if( ! $is_the_logged_user ):
	$user = $db->query(
		'SELECT id FROM users WHERE user = ?',
		$_GET['user']
	);
	if( 0 == $user->nums )
		result_error(
			__('The user does not exist.'),
			7
		);
	$id = $user->id;
	if( ! can_listen($id) ):
		result_error(
				__('The audios of this user are private'),
				8
			);
	endif;
else:
	$id = $_USER->id;
endif;
/** everything below this line is magic */
$query = 'SELECT * FROM audios
	WHERE user = ?
	AND reply_to = \'0\'
	ORDER BY time DESC';
$count = $db->query(
	'SELECT COUNT(*) AS size FROM audios
	WHERE user = ? AND reply_to = \'0\'',
	$id
);
$count = (int) $count->size;
if( 0 == $count ) // no audios no result
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
$total_pages = ceil( $count / 10 );

$page = validate_args($_GET['p']) ? sanitize_pageNumber( $_GET['p'] ) : 1;
if( $page > $total_pages ) // no more pages
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
//
$query .= ' LIMIT '. ($page-1) * 10 . ',10'; // pagination
$audios = $db->query($query, $id);
$result = array();
$result['audios'] = array();
while( $audio = $audios->r->fetch_array() )
	$result['audios'][] = json_display_audio($audio);
$result['p'] = $page;
$result['load_more'] = ($page < $total_pages);
result_success( null, $result );
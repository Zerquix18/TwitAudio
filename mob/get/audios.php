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

$x = isset( $_GET['user'] ) && is_string($_GET['user']) // ← validation
	&& strcasecmp($_GET['user'], $_USER->user) !== 0;
if( $x ): // if 'user' is passed and its not the logged one
	$u = $db->query(
		'SELECT id FROM users WHERE user = ?',
		$db->real_escape($_GET['user'])
	);
	if( $u->nums == 0 )
		result_error( __('The user does not exist.'), 7);
	$id = $u->id;
	if( ! can_listen($id) ):
		result_error(
				__('No permissions'),
				8
			);
	endif;
else:
	$id = $_USER->id;
endif;
/** everything below this line is magic */
$q = 'SELECT * FROM audios
	WHERE user = ?
	AND reply_to = \'0\'
	ORDER BY time DESC';
$count = $db->query(
	'SELECT COUNT(*) AS size FROM audios
	WHERE user = ? AND reply_to = \'0\'',
	$id
);
$count = (int) $count->size;
if( ! $count ) // no audios no result
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
$total_audios = ceil( $count / 10 );
// p means page
$p = isset($_GET['p']) && is_numeric($_GET['p']) ? (int) $_GET['p'] : 1;
if( $p > $total_audios ) // no more pages
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
//
$q .= ' LIMIT '. ($p-1) * 10 . ',10'; // pagination
$audios = $db->query($q, $id);
$result = array();
$result['audios'] = array();
while( $a = $audios->r->fetch_array() )
	$result['audios'][] = json_display_audio($a);
$result['p'] = $p;
$result['load_more'] = ($p < $total_audios);
result_success( null, $result );
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
$q = 'SELECT * FROM audios
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
if( ! $count )
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
$total_audios = ceil( $count / 10 );
$p = isset($_GET['p']) && is_numeric($_GET['p']) ? (int) $_GET['p'] : 1;
if( $p > $total_audios )
	result_success( null, array(
			'audios' 	=> array(),
		)
	);
//
$q .= ' LIMIT '. ($p-1) * 10 . ',10';
$audios = $db->query($q, $id);
$result = array();
$result['audios'] = array();
while( $a = $audios->r->fetch_array() )
	$result['audios'][] = json_display_audio($a);
$result['p'] = $p;
$result['load_more'] = ($p < $total_audios);
result_success( null, $result );
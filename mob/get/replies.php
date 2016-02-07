<?php
/**
* Mobile API replies file
* This file loads the replies of an audio in the app
* and should only be requested in from the app.
* But we know it won't be that way.
* @author Zerquix18
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mob/functions.php';
checkAuthorization();
header("Cache-Control: private, max-age=900");

if( ! validate_args( $_GET['id'])  )
	result_error( __('Missing fields.'), 4);
$a = $db->query(
	"SELECT user,id FROM audios WHERE id = ?",
	$db->real_escape( $_GET['id'])
);
if( ! $a->nums )
	result_error( __('Requested audio does not exist.') );
if( ! can_listen($a->user) )
	result_error( __('No permissions.' ) );
$id = $a->id;
/** magic ALERT: read audios.php to understand this */
$p = isset($_GET['p']) && is_numeric($_GET['p']) ? (int) $_GET['p'] : 1;
$q = "SELECT * FROM audios
	WHERE reply_to = ?
	ORDER BY `time` ASC";
$count = $db->query(
		"SELECT COUNT(*) AS size FROM audios
		WHERE reply_to = ?",
		$id
	);
$count = (int) $count->size;
if( ! $count )
	result_success( null, array(
			'replies' 	=> array(),
		)
	);
$total_audios = ceil( $count / 10 );
$p = isset($_GET['p']) && is_numeric($_GET['p']) ? (int) $_GET['p'] : 1;
if( $p > $total_audios )
	result_success( null, array(
			'replies' 	=> array(),
		)
	);
$q .= ' LIMIT '. ($p-1) * 10 . ',10';
$audios = $db->query($q, $id);
$result = array();
$result['count'] = $count;
$result['replies'] = array();
while( $a = $audios->r->fetch_array() )
	$result['replies'][] = json_display_audio($a);
$result['p'] = $p;
$result['load_more'] = ($p < $total_audios);
result_success( null, $result );
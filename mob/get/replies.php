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
$audio = $db->query(
	"SELECT user,id FROM audios WHERE id = ?",
	$_GET['id']
);
if( 0 == $audio->nums )
	result_error( __('Requested audio does not exist.') );
if( ! can_listen($audio->user) )
	result_error( __('No permissions.' ) );
$id = $audio->id;
$query = "SELECT * FROM audios
	WHERE reply_to = ?
	ORDER BY `time` ASC";
$count = $db->query(
		"SELECT COUNT(*) AS size FROM audios
		WHERE reply_to = ?",
		$id
	);
$count = (int) $count->size;
if( 0 == $count )
	result_success( null, array(
			'replies' 	=> array(),
		)
	);
$total_audios = ceil( $count / 10 );
$page = validate_args($_GET['p']) ? sanitize_pageNumber( $_GET['p'] ) : 1;
if( $page > $total_audios )
	result_success( null, array(
			'replies' 	=> array(),
		)
	);
$query .= ' LIMIT '. ($page-1) * 10 . ',10';
$audios = $db->query($query, $id);
$result = array();
$result['count'] = $count;
$result['replies'] = array();
while( $a = $audios->r->fetch_array() )
	$result['replies'][] = json_display_audio($a);
$result['p'] = $page;
$result['load_more'] = ($page < $total_audios);
result_success( null, $result );
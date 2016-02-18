<?php
/**
* Mobile API audios file
* This file loads the audios of an user in the app
* and should only be requested in from the app.
* But we know it won't be that way.
* @author Zerquix18
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mob/functions.php';
checkAuthorization();
header('Cache-Control: public, max-age=900');

if( ! validate_args( $_GET['q']) )
	result_error( __('Missing fields.'), 4);
$search = trim($_GET['q'], "\x20*\t\n\r\0\x0B");
if( '' == $search )
	result_error( __("Some required fields are empty"), 6);
$search = '*' . $search . '*'; // "*" = wildcard
/**
* a = audios
* u = users
**/
$type = isset($_GET['t']) && in_array($_GET['t'], array('a', 'u') ) ?
		$_GET['t']
	:
		'a';
if( 'a' == $type ):
	$query = 'SELECT * FROM audios
		WHERE reply_to = \'0\'
		AND MATCH(`description`)
		AGAINST (? IN BOOLEAN MODE)';
	$count = $db->query(
		'SELECT COUNT(*) AS size FROM audios
		WHERE reply_to = \'0\'
		AND MATCH(`description`)
		AGAINST (? IN BOOLEAN MODE)',
		$search
	);
else: // if the type is user
	$query = 'SELECT * FROM users
		WHERE MATCH(`user`, `name`, `bio`)
		AGAINST (? IN BOOLEAN MODE)';
	$count = $db->query(
		'SELECT COUNT(*) AS size FROM users
		WHERE MATCH(`user`, `name`, `bio`)
		AGAINST (? IN BOOLEAN MODE)',
		$search
	);
endif;
$count = (int) $count->size;
if( 0 == $count )
	result_success( null, array(
			'count' 		=> 0,
			'audios' 	=> [],
			'load_more' 	=> false,
		)
	);
$page = validate_args($_GET['p']) ? sanitize_pageNumber( $_GET['p'] ) : 1;
/**
* p = plays
* d = date
**/
$sort  = isset($_GET['s']) && in_array($_GET['s'], array('p', 'd') ) ?
		$_GET['s']
	:
		'd';

$total_pages = ceil( $count / 10 );

if( $page > $total_pages )
	result_success( null, array(
			'count' 		=> $count,
			'audios' 	=> array(),
			'p' 		=> $page,
			'load_more' 	=> false,
		)
	);

if( 'a' == $type ):
	if( 'd' == $sort )
		$query .= ' ORDER BY time DESC';
	else
		$query .= ' ORDER BY plays DESC';
endif;

$audios = array();
$query .= ' LIMIT '. ($page-1) * 10 . ',10';
$auds = $db->query($query, $search);
while( $result = $auds->r->fetch_object() ) {
	if( 'a' === $type ):
		if( can_listen($result->user) ):
			$audios[] = json_display_audio($result, true);
		endif;
	else: // if looking for users
		$audios[] = json_display_user_sm($result, true);
	endif;
}
result_success(null, array(
		'count' 		=> $count,
		'audios' 	=> $audios,
		'p' 		=> $page,
		'load_more' 	=> ($page < $total_pages),
	)
);
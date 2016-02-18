<?php
/**
* Mobile API home file
* This file loads the home screen in the app
* and should only be requested in from the app.
* But we know it won't be that way.
* @author Zerquix18
*
**/
/** request necesary files and check everything ok */
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mob/functions.php';
checkAuthorization();

$recent_popular_audios = $db->query(
	'SELECT * FROM audios
	WHERE user NOT IN (
			SELECT id
			FROM users
			WHERE audios_public = \'0\'
		)
	AND reply_to = \'0\'
	AND status = \'1\'
	AND `time` BETWEEN ? AND ?
	ORDER BY plays DESC
	LIMIT 3',
	time() - strtotime('-30 days'),
	time()
);
$recent_audios_by_user = $db->query(
	'SELECT * FROM audios
	WHERE user = ?
	AND reply_to = \'0\'
	AND status = \'1\'
	ORDER BY `time` DESC
	LIMIT 3',
	$_USER->id
);
$return = array(
		'recent_popular' => array(
				'audios' => array()
			),
		'recent_user'	  => array(
				'audios' => array()
			)
	);
//--
while( $audio = $recent_popular_audios->r->fetch_array() )
	$return['recent_popular']['audios'][] = json_display_audio( $audio );
//--
while( $audio = $recent_audios_by_user->r->fetch_array() )
	$return['recent_user']['audios'][] = json_display_audio( $audio );
//--
result_success($return);
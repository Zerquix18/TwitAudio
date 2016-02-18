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
header('Cache-Control: public, max-age=900');
checkAuthorization();
/*
* I am a ghost
* But only if you remember
* So save your prayers and promises for something better
* My hands are tired
* Stuck in this room forever
* I scream but only echoes care to answer
*/
// if $_GET['user'] is send it will use it
// else it will use the logged one
if( validate_args( $_GET['user'] ) &&
	strcasecmp($_GET['user'], $_USER->user) !== 0 )
	$user = $db->query(
		'SELECT * FROM users WHERE user = ?',
		trim($_GET['user'])
	);
else
	$user = $_USER; // no extra queries
if( 0 == $user->nums )
	result_error( __('The user doesn\' exist.'), 7);
// loads public info
$result = array(
		'id' 		=> (int) $u->id,
		'user' 		=> $u->user,
		'name'  	=> $u->name,
		'avatar' 	=> get_image($u->avatar),
		'bio'		=> $u->bio,
		'verified' 	=> (bool) $u->verified,
		'favs_public'	=> (bool) $u->favs_public,
		'audios_public' => (bool) $u->audios_public,
		'can_listen'	=> can_listen($u->id)
	);
result_success(null, $result);
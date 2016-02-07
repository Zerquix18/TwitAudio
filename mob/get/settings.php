<?php
/**
* Mobile API audios file
* This file loads the user's settings in the app
* and should only be requested in from the app.
* But we know it won't be that way.
* @author Zerquix18
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
checkAuthorization();
// ^ $_USER is set 
header('Cache-Control: public, max-age=900');
result_success( null, array(
		'audios_public' => (bool) $_USER->audios_public,
		'favs_public' 	=> (bool) $_USER->favs_public,
		'time'		=> (int) $_USER->time,
	)
);
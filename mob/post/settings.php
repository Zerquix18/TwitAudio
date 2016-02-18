<?php
/**
* Mobile API settings file
* This updates the settings for the logged user
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
checkAuthorization();
if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;
if( ! validate_args(
		$_POST['audios_public'],
		$_POST['favs_public']
		)
	)
	result_error( __('Missing fields.'), 4);

$favs_public = in_array($_POST['favs_public'], array('1', '0') ) ?
	$_POST['favs_public']
:
	$_USER->favs_public;

$audios_public = in_array($_POST['audios_public'], array('1', '0') ) ?
	$_POST['audios_public']
:
	$_USER->audios_public;

$db->update('users', array(
		'favs_public'	=> $favs_public,
		'audios_public' => $audios_public
	)
)->where('id', $_USER->id)->execute();

result_success( __('Settings updated successfully!') );
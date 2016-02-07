<?php
/**
* AJAX settings file
* This updates the user's settings
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit;

if( ! is_logged() )
	_result( __("Authentication required."), false);
// they must be 1 or 0
if( ! array_key_exists( 'audios_public', $_POST )
 || ! array_key_exists('favs_public', $_POST)
	)
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( ! in_array($_POST['audios_public'], array("1", "0") )
 || ! in_array($_POST['favs_public'], array("0", "1") )
 	)
	_result(
		__('There was an error while processing your request.'),
		false
	);

$db->update("users", array(
	"audios_public" => $_POST['audios_public'],
	"favs_public" => $_POST['favs_public']
	)
)->where("id", $_USER->id)->execute();

_result( __("Settings updated successfully :)"), true);
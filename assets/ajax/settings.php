<?php
require_once('../../load.php');

('POST' !== getenv('REQUEST_METHOD') ) and exit();

! is_logged() and _result( __("Authentication required."), false);

if( ! array_key_exists( 'audios_public', $_POST ) || ! array_key_exists('likes_public', $_POST) )
	_result( __("Request malformed."), false);

if( ! in_array($_POST['audios_public'], array("1", "0") ) || ! in_array($_POST['likes_public'], array("0", "1") ) )
	_result( __('Request malformed.'), false);

$db->update("users", array(
	"audios_public" => $_POST['audios_public'],
	"likes_public" => $_POST['likes_public']
	)
)->where("id", $_USER->id)->execute();

_result( __("Settings updated successfully :)"), true);
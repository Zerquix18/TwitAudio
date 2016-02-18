<?php
/**
* This file will check which effects have been applied
* and will return them with their URLs
* it requires an id which is stored in $_SESSION
* @author Zerquix18 <zerquix18>
* @copyright Copyright (c) 2016 - Luis A. Martínez
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;

if( ! is_logged() )
	_result( __("Authentication required."), false);

if( ! validate_args( $_POST['id']) )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( ! isset($_SESSION[ $_POST['id'] ] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$loaded_effects = get_finished_effects(
		$_SESSION[ $_POST['id'] ]['effects']
	);

$loaded_effects_count = count($loaded_effects);

$are_all_loaded =
count( get_available_effects() ) === $loaded_effects_count;

for($i = 0; $i < $loaded_effects_count; $i++) {
	/* replaces the 'file' key. Instead of a full path for backend
	* a full path for front-end. I mean https://...
	**/
	$loaded_effects[$i]['file'] = str_replace(
			$_SERVER['DOCUMENT_ROOT'] . '/',
			url(),
			$loaded_effects[$i]['file']
		);
}

$return = array(
		'loaded_effects'	=>	$loaded_effects,
		'are_all_loaded'		=>	$are_all_loaded
	);
_result( null, true, $return );
<?php
/**
* AJAX favorites file
* This loads more favorites in the profile when the users
* scrolls down.
* This file should be only be requested in an AJAX request
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;

/**
* @var $_POST['q'] is the username
* @var $_POST['p'] is the page to load.
*
**/
if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$q = trim($_POST['q']);
$p = (int) $_POST['p']; // can't be the 1 or less
if( ! is_numeric($_POST['p']) || $p <= 1 || empty($q) )
	_result(
		__('There was an error while processing your request.'),
		false
	);
$exists = $db->query(
		"SELECT id, favs_public FROM users WHERE user = ?",
		$db->real_escape( $_POST['q'])
	);
if( ! $exists->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( ! (int) $exists->favs_public )
	_result( __("This user's favorites are private."), false );

load_favs($exists->id, $p);
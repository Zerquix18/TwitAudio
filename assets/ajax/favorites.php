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

$query = trim($_POST['q']);
$page = sanitize_pageNumber( $_POST['p'] );
if( $page <= 1 || empty($query) )
	_result(
		__('There was an error while processing your request.'),
		false
	);
$user = $db->query(
		"SELECT id, favs_public FROM users WHERE user = ?",
		$query
	);
if( 0 == $user->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( 0 == $user->favs_public )
	_result( __("This user's favorites are private."), false );

load_favs($user->id, $page);
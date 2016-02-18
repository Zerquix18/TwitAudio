<?php
/**
* AJAX post file
* This file loads more replies while the user is scrolling down
* Should be only requested in an AJAX request
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit;
/**
* @var $_POST['q'] is the audio ID
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
$audio = $db->query(
	"SELECT user,id FROM audios
	WHERE id = ? AND status = '1'",
	$query
);
if( 0 == $audio->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( ! can_listen($audio->user) ) // Good intent! :D
	_result(
		__('There was an error while processing your request.'),
		false
	);

load_replies($audio->id, $page);
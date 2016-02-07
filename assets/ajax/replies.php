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

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p <= 1 || empty($q) )
	_result(
		__('There was an error while processing your request.'),
		false
	);
// does it exist?
$a = $db->query(
	"SELECT user,id FROM audios WHERE id = ?",
	$db->real_escape( $_POST['q'])
);
if( ! $a->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( ! can_listen($a->user) ) // Well try! :D
	_result(
		__('There was an error while processing your request.'),
		false
	);

load_replies($a->id, $p);
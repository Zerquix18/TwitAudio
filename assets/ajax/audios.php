<?php
/**
* AJAX audios file
* This loads more audios in the profile when the users
* scrolls down.
* This file should be only be requested in an AJAX request
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/

# get my backback
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( 'POST' !== getenv('REQUEST_METHOD') )
	exit; # why would u need to use get
/**
* @var $_POST['q'] is the username
* @var $_POST['p'] is the page to load.
*
**/
if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	); # missing parameters or they're wrong

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p <= 1 || empty($q) )
	_result(
		__('There was an error while processing your request.'),
		false
	); # p is not a number or is less than 1 or $q is empty
$exists = $db->query(
	"SELECT id FROM users WHERE user = ?",
	$db->real_escape( $_POST['q'] )
);
if( ! $exists->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	); # no users were found
if( ! can_listen($exists->id) )
	_result(
		__('This user\'s audios are private.'),
		false
	); # no authorization to listen to those audios

load_audios($exists->id, $p);
		# user id, page
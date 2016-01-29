<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( 'POST' !== getenv('REQUEST_METHOD') )
	exit; # why would u need to use get

if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	); # missing parameters

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 1 )
	_result(
		__('There was an error while processing your request.'),
		false
	); # dont troll
$exists = $db->query(
	"SELECT id FROM users WHERE user = ?",
	$db->real_escape( $_POST['q'] )
);
if( empty($q) || ! $exists->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( ! can_listen($exists->id) )
	_result(
		__('This user\'s audios are private.'),
		false
	);

load_audios($exists->id, $p);
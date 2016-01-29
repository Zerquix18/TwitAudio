<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit;

if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 2 )
	_result(
		__('There was an error while processing your request.'),
		false
	);
$a = $db->query(
	"SELECT user,id FROM audios WHERE id = ?",
	$db->real_escape( $_POST['q'])
);
if( empty($q) || ! $a->nums )
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
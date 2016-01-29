<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

('POST' !== getenv('REQUEST_METHOD') ) and exit();

if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 1 )
	_result(
		__('There was an error while processing your request.'),
		false
	);
$exists = $db->query("SELECT id, favs_public FROM users WHERE user = ?", $db->real_escape( $_POST['q']) );
if( empty($q) || ! $exists->nums )
	_result(
		__('There was an error while processing your request.'),
		false
	);
if( ! (int) $exists->favs_public )
	_result( __("This user's favorites are private."), false );

load_favs($exists->id, $p);
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

('POST' !== getenv('REQUEST_METHOD') ) and exit();

if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result( __('Request malformed.'), false );

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 1 )
	_result( __('Request malformed.'), false );
$exists = $db->query("SELECT id FROM users WHERE user = ?", $db->real_escape( $_POST['q']) );
if( empty($q) || ! $exists->nums )
	_result( __('Request malformed.'), false );

load_favs($exists->id, $p);
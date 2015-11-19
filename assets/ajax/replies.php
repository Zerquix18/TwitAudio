<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

('POST' !== getenv('REQUEST_METHOD') ) and exit();

! validate_args( @$_POST['q'], @$_POST['p'] ) and _result( __('Request malformed.'), false );

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 2 )
	_result( __('Request malformed.'), false );
$a = $db->query(
	"SELECT user,id FROM audios WHERE id = ?",
	$db->real_escape( $_POST['q'])
);
if( empty($q) || ! $a->nums )
	_result( __('Request malformed.'), false );
if( ! can_listen($a->user) ) // Well try! :D
	_result( __('You cannot read to the replies from this audio.'), false);

load_replies($a->id, $p);
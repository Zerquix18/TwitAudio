<?php
require_once('../../load.php');

('POST' !== getenv('REQUEST_METHOD') ) and exit();

! validate_args( @$_POST['q'], @$_POST['p'] ) and _result( __('Request malformed.'), false );

$q = trim($_POST['q']);
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 1 )
	_result( __('Request malformed.'), false );
$exists = $db->query("SELECT id FROM users WHERE user = ?", $db->real_escape( $_POST['q']) );
if( empty($q) || ! $exists->nums )
	_result( __('Request malformed.'), false );
if( ! can_listen($exists->id) )
	_result( __('You cannot listen to this users audios.'), false);

load_audios($exists->id, $p);
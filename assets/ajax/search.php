<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

('POST' !== getenv('REQUEST_METHOD') ) and exit();

if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result( __('Request malformed.'), false );

$q = trim($_POST['q']);
$s = validate_args( @$_POST['o'] )
	&& in_array($_POST['o'], array("d", "l", "p") ) ?
		$_POST['o']
	:
		'd';
$t = validate_args( @$_GET['t'] )
	&& in_array($_GET['t'], array('u', 'a') ) ?
		$_GET['t']
	:
		'a';
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 2 )
	_result( __('Request malformed.'), false );
if( empty($q) )
	_result( __('Request malformed.'), false );

search( $q, $s, $t, $p);
//     query,search,type,page
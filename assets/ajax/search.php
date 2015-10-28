<?php
require_once('../../load.php');

('POST' !== getenv('REQUEST_METHOD') ) and exit();

! validate_args( @$_POST['q'], @$_POST['p'] ) and _result( __('Request malformed.'), false );

$q = trim($_POST['q']);
$s = validate_args( @$_POST['o'] )
	&& in_array($_POST['o'], array("d", "l", "p") ) ?
		$_POST['o']
	:
		'd';
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p < 1 )
	_result( __('Request malformed.'), false );
if( empty($q) )
	_result( __('Request malformed.'), false );

search($q, $s, $p);
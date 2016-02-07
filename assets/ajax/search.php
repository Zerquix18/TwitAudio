<?php
/**
* AJAX search file
* This loads more content when scrolling in the search page
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;
/**
* @var $_POST['q'] is the search criteria
* @var $_POST['p'] is the page to load.
*
**/
if( ! validate_args( @$_POST['q'], @$_POST['p'] ) )
	_result( __('Request malformed.'), false );

$q = trim($_POST['q']);
/**
* @var string $s is the order of the search
* All the orders are descending
* d means date
* p means plays
* If it's not d or p, will be 'd' by default
**/
$s = validate_args( @$_POST['o'] )
	&& in_array($_POST['o'], array("d", "p") ) ?
		$_POST['o']
	:
		'd';
/**
* @var string $t is the type of the search
* u means users
* a means audios
**/
$t = validate_args( @$_GET['t'] )
	&& in_array($_GET['t'], array('u', 'a') ) ?
		$_GET['t']
	:
		'a';
$p = (int) $_POST['p'];
if( ! is_numeric($_POST['p']) || $p <= 1|| empty($q) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

search( $q, $s, $t, $p);
//     query,sort,type,page
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

$query = trim($_POST['q']);
/**
* All the orders are descending
* d means date
* p means plays
* If it's not d or p, will be 'd' by default
**/
$order = validate_args( @$_POST['o'] )
	&& in_array($_POST['o'], array("d", "p") ) ?
		$_POST['o']
	:
		'd';
/**
* u means users
* a means audios
**/
$type = validate_args( @$_GET['t'] )
	&& in_array($_GET['t'], array('u', 'a') ) ?
		$_GET['t']
	:
		'a';
$page = sanitize_pageNumber( $_POST['p'] );
if( $page <= 1|| empty($query) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

search( $query, $sort, $type, $page );
<?php
/**
*
* Will sign in the user
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright (c) 2015 - Luis A. Martínez
**/
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( !isset($_COOKIE['ta_session']) ) //need cookies
	ta_redirect( url() );

if( isset($_GET['back_to']) && is_string($_GET['back_to']) ):
	// this will replace every non alphabetic character
	// into :{character}
	// so ':' will be '\:'
	// and it's protected
	$url = preg_replace('/([^\w])/', '\\\$1', url() );
	// check it gets back to the site, no outside
	if( preg_match(
			'/^(' . $url . ')/',
			$bt = urldecode($_GET['back_to'])
			)
		)
		$_SESSION['back_to'] = $bt;
endif;

ta_redirect( $twitter->getURL() );
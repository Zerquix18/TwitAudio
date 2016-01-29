<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if( !isset($_COOKIE['ta_session']) )
	ta_redirect( url() );

if( isset($_GET['back_to']) && is_string($_GET['back_to']) ):
	$url = preg_replace('/([^\w])/', '\\\$1', url() );
	if( preg_match(
			'/^(' . $url . ')/',
			$bt = urldecode($_GET['back_to'])
			)
		)
		$_SESSION['back_to'] = $bt;
endif;

ta_redirect( $twitter->getURL() );
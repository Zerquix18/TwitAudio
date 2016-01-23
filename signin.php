<?php
require_once('load.php');

if( !isset($_COOKIE['ta_session']) )
	exit( __("You must accept cookies to sign in :(") );

ta_redirect( $twitter->getURL() );
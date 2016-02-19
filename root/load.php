<?php
/**
* Loader of the administration!
* @author Zerquix18
* @since 3/2/2016
**/
// get the whole system here
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
// must be logged in to check if it's id is an id of an admin
if( ! is_logged() )
	load_full_template('404') xor exit; // :c
$_CONFIG = json_decode( file_get_contents('c.json'), true );

require $_SERVER['DOCUMENT_ROOT'] . '/root/assets/functions.php';

if( ! adm_can_access() )
	load_full_template('404') xor exit; // fuck you
// require html/front-end functions
require $_SERVER['DOCUMENT_ROOT'] . '/root/assets/body.php';
// nothing else needed :)
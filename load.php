<?php
/**
* Loader file
* This file connects to the database and
* includes most of the files needed
* for this to work. :)
* @author Zerquix18
**/
//minify HTML:
 ob_start(function($b){return preg_replace(['/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s'],['>','<','\\1'],$b);});

/** database connection **/
require  $_SERVER['DOCUMENT_ROOT'] . '/assets/class.zerdb.php';
$db = new zerdb('localhost', 'root', 'TwitAudio123', 'audios');
if( ! $db->ready ) // todo: get this better
	exit("error code 1");

//get the system functions
require $_SERVER['DOCUMENT_ROOT'] . '/assets/functions.php';

/** session related **/
session_name('ta_session');
if( ! isset($_COOKIE['ta_session']) )
	session_id( generate_id(true) );
if( ! isset($_COOKIE['ta_session']) ||  
		preg_match(
			"/^(ta-)[\w]{29}+$/",
			$_COOKIE['ta_session']
		)
		// if cookie isn't valid,
		// PHP will throw a unavoidable warning
		// when calling session_start();
	) {
	session_start();
}
// imports is_logged() function
require $_SERVER['DOCUMENT_ROOT'] . '/assets/session.php';
// imports translation functions
require $_SERVER['DOCUMENT_ROOT'] . '/assets/i18n.php';
// imports html/front-end functions
require $_SERVER['DOCUMENT_ROOT'] . '/assets/body.php';
// imports Twitter's class (for api manipulation)
// todo: remove this shit from here and only
// require it when it's gonna be used
require $_SERVER['DOCUMENT_ROOT'] . '/assets/class.twitter.php';
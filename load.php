<?php
/** hai there! **/
ob_start(function($b){return preg_replace(['/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s'],['>','<','\\1'],$b);});
ob_implicit_flush( true );
define("PATH", dirname( __FILE__ ) . '/' );
define("INC", "assets/");
define("ASSETS", INC);
define("CSS", 'css/');
define("JS", 'js/' );
define("IMG", "img/");
define("TMP", "tmp/");
require  PATH . INC . 'class.zerdb.php';
$db = new zerdb('localhost', 'root', 'TwitAudio123', 'audios');
if( ! $db->ready )
	exit("error code 1");
require PATH . INC . 'functions.php';
session_name('ta_session');
if( ! isset($_COOKIE['ta_session']) )
	session_id( generate_id(true) );
if( isset($_COOKIE['ta_session']) && ! preg_match("/^(ta-)[\w]{29}+$/", $_COOKIE['ta_session']) )
	exit("nope.");
session_start();
require PATH . INC . 'session.php';
require PATH . INC . 'i18n.php';
require PATH . INC . 'body.php';
require PATH . INC . 'class.twitter.php';
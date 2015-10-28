<?php
/** hai there! **/
ob_start();
ob_implicit_flush( true );
define("PATH", dirname( __FILE__ ) . '/' );
define("INC", "assets/");
define("ASSETS", INC);
define("CSS", 'css/');
define("JS", 'js/' );
define("IMG", "img/");
define("TMP", "tmp/");
require_once( PATH . INC . 'class.zerdb.php');
$db = new zerdb('localhost', 'root', 'TwitAudio123', 'audios');
if( ! $db->ready )
	exit("error code 1");
require_once(PATH . INC . 'functions.php');
session_name('ta_session');
if( ! isset($_COOKIE['ta_session']) )
	session_id( generate_id(true) );
if( isset($_COOKIE['ta_session']) && ! preg_match("/^(ta-)[\w]{29}+$/", $_COOKIE['ta_session']) )
	exit("nope.");
session_start();
require_once(PATH . INC . 'session.php');
require_once(PATH . INC . 'i18n.php');
require_once(PATH . INC . 'class.audio.php');
require_once(PATH . INC . 'body.php');
require_once(PATH . INC . 'class.twitter.php');
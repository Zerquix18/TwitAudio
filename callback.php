<?php
require_once('load.php');
if( isset($_GET['denied']) )
	$_GET['denied'] === $_SESSION['oauth_token'] ?
		session_destroy() and exit( header("Location: process.php?denied=1") )
	:
		exit( header("Location: proccess.php?err=1") );
if( ! validate_args($_GET['oauth_token'], $_GET['oauth_verifier'], $_SESSION['oauth_token'], $_SESSION['oauth_token']) || $_SESSION['oauth_token'] !== $_GET['oauth_token'] )
	exit( header("Location: process.php?err=1") );
$twitter = new Twitter($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);
$access_token = $twitter->callback($_GET['oauth_verifier']);
if( 'error' === $access_token[0])
	exit( header("Location: process.php?err=1") );
// succeed
$_SESSION['access_token'] = $access_token['oauth_token'];
$_SESSION['access_token_secret'] = $access_token['oauth_token_secret'];
$_SESSION['id'] = $access_token['user_id'];
header("Location: process.php");
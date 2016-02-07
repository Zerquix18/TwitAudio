<?php
/**
* Twitter will redirect to this file after the authentication
* If it was accepted, Twitter will return the same oauth_token
* that was sent in the request and the oauth_verifier.
*
* If it was denied, Twitter will sent ?denied={ouath_token}
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright (c) 2015 Luis A. Martínez
*
**/
// get the loader with all the functions and classes
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
// if the request was denied
if( isset($_GET['denied']) ) {
	// if it's legit...
	if( $_GET['denied'] === $_SESSION['oauth_token'] ) {
		unset($_SESSION);
		session_destroy();
		ta_redirect('process.php?denied=1');
	}else // no legit
		ta_redirect('process.php?err=1');
}
if( ! validate_args(
		$_GET['oauth_token'],
		$_GET['oauth_verifier'],
		$_SESSION['oauth_token'],
		$_SESSION['oauth_token']
	)
	|| $_SESSION['oauth_token'] !== $_GET['oauth_token']
	)
	ta_redirect('process.php?err=1');
$twitter = new Twitter($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);
$access_token = $twitter->callback($_GET['oauth_verifier']);
if('error' === $access_token[0])
	ta_redirect('process.php?err=1');
// succeed
$_SESSION['access_token'] = $access_token['oauth_token'];
$_SESSION['access_token_secret'] = $access_token['oauth_token_secret'];
$_SESSION['id'] = $access_token['user_id'];
ta_redirect('process.php');
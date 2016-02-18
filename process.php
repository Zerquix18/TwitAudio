<?php
/**
* This file processes the data after a login
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright (c) 2015 Luis A. Martínez
* @todo Avoid multiple users with the same username
**/
// get everything we need
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
// Where will we redirect after login ?
if( isset($_SESSION['back_to']) ) {
	$redirect_to = $_SESSION['back_to'];
	unset($_SESSION['back_to']);
}else
	$redirect_to = url();
// if something failed
if( ($err = isset($_GET['err']) ) || ( $den = isset($_GET['denied']) )
	|| ! validate_args(
		$_SESSION['access_token'],
		$_SESSION['access_token_secret']
		)
	) {
	$_SESSION[ $den ? 'login_denied' : 'login_error' ] = true;
	ta_redirect( $redirect_to );
}
$twitter = new Twitter(
		$_SESSION['access_token'],
		$_SESSION['access_token_secret']
	);
/**
*
* Get the data from Twitter
* @link https://dev.twitter.com/rest/reference/get/account/verify_credentials
**/
$details = $twitter->tw->get('account/verify_credentials');
if( ! is_object($details) || array_key_exists('error', $details) ) {
	$_SESSION['login_error'] = true;
	ta_redirect( $redirect_to );
}
$user = $details->screen_name;
$name = $details->name;
$bio = $details->description;
$avatar = $details->profile_image_url_https;
$verified = (int) $details->verified;
$access_token = $_SESSION['access_token'];
$access_token_secret = $_SESSION['access_token_secret'];
// does the user exist?
$exists = $db->query("SELECT COUNT(*) AS size FROM users WHERE id = ?",$_SESSION['id']);
if( (int) $exists->size > 0 ) { // it already exists
	// re-update everythin'
	$r = $db->update("users", array(
			"user" => $user,
			"name" => $name,
			"avatar" => $avatar,
			"bio" => $bio,
			"verified" => $verified,
			"access_token" => $access_token,
			"access_token_secret" => $access_token_secret,
		) )->where('id', $_SESSION['id'])->_();
}else{
	// it does not exist
	$_SESSION['first_time'] = true;
	$favs_public =
	$audios_public = (int) ! $details->protected;
	$time = time();
	$lang = $details->lang;
	$db->insert("users", array(
			$_SESSION['id'],
			$user,
			$name,
			$avatar,
			$bio,
			$verified,
			$access_token,
			$access_token_secret,
			$favs_public,
			$audios_public,
			$time,
			$lang
		)
	);
}
unset($_SESSION['access_token']);
unset($_SESSION['access_token_secret']);
// insert the session
$db->delete("sessions")->where("sess_id", session_id() )->_();
$db->insert("sessions", array(
		$_SESSION['id'],
		session_id(),
		time(),
		getip(),
		'0'
	)
);
ta_redirect( $redirect_to );
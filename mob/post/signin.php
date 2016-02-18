<?php
/**
* Mobile API sign in file
* This file logs in the user
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright 2015 - Luis A. Martínez
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
if( 'POST' !== getenv('REQUEST_METHOD') )
	exit;
if( ! validate_args(
		@$_POST['access_token'],
		@$_POST['access_token_secret']
		)
	)
	result_error( __('Missing fields.'), 4);
$access_token = $_POST['access_token'];
$access_token_secret = $_POST['access_token_secret'];
$twitter = new Twitter($access_token, $access_token_secret);
$details = $twitter->tw->get('account/verify_credentials');
if( ! is_object($details) || array_key_exists('errors', $details) )
	result_error( __("Access Tokens are wrong."), 5);
$id = $details->id;
$user = $details->screen_name;
$name = $details->name;
$bio = $details->description;
$avatar = $details->profile_image_url_https;
$verified = (int) $details->verified;
$user = $db->query(
	"SELECT * FROM users WHERE id = ?",
	$id
);
if( 1 == $user->nums ) {
	// user exists
	$r = $db->update("users", array(
			"user" => $user,
			"name" => $name,
			"avatar" => $avatar,
			"bio" => $bio,
			"verified" => $verified,
			"access_token" => $access_token,
			"access_token_secret" => $access_token_secret,
		) )->where('id', $id)->_();
	$time = $user->time;
	$favs_public = $user->favs_public;
	$audios_public = $user->audios_public;
	$lang = $user->lang;
	$first_time = false;
}else{
	$first_time = true;
	$favs_public =
	$audios_public = (int) ! $details->protected;
	$time = time();
	$lang = $details->lang;
	$db->insert("users", array(
			$id,
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
$db->insert("sessions", array(
		$id,
		$sess_id = generate_id(true),
		$sess_time = time(),
		getip(),
		'1'
	)
);
$result = array(
		'id' 		=> (int) $id,
		'user' 		=> $user,
		'name' 		=> $name,
		'avatar' 	=> $avatar,
		'verified' 	=> (bool) $verified,
		'sess_id' 	=> $sess_id,
		'first_time' 	=> $first_time
	);
result_success(null, $result);
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
$s = $twitter->tw->get('account/verify_credentials');
if( ! is_object($s) || array_key_exists('errors', $s) )
	result_error( __("Access Tokens are wrong."), 5);
$id = $s->id;
$user = $s->screen_name;
$name = $s->name;
$bio = $s->description;
$avatar = $s->profile_image_url_https;
$verified = (int) $s->verified;
$exists = $db->query(
	"SELECT * FROM users WHERE id = ?",
	$id
);
if( $exists->nums ) {
	// user exists
	$r = $db->update("users", array(
			"user" => $user,
			"name" => $db->real_escape($name),
			"avatar" => $avatar,
			"bio" => $db->real_escape($bio),
			"verified" => $verified,
			"access_token" => $access_token,
			"access_token_secret" => $access_token_secret,
		) )->where('id', $id)->_();
	$time = $exists->time;
	$favs_public = $exists->favs_public;
	$audios_public = $exists->audios_public;
	$lang = $exists->lang;
	$first_time = false;
}else{
	$first_time = true;
	$favs_public =
	$audios_public = (int) ! $s->protected;
	$time = time();
	$lang = $s->lang;
	$db->insert("users", array(
			$id,
			$user,
			$db->real_escape($name),
			$avatar,
			$db->real_escape($bio),
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
		'id' 		=> (bool) $id,
		'user' 		=> $user,
		'name' 		=> $name,
		'avatar' 	=> $avatar,
		'verified' 	=> (bool) $verified,
		'sess_id' 	=> $sess_id,
		'first_time' 	=> $first_time
	);
result_success(null, $result);
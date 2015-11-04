<?php
require dirname(__FILE__) . '/load.php';
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
	"SELECT * FROM users WHERE id = ?", $id
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
	$likes_public = $exists->likes_public;
	$audios_public = $exists->audios_public;
	$lang = $exists->lang;
	$first_time = false;
}else{
	$first_time = true;
	$likes_public =
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
			$likes_public,
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
		getip()
	)
);
$db->update("sessions", array(
		"sess_key" => $sess_key = generate_sess_key()
	)
)->where('sess_id', $sess_id)->_();
$result = array(
		'id' 		=> $id,
		'user' 		=> $user,
		'name' 		=> $name,
		'bio' 		=> $bio,
		'avatar' 	=> $avatar,
		'verified' 	=> $verified,
		'likes_public' 	=> $likes_public,
		'audios_public' 	=> $audios_public,
		'time' 		=> $time,
		'lang' 		=> $lang,
		'sess_id' 	=> $sess_id,
		'sess_key' 	=> $sess_key,
		'sess_time' 	=> $sess_time,
		'first_time' 	=> $first_time
	);
result_success(null, $result);
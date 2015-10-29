<?php
require_once('load.php');
$error = __("There was an error while processing your data :(");
$denied =  __("You denied our request, so there's nothing we can do. :/");
$_BODY['page'] = __('Processing');
$_BODY['meta']['robots'] = false;
if( ($err = isset($_GET['err']) ) || ( $den = isset($_GET['denied']) ) || ! validate_args( $_SESSION['access_token'], $_SESSION['access_token_secret']) ) {
	$_BODY['error'] = $den ? $denied : $error;
	load_full_template('process');
	exit();
}
$twitter = new Twitter($_SESSION['access_token'], $_SESSION['access_token_secret']);
$s = $twitter->tw->get('account/verify_credentials');
if( ! is_object($s) )
	$_BODY['error'] = $error and exit( load_full_template('process') );
load_full_template('process');
$user = $s->screen_name;
$name = $s->name;
$bio = $s->description;
$avatar = $s->profile_image_url_https;
$verified = (int) $s->verified;
$access_token = $_SESSION['access_token'];
$access_token_secret = $_SESSION['access_token_secret'];
// does the user exist?
$exists = $db->query("SELECT COUNT(*) AS size FROM users WHERE id = ?", $_SESSION['id']);
if( (int) $exists->size > 0 ) { // it already exists
	// re-update everythin'
	$r = $db->update("users", array(
			"user" => $user,
			"name" => $db->real_escape($name),
			"avatar" => $avatar,
			"bio" => $db->real_escape($bio),
			"verified" => $verified,
			"access_token" => $access_token,
			"access_token_secret" => $access_token_secret,
		) )->where('id', $_SESSION['id'])->_();
}else{
	// it does not exist
	$_SESSION['first_time'] = true;
	$likes_public =
	$audios_public = (int) ! $s->protected;
	$time = time();
	$lang = $s->lang;
	$r = $db->insert("users", array(
			$_SESSION['id'],
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
unset($_SESSION['access_token']);
unset($_SESSION['access_token_secret']);
// insert the session
if( $r )
	$db->insert("sessions", array(
			$_SESSION['id'],
			session_id(),
			time(),
			getip()
		)
	);
header("Location: ". url() );
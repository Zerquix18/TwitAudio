<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';

if('POST' !== getenv('REQUEST_METHOD') )
	exit();

if( ! is_logged() )
	_result( __("Authentication required."), false);

if( ! validate_args( $_POST['id'], $_POST['description']) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( ! array_key_exists('s_twitter', $_POST) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( ! array_key_exists($_POST['id'], $_SESSION) )
	_result(
		__('There was an error while processing your request.'),
		false
	);

$id = $_POST['id'];

if( $_SESSION[$id]['duration'] > 120 )
	_result(
		__('There was an error while processing your request.'),
		false
	);

if( mb_strlen($_POST['description'], 'utf-8') > 200 )
	_result( __("The description can't be longer than 200 characters"), false );

$_POST['description'] = trim($_POST['description']);
extract_hashtags( $_POST['description'] );
// ok then

while( file_exists(
	PATH . INC . 'audios/' .
	$n = substr( md5( uniqid() . rand(1,100) ), 0, 26 ) . '.mp3'
	)
);

rename( $_SESSION[$id]['tmp_url'], PATH . INC . 'audios/' . $n);

$db->insert("audios", array(
		$a_id = generate_id(),
		$_USER->id,
		$n,
		0,
		$db->real_escape($_POST['description']),
		0,
		time(),
		0,
		0,
		(string) $_SESSION[$id]['duration'],
		(string) (int) $_SESSION[$id]['is_voice']
	)
);
unset($_SESSION[$id]);
if( $_POST['s_twitter'] === '1' ) {
	$tweet = 'https://twitaudio.com/'. $a_id;
	$len = strlen($tweet);
	$desc = $_POST['description'];
	if( strlen($desc) > (140-$len) )
		$desc = substr($desc, 0, 140-$len-4 ) . '...';
	$tweet = $desc . ' ' . $tweet;
	$x = $twitter->tweet($tweet);
	if( $x )
		$db->update("audios", array(
				"tw_id" => $x
			)
		)->where("id", $a_id)->_();
}
_result( __("Audio successfully posted!"), true);
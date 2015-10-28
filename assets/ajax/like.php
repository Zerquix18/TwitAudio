<?php
require_once('../../load.php');

('POST' !== getenv('REQUEST_METHOD') ) and exit();

! is_logged() and _result( __("Authentication required."), false);

! validate_args( @$_POST['id'] ) and _result( __('Request malformed.'), false );

$id = $_POST['id'];

! preg_match("/^[A-Za-z0-9]{6}$/", $id ) and _result( __('Request malformed.'), false );

// does audio exist ?

$exists_audio = $db->query("SELECT user FROM audios WHERE id = ?", $id);
if( ! (int) $exists_audio->nums )
	_result( __("The audio you tried to like doesn't exist."), false );
if( ! can_listen( $exists_audio->user ) )
	_result( __("You can't listen to this user's audios, so you can neither like them."), false );

// already liked?

$liked = $db->query("SELECT COUNT(*) AS size FROM likes WHERE audio_id = ? AND user_id = ?", $id, $_USER->id);

$liked = (int) $liked->size;

if( $liked ) {
	$db->query("UPDATE audios SET likes = likes-1 WHERE id = ?", $id);
	$db->query("DELETE FROM likes WHERE audio_id = ? AND user_id = ?", $id, $_USER->id);
}else{
	$db->query("UPDATE audios SET likes = likes+1 WHERE id = ?", $id);
	$db->insert("likes", array(
			$_USER->id,
			$id,
			time()
		)
	);
}
$extra = array(
		'action' => $liked ? 'dislike' : 'like',
		'count' => $liked ? $liked - 1 : $liked + 1
	);
_result(true, true, $extra);
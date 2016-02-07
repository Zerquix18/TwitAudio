<?php
/**
* Will return an array with the data of an user
* @param mixed $u
* @param bool $profile_extra
**/
function json_display_user_sm( $u, $profile_extra = false ) {
	global $db;
	if( is_array($u) )
		$u = (object) $u;
	elseif( ! is_object($u) )
		$u = $db->query('SELECT * FROM users WHERE id = ?', $u );
	$return = array(
			'user' 		=> $u->user,
			'name' 		=> $u->name,
			'avatar' 	=> $u->avatar,
			'verified' 	=> (bool) $u->verified
		);
	if( $profile_extra )
		$return += array(
				'bio'		=> $u->bio,
				'avatar_big' 	=> get_image($u->avatar)
			);
	return $return;
}
/**
* Will show the information of the array $a
* which is an audio
* @param object|array $a
* @param bool $profile_extra
**/
function json_display_audio( $a, $profile_extra = false ) {
	global $db, $_USER;
	if( is_object($a) )
		$a = (array) $a;
	$favorited = $db->query(
			'SELECT COUNT(*) AS size FROM favorites
			WHERE user_id = ? AND audio_id = ?',
			$_USER->id,
			$a['id']
		);
	if( $a['reply_to'] == '0' ):
		$replies_count = $db->query(
			'SELECT COUNT(*) AS size FROM audios
			WHERE reply_to = ?',
			$a['id']
		);
		$replies_count = (int) $replies_count->size;
	else:
		$replies_count = 0;
	endif;
	return array(
			'id' 		=> $a['id'],
			'user' 		=> // ↓
			json_display_user_sm($a['user'], $profile_extra),
			'audio' 		=> ! empty($a['audio'] ) ?
			'https://twitaudio.com/' . 'assets/audios/' . $a['audio'] : '',
			'description' 	=> $a['description'],
			'time' 		=> (int) $a['time'],
			'plays' 		=> (int) $a['plays'],
			'favorites' 	=> (int) $a['favorites'],
			'duration' 	=> (int) $a['duration'],
			'favorited' 	=> (bool) $favorited->size,
			'replies_count' => $replies_count,
		);
}
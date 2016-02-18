<?php
/**
* Will return an array with the data of an user
* @param mixed $u
* @param bool $profile_extra
**/
function json_display_user_sm( $user, $profile_extra = false ) {
	global $db;
	if( is_array($user) )
		$user = (object) $user;
	elseif( ! is_object($user) )
		$user = $db->query(
			'SELECT * FROM users WHERE id = ?',
			$user
		);
	$return = array(
			'user' 		=> $user->user,
			'name' 		=> $user->name,
			'avatar' 	=> $user->avatar,
			'verified' 	=> (bool) $user->verified
		);
	if( $profile_extra )
		$return += array(
				'bio'		=> $user->bio,
				'avatar_big' 	=> get_image($user->avatar)
			);
	return $return;
}
/**
* Will show the information of the array $a
* which is an audio
* @param object|array $a
* @param bool $profile_extra
**/
function json_display_audio( $audio, $profile_extra = false ) {
	global $db, $_USER;
	if( is_object($audio) )
		$audio = (array) $audio;
	$is_favorited = $db->query(
			'SELECT COUNT(*) AS size FROM favorites
			WHERE user_id = ? AND audio_id = ?',
			$_USER->id,
			$audio['id']
		);
	$is_favorited = (bool) $is_favorited->size;
	if( $audio['reply_to'] == '0' ):
		$replies_count = $db->query(
			'SELECT COUNT(*) AS size FROM audios
			WHERE reply_to = ?',
			$audio['id']
		);
		$replies_count = (int) $replies_count->size;
	else:
		$replies_count = 0;
	endif;
	return array(
			'id' 		=> $audio['id'],
			'user' 		=> // ↓
			json_display_user_sm($audio['user'], $profile_extra),
			'audio' 		=> ! empty($audio['audio'] ) ?
			'https://twitaudio.com/' . 'assets/audios/' .
					$audio['audio'] : '',
			'description' 	=> $audio['description'],
			'time' 		=> (int) $audio['time'],
			'plays' 		=> (int) $audio['plays'],
			'favorites' 	=> (int) $audio['favorites'],
			'duration' 	=> (int) $audio['duration'],
			'favorited' 	=> $is_favorited
			'replies_count' => $replies_count,
		);
}
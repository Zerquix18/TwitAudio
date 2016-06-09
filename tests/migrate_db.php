<?php
/**
 * File to migrate the database to a new and better database structure :)
 */
// make this file executable from anywhere
chdir( dirname(__FILE__) );
chdir('../');

require 'application/zerdb.php';

function _log( $what ) {
	echo $what, PHP_EOL;
}

// the original database
$o_db = new zerdb('localhost', 'root', 'zer1234', 'audios');
// new db
$n_db = new zerdb('localhost', 'root', 'zer1234', 'twitaudio');

if( ! $o_db ) {
	exit('Error connecting to original db: ' . $o_db->error);
}
if( ! $n_db ) {
	exit('Error connecting to new db: ' . $n_db->error);
}

_log('Moving users table...');
$o_users       = $o_db->query('SELECT * FROM users');
$o_users_count = $o_db->nums;
while( $user = $o_users->r->fetch_assoc() ) {
	// re-insert in the new 
	$query = $n_db->query(
			'INSERT INTO users
			 SET
			 	id                  = ?,
			 	username            = ?,
			 	name                = ?,
			 	bio                 = ?,
			 	avatar              = ?,
			 	access_token        = ?,
			 	access_token_secret = ?,
			 	is_verified         = ?,
			 	favs_privacy        = ?,
			 	audios_privacy      = ?,
			 	date_added          = ?
			',
			$user['id'],
			$user['user'],
			$user['name'],
			$user['bio'],
			$user['avatar'],
			$user['access_token'],
			$user['access_token_secret'],
			$user['verified'],
			!! $user['favs_public']   ? 'public' : 'private',
			!! $user['audios_public'] ? 'public' : 'private',
			(int) $user['time']
		);
	if( ! $query ) {
		exit('Could not move users: ' . $n_db->error);
	}
}

_log('Just moved ' . $o_users_count . ' users');


_log('Moving audios table...');
$o_audios       = $o_db->query('SELECT * FROM audios');
$o_audios_count = $o_audios->nums;
while( $audio = $o_audios->r->fetch_assoc() ) {
	$audio['description'] = addslashes($audio['description']);
	if( in_array( trim($audio['audio']), array('0', '') ) ) {
		$audio['audio'] = 'NULL';
	} else {
		$audio['audio'] = "'{$audio['audio']}'";
	}
	if( in_array( trim($audio['reply_to']), array('0', '') ) ) {
		$audio['reply_to'] = 'NULL';
	} else {
		$audio['reply_to'] = "'{$audio['reply_to']}'";
	}
	$query = $n_db->query(
			"INSERT INTO audios
				SET
					id          = '{$audio['id']}',
					user_id     = {$audio['user']},
					audio_url   = {$audio['audio']},
					reply_to    = {$audio['reply_to']},
					description = '{$audio['description']}',
					twitter_id  = '{$audio['tw_id']}',
					date_added  = {$audio['time']},
					plays       = {$audio['plays']},
					favorites   = {$audio['favorites']},
					duration    = {$audio['duration']},
					is_voice    = '{$audio['is_voice']}'
			"
		);
	if( ! $query ) {
		exit('Could not move audios: ' . $n_db->error);
	}
}
_log('Just moved ' . $o_audios_count . ' audios');

_log('Moving plays table...');
$o_plays       = $o_db->query('SELECT * FROM plays');
$o_plays_count = $o_plays->nums;

while( $play = $o_plays->r->fetch_assoc() ) {
	// some ips where wrong idk why
	if( ! filter_var($play['user_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ){		continue;
	}
	$query = $n_db->query(
			"INSERT INTO plays
				SET
					user_ip    = INET_ATON('{$play['user_ip']}'),
					audio_id   = '{$play['audio_id']}',
					date_added = {$play['time']}
			"
		);
	if( ! $query ) {
		exit('Could not move plays: ' . $n_db->error);
	}
}
_log('Just moved ' . $o_plays_count . ' plays');

_log('Moving favorites table...');
$o_favorites       = $o_db->query('SELECT * FROM favorites');
$o_favorites_count = $o_favorites->nums;

while( $favorite = $o_favorites->r->fetch_assoc() ) {
	$query = $n_db->query(
			"INSERT INTO favorites
				SET
					user_id    =  {$favorite['user_id']},
					audio_id   = '{$favorite['audio_id']}',
					date_added =  {$favorite['time']}
			"
		);
	if( ! $query ) {
		exit('Could not move favorites: ' . $n_db->error);
	}
}
_log('Just moved ' . $o_favorites_count . ' favorites');

$o_db->query("DELETE FROM sessions WHERE user_id = '0'");
_log('Moving sessions table...');
$o_sessions       = $o_db->query('SELECT * FROM sessions');
$o_sessions_count = $o_sessions->nums;

while( $session = $o_sessions->r->fetch_assoc() ) {
	$query = $n_db->query(
			"INSERT INTO sessions
				SET
					id         = '{$session['sess_id']}',
					user_id    = {$session['user_id']},
					user_ip    = INET_ATON('{$session['ip']}'),
					date_added = {$session['time']},
					is_mobile  = '{$session['is_mobile']}'
			"
		);
	if( ! $query ) {
		exit('Could not move sessions: ' . $n_db->error);
	}
}
_log('Just moved ' . $o_sessions_count . ' sessions');

_log('Moving following_cache table...');
$o_following       = $o_db->query('SELECT * FROM following_cache');
$o_following_count = $o_following->nums;

while( $following = $o_following->r->fetch_assoc() ) {
	$query = $n_db->query(
			"INSERT INTO following_cache
				SET
					user_id    = {$following['user_id']},
					following  = {$following['following']},
					date_added = {$following['time']},
					result     = '{$following['result']}'
			"
		);
	if( ! $query ) {
		exit('Could not move followings: ' . $n_db->error);
	}
}
_log('Just moved ' . $o_following_count . ' followings');

_log('Moving payments table...');
$o_playments       = $o_db->query('SELECT * FROM payments');
$o_playments_count = $o_playments->nums;

while( $payment = $o_playments->r->fetch_assoc() ) {
	$query = $n_db->query(
			"INSERT INTO payments
				SET
					id         = {$payment['id']},
					user_id    = {$payment['user_id']},
					method     = '{$payment['method']}',
					user_agent = '{$payment['user_agent']}',
					user_ip    = INET_ATON('{$payment['ip']}'),
					date_added = {$payment['time']}
			"
		);
	if( ! $query ) {
		exit('Could not move followings: ' . $n_db->error);
	}
}
_log('Just moved ' . $o_playments_count . ' payments');
<?php
/**
*
* Functions file
*
* @author Zerquix18
* @copyright Copyright (c) 2015 - Luis A. Martínez
**/
/**
* Returns the URL
* @return string
**/
function url() {
	if('localhost' == $_SERVER['HTTP_HOST'])
		return 'http://localhost/TwitAudio/'; //:)
	return 'https://twitaudio.com/';
}
/**
* All the arguments passed by this function
* MUST be existing variables and must be strings
* This is to avoid the typical lol[]='xd' in get/post methods
**/
function validate_args() {
	$args = func_get_args();
	foreach($args as $a)
		if( ! isset($a) || !is_string($a) )
			return false;
	return true;
}
/**
* Will return an ID for a session or an audio
* This ID is unique. It doesn't exist in the database
* @param $session bool (true for sessions, false for audios)
* @return string
**/
function generate_id($session=false) {
	global $db;
	$chars = 
	'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890';
	$table = $session ? 'sessions' : 'audios';
	$wh = $session ? 'sess_id' : 'id';
	while( // check if exists
		($x = (
			$db->query(
				"SELECT COUNT(*) AS size FROM $table
				 WHERE $wh = ?", 
				$id = $session ?
				'ta-' . substr( str_shuffle($chars), 0, 29)
				:
				substr( str_shuffle($chars), 0, 6)
			)
		) )&& $x->size > 0
	);
	return $id;
}
/**
* Tries to the real user IP (even if it's using proxies)
* @return string 
**/
function getip() {
	if( ! empty($_SERVER['HTTP_CLIENT_IP']) )
		return $_SERVER['HTTP_CLIENT_IP'];
	if( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	return $_SERVER['REMOTE_ADDR'];
}
/**
* Will return the differences between $time and the current time
* @todo just use javascript and delete this shit
* @param string $time (the old time)
* @return string
**/
function d_diff( $time ) {
	$n = new Datetime('@'.$time);
	$f = new DateTime();
	$diff = $f->diff($n);
	$diff->w = floor( $diff->days / 7 );
	if( $diff->w > 4 )
		return date('d/m/Y', $time);
	if( $diff->w >= 1)
		return sprintf( $diff->w == 1 ?
			__('%d week')
		:
			__('%d weeks')
		, $diff->w);
	if( $diff->d >= 1 )
		return sprintf( $diff->d == 1 ?
				__('%d day')
			:
				__('%d days')
			, $diff->d);
	if( $diff->h >= 1 )
		return sprintf( $diff->h == 1 ?
				__('%d hour')
			:
				__('%d hours')
			, $diff->h);
	if( $diff->i >= 1 )
		return sprintf( $diff->i == 1 ?
				__('%d min')
			:
				__('%d mins')
			, $diff->i);
	if( $diff->s >= 1 )
		return sprintf( $diff->s == 1 ?
				__('%d second')
			:
				__('%d seconds')
			, $diff->s);
	return __('now');
}
/**
* Will exit a JSON code in AJAX requests
* @param mixed $response
* @param bool success
* @param array $extra
* @return void
**/
function _result( $response, $success, $extra = null ) {
	$arr = array(
			'response' => $response,
			'success' => $success
		);
	$arr = is_array($extra) ?
		array_merge($arr, array('extra' => $extra) )
	:
		$arr;
	exit( json_encode($arr) );
}
/**
* will extract 3 hashtags and insert them
* @deprecated
**/
function extract_hashtags($text) {
	global $db, $_USER;
	preg_match_all('~([#])([^\s#!\"\$\%&\'\(\)\*\+\,\-./\:\;\<\=\>?\[/\/\/\\]\^\`\{\|\}\~]+)~', $text, $matches );
	if( ! count($matches[2]) ) // no hashtag, return nothin
		return;
	// no repeated
	$trendings = array_unique($matches[2]);
	// no abuse
	$trendings = array_slice($trendings, 0, 3);
	$query = "INSERT INTO trends (`" .
		implode("`,`", $db->tablas['trends']) .
		"`) VALUES ";
	$time = time();
	$count = count($trendings);
	if( $count === 1 )
		$query .= "('$_USER->id', '$trend', '$time')";
	else {
		$last = $count-1; // last one 
		for($i=0; $i < $count; $i++) {
			$query .= "('$_USER->id', '$trendings[$i]', '$time')";
			if( $i != $last )
				$query .= ','; // no commas for the last one
		}
	}
	$db->query($query);
}
/**
* Will return the last value of an array
* @param array $arr
* @return mixed
**/
function last( array $arr ) {
	# PHP script standards
	# only variables per reference
	return end($arr);
}
/**
* Checks if the current page is one of the sent
* in the params
* @return bool
**/
function is() {
	global $_BODY;
	return in_array( $_BODY['page'], func_get_args() );
}
/**
* Checks if the logged user can listen to
* @id2's audios
* @param string $id2
* @return bool
**/
function can_listen( $id2 ) {
	global $db, $twitter, $_USER;
	$l = $_USER !== NULL; // check if logged in
	if( $l && $_USER->id == $id2 ) // same user
		return true;
	// check if audios of $id2 are private.
	$c = $db->query(
		"SELECT audios_public FROM users WHERE id = ?",
		$id2
	);
	if( $c->nums > 0 && $c->audios_public == '1' )
		return true; // they're public.
	if( ! $l )
		return false; // not logged and audios aren't public.
	// not public. check if cached ...
	$db->query( // cleans
		"DELETE FROM following_cache WHERE time < " .
		time() - 1800 // (60*30) half hour
	);
	$x = $db->query("SELECT result FROM following_cache
		WHERE user_id = ? AND following = ?",
		$_USER->id,
		$id2
	);
	if( $x->nums > 0 )
		return (bool) $x->result;
	// not cached, make twitter requests
	$g = $twitter->tw->get(
		'friendships/lookup',
		array('user_id' => $id2)
	);
	if( array_key_exists('errors', $g ) ) {
		// API rate limit reached :( try another
		$t = $twitter->tw->get(
			'users/lookup',
			array('user_id' => $id2)
		);
		if( array_key_exists('errors', $t )
		|| array_key_exists('error', $t)
			)
			return false; // both limits reached... ):
		$check = array_key_exists('following', $t[0]) && $t[0]->following;
	}else
		$check = in_array('following', $g[0]->connections);
	$db->insert("following_cache", array(
			$_USER->id,
			$id2,
			time(),
			(string) (int) $check // result
		)
	);
	return $check;
}
/**
* Sanitizes a text
* Prevents XSS, replaces links for clickable links
* and replaces @mentions by clickable links
* @param string $str (wow)
* @return string
**/
function sanitize( $str ) {
	if( mb_strlen( $str, 'utf8' ) < 1 )
		return '';
	$str = htmlspecialchars( $str, ENT_QUOTES, 'utf-8' );
	$str = str_replace( array( chr( 10 ), chr( 13 ) ), '' , $str );
	$str = preg_replace(
		'/https?:\/\/[\w\-\.!~#?&=+%;:\*\'"(),\/]+/u',
		'<a href="$0" target="_blank" rel="nofollow">$0</a>',
		$str
	);
    	$str = preg_replace_callback(
    		'~([@])([^\s#@!\"\$\%&\'\(\)\*\+\,\-./\:\;\<\=\>?\[/\/\/\\]\^\`\{\|\}\~]+)~',
    		function($m) {
    			$dir = $m[1] == "#" ? "search/?q=%23" : "";
    			return '<a href="' . url() . $dir . $m[2] . '">' . $m[0] . '</a>';
    		},
       	$str );
	return $str;
}
/**
* The name says it all
* @param int $count
* @return string
**/
function format_number( $count ) {
	$count = (int) $count; // just in case
	if( $count >= 1000 &&  $count < 1000000 ) 
		return number_format( $count/1000, 1 ) . 'k';
	elseif( $count >= 1000000 ) 
		return number_format( $count/1000000, 1 ) . "m"; 
	else
        		return $count;
}
/**
* Redirects to $url
* @param string $url
* @param bool $status
* @return void
**/
function ta_redirect( $url, $status = 302 ) {
	header('Location: ' . $url, true, $status);
	exit;
}
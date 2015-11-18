<?php
/**
*
* Funciones para el sistema
*
* @author Zerquix18
**/
function url() {
	global $_USER;
	if('localhost' == $_SERVER['HTTP_HOST'])
		return 'http://localhost/TwitAudio/';
	return 'https://twitaudio.com/';
}
function validate_args() {
	$args = func_get_args();
	foreach($args as $a)
		if( ! isset($a) || !is_string($a) )
			return false;
	return true;
}
// session = true will generate an ID for a session
// false = will generate an id for an audio
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
function getip() {
	if( ! empty($_SERVER['HTTP_CLIENT_IP']) )
		return $_SERVER['HTTP_CLIENT_IP'];
	if( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	return $_SERVER['REMOTE_ADDR'];
}
function d_diff( $time ) {
	$n = new Datetime('@'.$time);
	$f = new DateTime();
	$diff = $f->diff($n);
	$diff->w = round( $diff->days / 7 );
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
function last( array $arr ) {
	return end($arr);
}
function is() {
	global $_BODY;
	return in_array( $_BODY['page'], func_get_args() );
}
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
function sanitize( $str ) {
	if( mb_strlen( $str, 'utf8' ) < 1 )
		return '';
	$str = htmlspecialchars( $str );
	$str = str_replace( array( chr( 10 ), chr( 13 ) ), '' , $str );
	$str = preg_replace(
		'/https?:\/\/[\w\-\.!~#?&=+%;:\*\'"(),\/]+/u',
		'<a href="$0" target="_blank" rel="nofollow">$0</a>',
		$str
	);
    	$str = preg_replace_callback(
    		'~([#@])([^\s#@!\"\$\%&\'\(\)\*\+\,\-./\:\;\<\=\>?\[/\/\/\\]\^\`\{\|\}\~]+)~',
    		function($m) {
    			$dir = $m[1] == "#" ? "search/?q=%23" : "";
    			return '<a href="' . url() . $dir . $m[2] . '">' . $m[0] . '</a>';
    		},
       	$str );
	return $str;
}
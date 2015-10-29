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
		$x = (
			$db->query(
				"SELECT COUNT(*) AS size FROM $table
				 WHERE $wh = ?", 
				$id = $session ?
				'ta-' . substr( str_shuffle($chars), 0, 29)
				:
				substr( str_shuffle($chars), 0, 6)
			)
		) && $x->size > 0
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
function alert_error($error, $centered = false) {
	$centered = $centered ? 'center' : '';
	echo '<div class="alert error '.$centered.'">'. $error . '</div>';
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
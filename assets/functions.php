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
		return 'http://localhost/a/';
	return 'https://twitaudio.com/';
}
function validate_args() {
	$args = func_get_args();
	foreach($args as $a)
		if( ! isset($a) || !is_string($a) )
			return false;
	return true;
}
function redirect($a_sitio = false, $segundos = 2) {
	if( ! $a_sitio )
		return false;
	
	return '<meta http-equiv="refresh" content="' . $segundos . ';url=' . $a_sitio . '">';
}
function _sleep( $segundos = 2 ) {
	ob_end_flush();
	flush();
	ob_flush();
	@sleep($segundos);
	ob_start();
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
			$db->query("SELECT COUNT(*) AS size FROM $table WHERE $wh = ?", 
				$id = $session ? 'ta-' . substr( str_shuffle($chars), 0, 29) : substr( str_shuffle($chars), 0, 6) )
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
	$arr = is_array($extra) ? array_merge($arr, array('extra' => $extra) ) : $arr;
	exit( json_encode($arr) );
}
function extract_hashtags($text) {
	global $db, $_USER;
	preg_match_all('~([#])([^\s#!\"\$\%&\'\(\)\*\+\,\-./\:\;\<\=\>?\[/\/\/\\]\^\`\{\|\}\~]+)~', $text, $matches );
	foreach($matches[2] as $trend)
		$db->insert("trends", array(
				$_USER->id,
				$trend,
				time()
			)
		);
}
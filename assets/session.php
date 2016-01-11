<?php
function _is_logged() {
	global $db;
	if( ! isset($_COOKIE['ta_session']) )
		return 0;
	$x = $db->query(
		"SELECT user_id FROM sessions WHERE sess_id = ? AND sess_key = ''",
		session_id() // protected by regex
	);
	return $x->nums > 0 ? (int) $x->user_id : 0;
}
$just_1_query = _is_logged();
function is_logged() {
	return $GLOBALS['just_1_query']; // such a pro, thats me
}
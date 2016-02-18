<?php
/**
* Manage sessions
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
**/
function _is_logged() {
	global $db;
	if( ! isset($_COOKIE['ta_session']) )
		return 0;
	$session = $db->query(
		"SELECT user_id FROM sessions WHERE sess_id = ? AND is_mobile = '0'",
		session_id()
	);
	return $session->nums > 0 ? (int) $session->user_id : 0;
}
$just_1_query = _is_logged();
/**
* Checks if user is logged in
* @see _is_logged()
* @return bool
**/
function is_logged() {
	return $GLOBALS['just_1_query']; // such a pro, thats me
}
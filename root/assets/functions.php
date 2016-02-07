<?php
/**
* Functions for the administration
*
**/
/**
*
* @return bool
*
**/
function adm_can_access() {
	global $_CONFIG, $_USER;
	return in_array(
			(int) $_USER->id,
			$_CONFIG['admins']
		);
}
/**
* Checks if the current page is one of the sent
* in the params
* @return bool
**/
function adm_is() {
	global $_ADM_BODY;
	return in_array( $_ADM_BODY['page'], func_get_args() );
}
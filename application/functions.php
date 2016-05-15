<?php
/**
* Functions file
*
**/
/**
* Formats a number, making it smaller and easy to read.
* @param $count - string|int
* @return string
**/
function format_number( $count ) {
	$count = (int) $count; // just in case
	if( $count >= 1000 &&  $count < 1000000 ) {
		return number_format($count / 1000, 1) . 'k';
	} elseif( $count >= 1000000 ) {
		return number_format($count / 1000000, 1) . "m";
	}
	return (string) $count;
}
/**
* Generates an non-existent ID for the given param $for
* @param $for string
* @return string
**/
function generate_id( $for ) {
	$chars = 
	'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890';
	if( ! in_array($for, array('session', 'audio') ) ) {
		return trigger_error(
			"generate_id only accepts 'session' and 'audio'"
		);
	}
	$table  = 'session' == $for ? 'sessions' : 'audios';
	$column = 'session' == $for ? 'sess_id'  : 'id';
	// check out this amazing hack!
	while(
		($check = (
			db()->query(
				"SELECT COUNT(*) AS size FROM $table
				 WHERE $column = ?", 
				$id = 'session' == $for ?
				'ta-' . substr( str_shuffle($chars), 0, 29)
				:
				substr( str_shuffle($chars), 0, 6)
			)
		) ) && $check->size > 0
	);
	return $id;
}
/**
* Returns the URL of the website, with HTTP and the final slash
* If the param $path is passed, then it will return
* the url + the path
*
* @param $path string
* @return string
**/
function url( $path = '' ) {
	return \Config::get('url') . $path;
}
/**
* Returns the avatar resized
* based on $link.
* @param $link string
* @param $size string
**/
function get_avatar( $link, $size = '' ) {
	$hola = explode(".", $link);
	$format = end($hola);
	$hola = explode("_", $link);
	array_pop($hola);
	$link = implode("_", $hola);
	if( $size == 'bigger' ) {
		return $link . '_bigger.'. $format;
	} elseif( $size == '' ) {
		return $link . '.' . $format;
	}
	return $link . '_normal.' . $format;
}
/**
* Returns the differences between $old_time
* and NOW.
* @param $old_time string
* @return string
*
**/
function get_date_differences( $old_time ) {
	$old_date = new \Datetime('@' . $old_time);
	$current_date = new \DateTime();
	$diff = $current_date->diff($old_date);
	$diff->w = floor( $diff->days / 7 );
	if( $diff->w > 4 ) {
		return date('d/m/Y', $old_time);
	}
	if( $diff->w >= 1) {
		return sprintf( $diff->w == 1 ?
				'%d week'
			:
				'%d weeks',
			$diff->w
		);
	}
	if( $diff->d >= 1 ) {
		return sprintf( $diff->d == 1 ?
				'%d day'
			:
				'%d days'
			, $diff->d);
	}
	if( $diff->h >= 1 ) {
		return sprintf( $diff->h == 1 ?
				'%d hour'
			:
				'%d hours'
			, $diff->h);
	}
	if( $diff->i >= 1 ) {
		return sprintf( $diff->i == 1 ?
				'%d min'
			:
				'%d mins'
			, $diff->i);
	}
	if( $diff->s >= 1 ) {
		return sprintf( $diff->s == 1 ?
				'%d second'
			:
				'%d seconds'
			, $diff->s);
	}
	return 'now';
}
/**
* Returns the current IP address of the user.
* @return string
**/
function get_ip() {
	if( ! empty($_SERVER['HTTP_CLIENT_IP']) ) {
		return $_SERVER['HTTP_CLIENT_IP'];
	}

	if( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	
	return $_SERVER['REMOTE_ADDR'];
}
/**
* PHP "strict standards" does not let me to do this
* end( explode( something ) )
* so I do last( explode( something) )
**/
function last( array $array ) {
	return end($array);
}
/**
* Returns a bool if we are being called
* from the mobile API or not.
* @return bool
**/
function is_mobile() {
	return 'mob' === substr( $_SERVER['REQUEST_URI'], 1, 3);
}
/**
* Returns the database global variable
* That variable has the zerdb class
* to perform queries.
**/
function db() {
	global $db;
	return $db;
}
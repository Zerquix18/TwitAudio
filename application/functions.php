<?php
/**
* Functions file
*
**/
/**
 * Formats a number, making it smaller and easy to read.
 * @param  string|int $count
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
 * Generates an non-existent ID in the database
 * @param  string $for Must be audio|session
 * @return string the ID :)
**/
function generate_id( $for ) {
	$chars = 
	'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890';
	if( ! in_array($for, array('session', 'audio') ) ) {
		trigger_error(
			"generate_id only accepts 'session' and 'audio'"
		);
		return '';
	}
	$table  = 'session' == $for ? 'sessions' : 'audios';
	$column = 'session' == $for ? 'sess_id'  : 'id';
	$id     = '';
	while( true ) {
		if( 'session' == $for ) {
			$id = 'ta-' . substr( str_shuffle($chars), 0, 29);
		} else {
			$id = substr( str_shuffle($chars), 0, 6);
		}
		$result = db()->query(
				"SELECT COUNT(*) AS size FROM {$table}
				 WHERE {$column} = '{$id}'"
			);
		if( ! $result ) {
			throw new \Exception('SELECT error: ' . db()->error);
		}
		if( 0 === (int) $result->size )
			break;
	}
	return $id;
}
/**
* Returns the URL of the website, with HTTP and the final slash
*
* @param  $path string If it's passed, then it's appended to the URL
* @return string
**/
function url( $path = '' ) {
	return \Config::get('url') . $path;
}
/**
* Returns the avatar resized
* based on $link.
* @param $link string The Twitter URL of the avatar.
* @param $size string bigger or empty.
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
 * end( explode( '.', $something ) )
 * so I do last( explode( '.', $something) )
 * FUCK THE POLICE
 * @param  array $array
 * @return mixed
**/
function last( array $array ) {
	return end($array);
}
/**
* Checks if the request was done to the mobile API
* @return bool
**/
function is_mobile() {
	return 'mob' === substr( $_SERVER['REQUEST_URI'], 1, 3);
}
/**
* Returns the database global variable
* That variable has the zerdb class
* to perform queries.
* @return object
**/
function db() {
	global $db;
	return $db;
}
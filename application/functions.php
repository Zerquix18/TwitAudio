<?php
/**
* Functions file
*
**/
function format_number( $count ) {
	$count = (int) $count; // just in case
	if( $count >= 1000 &&  $count < 1000000 ) {
		return number_format( $count/1000, 1 ) . 'k';
	} elseif( $count >= 1000000 ) {
		return number_format( $count/1000000, 1 ) . "m";
	}
	return $count;
}

function generate_id_for( $for ) {
	global $db, $_CONFIG;
	$chars = 
	'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890';
	try {
		if( ! in_array($for, array('session', 'audio') ) ) {
			throw new \Exception(
				"generate_id_for only accepts 'session' and 'audio'"
			);
		}
		$table = 'session' == $for ? 'sessions' : 'audios';
		$column = 'session' == $for ? 'sess_id' : 'id';
		while( // check if exists
			($check = (
				$db->query(
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
	} catch (\Exception $e ) {
		if( $_CONFIG['display_errors'] ) {
			exit( $e->getMessage() );
		}
	}
}
function url( $path = '' ) {
	global $_CONFIG; //defined in config.ini
	return $_CONFIG['url'] . $path;
}
function get_avatar( $link, $size = '' ) {
	$hola = explode(".", $link);
	$format = end($hola);
	$hola = explode("_", $link);
	array_pop($hola);
	$link = implode("_", $hola);
	if( $size == 'bigger' )
		return $link . '_bigger.'. $format;
	elseif($size == '')
		return $link . '.' . $format;
	else
		return $link . '_normal.' . $format;
}
function date_differences( $oldtime ) {
	$old_date = new \Datetime('@'.$oldtime);
	$current_date = new \DateTime();
	$diff = $current_date->diff($old_date);
	$diff->w = floor( $diff->days / 7 );
	if( $diff->w > 4 )
		return date('d/m/Y', $oldtime);
	if( $diff->w >= 1) {
		return sprintf( $diff->w == 1 ?
				'%d week'
			:
				'%d weeks',
			$diff->w
		);
	}
	if( $diff->d >= 1 )
		return sprintf( $diff->d == 1 ?
				'%d day'
			:
				'%d days'
			, $diff->d);
	if( $diff->h >= 1 )
		return sprintf( $diff->h == 1 ?
				'%d hour'
			:
				'%d hours'
			, $diff->h);
	if( $diff->i >= 1 )
		return sprintf( $diff->i == 1 ?
				'%d min'
			:
				'%d mins'
			, $diff->i);
	if( $diff->s >= 1 )
		return sprintf( $diff->s == 1 ?
				'%d second'
			:
				'%d seconds'
			, $diff->s);
	return 'now';
}
function get_ip() {
	if( ! empty($_SERVER['HTTP_CLIENT_IP']) ) {
		return $_SERVER['HTTP_CLIENT_IP'];
	}

	if( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	
	return $_SERVER['REMOTE_ADDR'];
}
function last( array $array ) {
	return end($array);
}
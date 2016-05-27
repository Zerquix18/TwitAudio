<?php
/**
* Home File
* This file loads and routes everythin'!
*
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/

/** configuration **/
$config_file = dirname(__FILE__) . '/config.ini';
try {
	if( ! is_readable($config_file) ) {
		throw new \Exception("Can't read $config_file or it does not exist");
	}

	$_CONFIG = parse_ini_file($config_file);
	$_SERVER['DOCUMENT_ROOT'] = $_CONFIG['document_root'];
	require dirname(__FILE__) . '/application/Config.php';
} catch (\Exception $e ) {

	if( 'www.twitaudio.com' === $_SERVER['HTTP_HOST'] ) {
		exit('Something terrible happened.');
	}

	exit( $e->getMessage() );
}
// now we call \Config::get()

if( \Config::get('is_production') ) {
	error_reporting(0);
} else {
	error_reporting(E_ALL);
}
/** database connection **/
require $_SERVER['DOCUMENT_ROOT'] . '/application/zerdb.php';
try {
	$db = new zerdb(
		\Config::get('host'),
		\Config::get('user'),
		\Config::get('password'),
		\Config::get('database')
	);
	if( ! $db->ready ) {
		throw new \Exception( $db->error );
	}

} catch( \Exception $e ) {
	if( ! \Config::get('is_production') ) {
		echo $e->getMessage();
	} else {
		exit( file_get_contents('assets/templates/error-500.html') );
	}
}


/** vendor autoloader **/

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

/** twitaudio dependencies **/
require $_SERVER['DOCUMENT_ROOT'] . '/application/functions.php';
require $_SERVER['DOCUMENT_ROOT'] . '/application/sessions.php';

ob_start('minify_html');

spl_autoload_register( function ( $name ) {
	$file = str_replace('\\', '/', $name);
	$file = $_SERVER['DOCUMENT_ROOT'] . '/' . $file . '.php';
	if( is_readable( $file ) ) {
		require $file;
	}
});

$_USER = ( $id = is_logged() ) ?
		$db->query(
				'SELECT * FROM users WHERE id = ?',
				$id
			)
	:
		NULL;

require $_SERVER['DOCUMENT_ROOT'] . '/application/router.php';
// :)
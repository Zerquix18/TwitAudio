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
$config_file = './config.ini';
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
	/** minify HTML **/
	ob_start( function($output) {
		return preg_replace(
			['/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s'],
			['>','<','\\1'],
			$output
		);
	});
} else {
	error_reporting(E_ALL);
	ob_start();
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
	if( \Config::get('is_production') ) {
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

<<<<<<< Updated upstream
require $_SERVER['DOCUMENT_ROOT'] . '/application/router.php';
// :)
=======
/** Now JUST DO IT! **/

$router = new AltoRouter();
$router->setBasePath( $_CONFIG['base_path'] );
$router->addMatchTypes( array(
		'valid_username' 	=> '([\w]{2,15})',
		'valid_audio_id'	=> '([A-Za-z0-9]{6})',
	)
);

/** Map common pages **/

$router->map('GET', '/[signin|callback:login]', function( $login ) {
	return new \controllers\LoginController( $login );
});

$router->map('GET|POST|DELETE|PUT', '/', function() {
	return new \controllers\FrontController();
});

$router->map(
		'GET', '/[about|terms|privacy|faq|licensing:page]', function( $page ) {
			return new \controllers\TextPagesController( $page );
		}
	);

$router->map(
		'GET',
		'/[audios|favorites:profile_page]/[valid_username:user]',
		function( $profile_page, $user ) {
			return new \controllers\ProfileController(
						$profile_page,
						$user
					);
		}
	);

$router->map('GET', '/search', function() {
	return new \controllers\SearchController();
});

/**
*    ^ That guy must be above 
*because if it's not the guy below will catch it
**/

$router->map('GET', '/[valid_audio_id:audio_id]', function( $audio_id ) {
	return new \controllers\AudioController( $audio_id );
});

$router->map('GET', '/frame/[valid_audio_id:audio_id]', function( $id ) {
	return new \controllers\FrameController( $id );
});

/** Route AJAX & Mobile requests **/
$router->map(
		'GET|POST',
		'/[ajax|mob:via]/[get|post:method]/[a:action]',
		function( $via, $method, $action ) {
			return new \controllers\MobileAJAXController(
					$via, $method, $action
				);
		}
	);

$router->map('GET', '/re-update-943', function() {
	// accept the param branch if it's in beta
	if( 'beta.twitaudio.com' === $_SERVER['HTTP_HOST'] ) {
		$branch = ' ' . \application\HTTP::get('branch');
	} else {
		$branch = '';
	}
	exec('./re-update.sh' . $branch, $output);
	echo implode("\n", $output);
});

/**
* For testing purposes
**/
$router->map('GET', 'tests/[a:file]', function( $file ) {
	require $file;
});

/** end routes, match em'! **/

$match = $router->match();

if( $match && is_callable( $match['target'] ) ) {
	call_user_func_array( $match['target'], $match['params'] );
} else {
	\application\View::load_full_template('404');
}
>>>>>>> Stashed changes

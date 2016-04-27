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
	if( ! is_readable($config_file) )
		throw new \Exception("Can't read $config_file or it does not exist");

	$_CONFIG = parse_ini_file('config.ini');
	$_SERVER['DOCUMENT_ROOT'] = $_CONFIG['document_root'];

} catch (\Exception $e ) {

	if( 'www.twitaudio.com' === $_SERVER['HTTP_HOST'] )
		exit('Something terrible happened.');

	exit( $e->getMessage() );
}

if( '1' == $_CONFIG['display_errors'] )
	error_reporting(E_ALL);
else
	error_reporting(0);

if( '1' == $_CONFIG['minify_html'] )
	ob_start( function($output) {
		return preg_replace(
			['/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s'],
			['>','<','\\1'],
			$output
		);
	});
else
	ob_start();

/** database connection **/
require $_SERVER['DOCUMENT_ROOT'] . '/application/zerdb.php';
try {
	$db = new zerdb(
		$_CONFIG['host'],
		$_CONFIG['user'],
		$_CONFIG['password'],
		$_CONFIG['database']
	);
	if( ! $db->ready )
		throw new \Exception( $db->error );

} catch( \Exception $e ) {
	if( $_CONFIG['display_errors'] )
		echo $e->getMessage();
	else
		exit( file_get_contents('assets/templates/error-500.html') );
}

/** vendor autoloader **/

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

/** twitaudio dependencies **/
require $_SERVER['DOCUMENT_ROOT'] . '/application/functions.php';
require $_SERVER['DOCUMENT_ROOT'] . '/application/sessions.php';
require $_SERVER['DOCUMENT_ROOT'] . '/application/i18n.php';

$_USER = ( $id = is_logged() ) ?
		$db->query(
				'SELECT * FROM users WHERE id = ?',
				$id
			)
	:
		NULL;

spl_autoload_register( function ( $name ) use ($_CONFIG) {
	$file = str_replace('\\', '/', $name);
	$file = $_SERVER['DOCUMENT_ROOT'] . '/' . $file . '.php';
	if( file_exists( $file ) )
		require $file;
});

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

$router->map('GET', '/[valid_audio_id:audio_id]', function( $audio_id) {
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
	if( 'beta.twitaudio.com' === $_SERVER['HTTP_HOST'] )
		$branch = ' ' . \application\HTTP::get('branch');
	else
		$branch = '';
	exec('./re-update.sh' . $branch, $output);
	echo implode("\n", $output);
});

/** end routes, match em'! **/

$match = $router->match();

if( $match && is_callable( $match['target'] ) )
	call_user_func_array( $match['target'], $match['params'] );
else
	\application\View::load_full_template('404');
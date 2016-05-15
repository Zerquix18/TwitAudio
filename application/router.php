<?php
/**
* This file routes everything
* See AltoRouter documentation here
* http://altorouter.com/
*
**/
if( 'cli' === php_sapi_name() ) {
	/**
	* in CLI there are no URLs so
	* instead of printing a 404...
	**/
	return;
}
$router = new AltoRouter();
$router->setBasePath( \Config::get('base_path') );
$router->addMatchTypes( array(
		'valid_username' 	=> '([\w]{2,15})',
		'valid_audio_id'	=> '([A-Za-z0-9]{6})',
	)
);
/**
* The home page*/
$router->map(
		'GET', // method
		'/', // path
		function() {
			return new \controllers\FrontController();
		}
	);
/**
* The login page
* The param login will be either signin or callback
**/
$router->map(
		'GET',
		'/[signin|callback:login]',
		function( $login ) {
			return new \controllers\LoginController( $login );
		}
	);
/**
* Text pages
**/
$router->map(
		'GET',
		'/[about|terms|privacy|faq|licensing:page]',
		function( $page ) {
			return new \controllers\TextPagesController( $page );
		}
	);
/**
* The profile
**/
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

$router->map(
		'GET',
		'/search',
		function() {
			return new \controllers\SearchController();
		}
	);

/**
*    ^ That guy must be above 
*because if it's not the guy below will catch it
*
* Audio pages
*
**/

$router->map(
		'GET',
		'/[valid_audio_id:audio_id]',
		function( $audio_id ) {
			return new \controllers\AudioController( $audio_id );
		}
	);
/**
* Frame for audios
**/
$router->map(
		'GET',
		'/frame/[valid_audio_id:audio_id]',
		function( $id ) {
			return new \controllers\FrameController( $id );
		}
	);

/** AJAX & Mobile requests **/
$router->map(
		'GET|POST',
		'/[ajax|mob:via]/[get|post:method]/[a:action]',
		function( $via, $method, $action ) {
			return new \controllers\MobileAJAXController(
					$via, $method, $action
				);
		}
	);
/**
* The page to re-update everything
* from the repo
**/
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

/** end routes, match em'! **/

$match = $router->match();

if( $match && is_callable( $match['target'] ) ) {
	call_user_func_array( $match['target'], $match['params'] );
} else {
	\application\View::load_full_template('404');
}
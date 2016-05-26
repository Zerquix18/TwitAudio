<?php
/**
 *
 * Front Controller
 * Loads the home page
**/
namespace controllers;
use \application\View,
	\models\Audios,
	\models\Users;
class FrontController {
	/**
	 * Prints the home page
	 */
	public function __construct() {
		try {
			if( ! is_logged() || \application\HTTP::get('logout') ) {
				View::set_page('home_unlogged');
				echo View::get_group_template('main/home-unlogged');
				return;
			}
			// --------------------------------------
			$show_noscript         = ! isset($_COOKIE['noscript'])  && 
									 ! isset($_GET['_ta_script']);

			$show_noscript_message =   isset($_GET['_ta_noscript']) &&
									 ! isset($_COOKIE['noscript']);

			if( $show_noscript_message ) {
				// so it won't be shown again
				setcookie('noscript', '1', time()+3600);
			}

			$cut_player     = array(
					'id'       => 'cut',
					'autoload' => false,
				);
			$preview_player = array(
					'id'       => 'preview',
					'autoload' => false, 
				);
			$effects_player = array(
					'id'       => 'effect-none',
					'autoload' => false,
				);

			$current_user   = Users::get_current_user();
			$minutes_length = $current_user->get_limit('audio_duration') / 60;

			$bars   = array(
					'home' => array(
						// show...?
						'show_noscript'         => $show_noscript,
						'show_noscript_message' => $show_noscript_message,

						// players
						'cut_player'     => $cut_player,
						'preview_player' => $preview_player,
						'effects_player' => $effects_player,

						//recents...

						'recent_popular' => Audios::get_popular_audios(),
						'recent_audios'	 => Audios::get_recent_audios(),

						'minutes_length' => $minutes_length,
					)
				);
			View::set_title('Home');
			View::set_page('home_logged');
			echo View::get_group_template('main/home-logged', $bars);
			
		} catch( \Exception $e ) {
			// database error or template error :c
			if( \Config::get('is_production') ) {
				echo file_get_contents('assets/templates/error-500.html');
			} else {
				echo $e->getMessage(), PHP_EOL;
			}
		} // catch
	} // __construct
} // Class
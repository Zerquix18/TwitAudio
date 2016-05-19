<?php
/**
 *
 * Front Controller
 * Loads the home page
**/
namespace controllers;
use \application\View,
	\models\Audios;
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
			View::set_page('home_logged');
			$data   = array(
					'recent_popular' => Audios::get_popular_audios(),
					'recent_audios'	 => Audios::get_recent_audios(),
				);
			View::get_group_template('home-logged', $data);
			
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
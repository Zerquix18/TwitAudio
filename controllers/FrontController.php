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
			$this->process();
		} catch( \Exception $e ) {
			echo $e->getMessage(), PHP_EOL;
		}
	}

	public function process() {
		if( ! is_logged() || \application\HTTP::get('logout') ) {
			View::set_page('home_unlogged');
			echo View::get_group_template('main/home-unlogged');
			return;
		}
		$data   = array(
				'recent_popular' => Audios::get_popular_audios(),
				'recent_audios'	 => Audios::get_recent_audios(),
			);
		
		View::load_full_template('default', $data);
	}
}
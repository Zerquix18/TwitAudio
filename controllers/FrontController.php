<?php
/**
*
* Front Controller
* Loads the home page
*
**/
namespace controllers;
use \application\View,
	\models\Audios;
class FrontController {

	public function __construct() {

		if( ! is_logged() || \application\HTTP::get('logout') ) {
			return View::load_full_template('index');
		}
		$data   = array(
				'recent_popular' => Audios::get_popular_audios(),
				'recent_audios'	 => Audios::get_recent_audios(),
			);
		
		View::load_full_template('default', $data);
	}
}
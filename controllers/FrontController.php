<?php
/**
*
* Front Controller
* Loads the home page
*
**/
namespace controllers;

use \application\Views as Views;

class FrontController {

	public function __construct() {

		if( ! is_logged() || \application\HTTP::get('logout') )
			return Views::load_full_template('index');
		
		$audios = new \models\Audio();
		$data = array(
				'recent_popular'	=> $audios->get_popular_audios(),
				'recent_audios'		=> $audios->get_recent_audios_by_user(),
			);
		Views::load_full_template('default', $data);
	}
}
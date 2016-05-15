<?php
/**
 * Controller for the frame page
 * 
 * @author Zerquix18
 * @copyright 2016 Luis A. Martínez
**/
namespace controllers;
use \application\View,
	\models\Audios;
	
class FrameController {
	/**
	 * Prints the HTML for the frame page.
	 * If it does not exist, or the audio is not public,
	 * then will print a 404 page.
	 * 
	 * @param string $audio_id}
	 */
	public function __construct( $audio_id ) {
		$audio  = Audios::get($audio_id);
		
		if( empty($audio) ) {
			View::exit_404();
		}

		$user = $audio['user'];

		if( ! $user['audios_public'] ) {
			View::exit_404();
		}

		View::load_full_template('frame', array('audio' => $audio) );
	}
}
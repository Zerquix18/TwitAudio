<?php
/**
*
* Controller for the frame page
* @author Zerquix18
*
*
**/
namespace controllers;
use \application\View,
	\models\Audios;
	
class FrameController {

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
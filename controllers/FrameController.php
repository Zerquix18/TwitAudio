<?php
/**
*
* Controller for the frame page
* @author Zerquix18
*
*
**/
namespace controllers;
use \application\Views, \models\Audio;

class FrameController {

	public function __construct( $audio_id ) {
		$audios = new Audio;
		$audio = $audios->get_audio_info($audio_id);
		
		if( ! $audio )
			Views::exit_404();

		$user = $audio->user;

		if( 0 == $user->audios_public )
			Views::exit_404();

		Views::load_full_template('frame', array('audio' => $audio ) );
	}
}
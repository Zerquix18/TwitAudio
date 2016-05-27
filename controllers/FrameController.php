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
	 * @param string $audio_id
	 */
	public function __construct( $audio_id ) {
		try {
			$audio  = Audios::get($audio_id);
			
			if( empty($audio) ) {
				View::exit_404();
			}

			$user = $audio['user'];

			if( ! $user['audios_public'] ) {
				View::exit_404();
			}
			$bars = array('frame'  =>
					array('player' => $audio['player'])
				);

			View::set_page('frame');
			echo View::get_group_template('main/frame', $bars);
		} catch( \Exception $e ) {
			// database error or template error :c
			if( \Config::get('is_production') ) {
				View::exit_500();
			} else {
				echo $e->getMessage(), PHP_EOL;
			}//if
		}//catch
	}//__construct
}//class
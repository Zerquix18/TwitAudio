<?php
/**
 * AudioController.php
 * Controller for audio pages
 *
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/
namespace controllers;
use \application\View,
	\application\HTTP,
	\models\Audios,
	\models\Users;
	
class AudioController {
	/**
	 * Prints the HTML with the content for the audio ID.
	 * If the audio does not exist,
	 * or the current user can't listen to it,
	 * then prints a 404.
	 * 
	 * @param string $audio_id
	 */
	public function __construct( $audio_id ) {
		$audio = Audios::get($audio_id);
		if( ! $audio ) {
			View::exit_404();
		}

		$current_user = Users::get_current_user();
		if( ! $current_user->can_listen( $audio['user']['user'] ) ) {
			View::exit_404();
		}

		if( '0' !== $audio['reply_to'] ) {
			HTTP::redirect(
					url() . $audio['reply_to'] . '?reply_id=' . $audio['id']
				);
		}

		$replies = Audios::get_replies($audio['id'], 1);
		/** LINKED REPLIES **/
		// Dragons be here
		if( $reply_id = HTTP::get('reply_id') ) { // was the param sent?
			$reply = Audios::get($reply_id);
			if( ! empty($reply) && $reply['reply_to'] == $audio_id ) {
				// reply exists and it's replying to this audio
				$linked             = $reply_id;
				$all_replies        = $replies;
				$reply['is_linked'] = true; // to tell the display_audio function
				$replies = array( // move everything
						'audios'    => array(),
						'load_more' => $all_replies['load_more'],
						'page'      => $all_replies['page'],
						'total'     => $all_replies['total']
					);
				$replies['audios'][] = $reply; // he goes first!
				$count_replies       = count($all_replies['audios']);
				for( $i = 0; $i < $count_replies; $i++ ) {
					if( $all_replies['audios'][$i]['id'] == $reply_id ) {
						/** don't add the linked reply, cuz it was added first **/
						continue;
					}
					$replies['audios'][] = $all_replies['audios'][$i];
				}
			}
		}
		/** / LINKED REPLIES **/
		View::load_full_template('audio', array(
				'audio'		=> $audio,
				'replies'	=> $replies,
				'linked'    => isset($linked) ? $linked : ''
			)
		);
	}
}
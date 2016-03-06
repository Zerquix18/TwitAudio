<?php
/**
* Controller for audio pages
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright (c) 2016 - Luis A. Martínez
*
**/
namespace controllers;

use \application\Views,
	\models\Audio,
	\models\User,
	\application\HTTP;

class AudioController {

	public function __construct( $audio_id ) {
		$audios = new Audio;
		$audio = $audios->get_audio_info( $audio_id );
		if( ! $audio )
			Views::exit_404();

		$current_user = new User;
		if( ! $current_user->can_listen( $audio->user->user ) )
			Views::exit_404();

		if( '0' !== $audio->reply_to )
			HTTP::redirect(
					url() . $audio->reply_to . '?reply_id=' . $audio->id
				);

		$replies = $audios->load_replies( $audio->id, 1);
		/** LINKED REPLIES **/
		if( $reply_id = HTTP::get('reply_id') ) { // was the param sent?
			$reply = $audios->get_audio_info(
					$reply_id,
					'id,user,audio,reply_to,description,
						time,plays,favorites,duration'
				);
			if( $reply && $reply->reply_to == $audio_id ) {
				$linked = $reply_id;
				// reply exists and it's replying to this audio
				$all_replies = $replies;
				$reply->is_linked = true; // to tell the display_audio function
				$replies = array( // move everything
						'audios'    => array(),
						'load_more' => $all_replies['load_more'],
						'page'      => $all_replies['page'],
						'total'     => $all_replies['total']
					);
				$replies['audios'][] = $reply; // he goes first!
				$count_replies = count($all_replies['audios']);
				for( $i = 0; $i < $count_replies; $i++ ) {
					if( $all_replies['audios'][$i]->id == $reply_id ) {
						/** don't add the linked reply, cuz it was added first **/
						continue;
					}
					$replies['audios'][] = $all_replies['audios'][$i];
				}
			}
		}
		/** / LINKED REPLIES **/
		Views::load_full_template('audio', array(
				'audio'		=> $audio,
				'replies'	=> $replies,
				'linked'    => isset($linked) ? $linked : ''
			)
		);
	}
}
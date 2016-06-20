<?php
/**
 * AudioController.php
 * Controller for audio pages
 *
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/
namespace controllers;

use \application\View;
use \application\HTTP;
use \models\Audios;
use \models\Users;
    
class AudioController
{
    /**
     * Prints the HTML with the content for the audio ID.
     * If the audio does not exist,
     * or the current user can't listen to it,
     * then prints a 404.
     * 
     * @param string $audio_id
     */
    public function __construct($audio_id)
    {
        $audio = Audios::get($audio_id);
        if (! $audio) {
            View::exit404();
        }

        $current_user = Users::getCurrentUser();

        if (! $current_user->canListen($audio['user']['id'])) {
            View::exit404();
        }

        if ($audio['reply_to']) {
            // if is trying to get into a reply
            // redirect to the original audio

            HTTP::redirect(
                    url() . $audio['reply_to'] .
                    '?reply_id=' . $audio['id']
                );
        }
        $replies = Audios::getReplies($audio['id'], 1);
        /** LINKED REPLIES **/
        // Dragons be here
        $reply_id = HTTP::get('reply_id');
        if ($reply_id) { // was the param sent?
            $reply = Audios::get($reply_id);
            if ($reply && $reply['reply_to'] == $audio_id) {
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
                for ($i = 0; $i < $count_replies; $i++) {
                    if ($all_replies['audios'][$i]['id'] == $reply_id) {
                        /** don't add the linked reply, cuz it was added first **/
                        continue;
                    }
                    $replies['audios'][] = $all_replies['audios'][$i];
                }
            }
        } /** / LINKED REPLIES **/

        /*                   window title              */

        $user               = $audio['user']['username'];
        $title              = $user . ': ';
        $description_length = mb_strlen($audio['description'], 'utf-8');
        if (0 === $description_length) {
            // no text, then nothing
            $title = sprintf("%s's audio", $user);
        } elseif ($description_length > 15) {
            $title .= substr($audio['description'], 0, 10) . '...';
        } elseif ($description_length < 15) {
            $title .= $audio['description'];
        }

        $robots = $audio['user']['audios_privacy'] === 'public';

        $twitter_share_url  = 'https://twitter.com/intent/tweet?';
        $twitter_share_url .= // ↓
        http_build_query(array(
                'text'    => sprintf(
                            "Listen to @%s's audio",
                            $user
                        ),
                'via'     => 'twit_audio',
                'related' => 'twit_audio,zerquix18,superjd10_,chusen'
            )
        );

        $bars = array(
            /** twitter cardss **/
            'header' => array(
                'twitter' => array(
                        'player' => url('frame/'. $audio['id']),
                        'url'    => url($audio['id']),
                        'title'  => sprintf('%s on TwitAudio', $user),
                    )
                ),
            'audio'  => array(
                    'audio'             => $audio,
                    'replies'           => $replies,
                    'linked'            => isset($linked) ? $linked : '',
                    'twitter_share_url' => $twitter_share_url,
                )
            );
        View::setTitle($title);
        View::setRobots($robots);
        View::setPage('audio');
        echo View::getGroupTemplate('main/audio', $bars);
    }//__construct
}//class
<?php
/**
 * Controller for the frame page
 * 
 * @author Zerquix18
 * @copyright 2016 Luis A. Martínez
**/
namespace controllers;

use \application\View;
use \models\Audios;
    
class FrameController
{
    /**
     * Prints the HTML for the frame page.
     * If it does not exist, or the audio is not public,
     * then will print a 404 page.
     * 
     * @param string $audio_id
     */
    public function __construct($audio_id)
    {
        $audio  = Audios::get($audio_id);
            
        if (! $audio) {
            View::exit404();
        }

        $user = $audio['user'];

        if ('public' !== $user['audios_privacy']) {
            View::exit404();
        }
        $bars = array(
                    'frame'  => array('player' => $audio['player'])
                );

        View::setPage('frame');
        echo View::getGroupTemplate('main/frame', $bars);
    }//__construct
}//class
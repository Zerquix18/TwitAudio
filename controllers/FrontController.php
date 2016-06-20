<?php
/**
 *
 * Front Controller
 * Loads the home page
**/
namespace controllers;

use \application\View;
use \models\Audios;
use \models\Users;
class FrontController
{
    /**
     * Prints the home page
     */
    public function __construct()
    {
        if (! is_logged() || \application\HTTP::get('logout')) {
            View::setPage('home_unlogged');
            echo View::getGroupTemplate('main/home-unlogged');
            return;
        }
        // --------------------------------------
        $show_noscript         = ! isset($_COOKIE['noscript'])  && 
                                 ! isset($_GET['_ta_script']);

        $show_noscript_message =   isset($_GET['_ta_noscript']) &&
                                 ! isset($_COOKIE['noscript']);

        if ($show_noscript_message) {
            // so it won't be shown again
            setcookie('noscript', '1', time() + 3600);
        }

        $cut_player     = array(
                'id'       => 'cut',
                'autoload' => false,
            );
        $preview_player = array(
                'id'       => 'preview',
                'autoload' => false, 
            );
        $effects_player = array(
                'id'       => 'effect-none',
                'autoload' => false,
            );

        $current_user   = Users::getCurrentUser();
        $minutes_length = $current_user->getLimit('audio_duration') / 60;

        $bars   = array(
                'home' => array(
                    // show...?
                    'show_noscript'         => $show_noscript,
                    'show_noscript_message' => $show_noscript_message,

                    // players
                    'cut_player'     => $cut_player,
                    'preview_player' => $preview_player,
                    'effects_player' => $effects_player,

                    //recents...

                    'recent_popular' => Audios::getPopularAudios(),
                    'recent_audios'  => Audios::getRecentAudios(),

                    'minutes_length' => $minutes_length,
                )
            );
        View::setTitle('Home');
        View::setPage('home_logged');
        echo View::getGroupTemplate('main/home-logged', $bars);
    } // __construct
} // Class
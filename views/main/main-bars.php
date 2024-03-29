<?php
/**
 * This file contains all the bars 
 * for the main template.
 * It also contains some globals bars like 'user'
 */
use \models\Users,
    \models\Audios,
    \application\View,
    \application\HTTP,
    \models\Payment;

$is_logged    = is_logged();
$current_user = Users::getCurrentUser();
$is_premium   = $is_logged && $current_user->isPremium();

// contains all the strings:
$bars      = array();

// Ok, let's start with the header bars:

$bars['header'] = array();

// robots by default will be on

$bars['header']['robots'] = true;

/**
 * the styles
 */
if (\Config::get('is_production')) {
    $bars['header']['styles'] = array('vendor.css', 'app.css');
} else {
    // all the styles are in this JSON. In the live website, this JSON
    // is read and they all are minified, resulting in vendor.css and app.css
    $styles = file_get_contents(
            DOCUMENT_ROOT . '/assets/css/styles.json'
        );
    $styles = json_decode($styles, true);
    foreach ($styles as $directory => $all_styles) {
        $all_styles_count = count($all_styles);
        for ($i = 0; $i < $all_styles_count; $i++) {
            $bars['header']['styles'][] = $directory . '/' . $all_styles[$i];
        }
    }
}

// this will be empty by default. In the AudioController this should be
// overwritten.
$bars['header']['twitter']  = array();

// main global styles

$bars['main'] = array();

// the URLs
$bars['main']['home_url']   = url();
$bars['main']['search_url'] = url('search');
$bars['main']['signin_url'] = 
url('signin?back_to=') . //site.com/?signin?back_to=http://site.com/currenturl
urlencode(url(substr($_SERVER['REQUEST_URI'], 1)));
// substr is because url() already has the final slash and request_uri has one
$bars['main']['terms_url']   = url('terms');
$bars['main']['about_url']   = url('about');
$bars['main']['privacy_url'] = url('privacy');
$bars['main']['faq_url']     = url('faq');
// this one must have the final slash ↓
$bars['main']['ajax_url']    = url('ajax/');
$bars['main']['swf_path']    = url('assets/swf/');
if ($is_logged) {
    $bars['main']['logout_url']    = url('?logout=1');
}
if (View::is('home_unlogged')) {
    // don't add a return_to in the home page
    $bars['main']['signin_url'] = url('signin');
}

// should we show... ?
$bars['main']['show_sidebar'] = ! View::is('404', 'text', 'frame');
$bars['main']['show_navbar']  = ! View::is('home_unlogged', 'frame');

// the class of the body 
if (View::is('text')) {
    $bars['main']['body_class'] = 'text';
} elseif (View::is('404')) {
    $bars['main']['body_class'] = 'page-404';
} elseif (View::is('home_unlogged', 'frame')) {
    $bars['main']['body_class'] = 'white';
}

// after login
if (   ! View::is('404', 'text', 'home_unlogged')
    && isset($_SESSION['first_time'])) {
    unset($_SESSION['first_time']);
    $bars['main']['after_login']           = array();
    $bars['main']['after_login']['status'] = $current_user->audios_privacy;
}

// after login but with error
if (isset($_SESSION['login_error'])) {
    $bars['main']['login_error'] =
    str_replace(array("\n", '"'), '', strip_tags($_SESSION['login_error']));
    unset($_SESSION['login_error']);
}

// stripe key for the footer
$bars['main']['stripe_key'] = Payment::getStripePublicKey();

// after logout
if (View::is('home_unlogged') && $is_logged && HTTP::get('logout')) {
    // if the user request a logout
    \Sessions::signOut(); // defined in models/Sessions.php
    $bars['main']['logout_successful'] = true;
}

// info of the current user
$bars['user']                         = (array) $current_user;
$bars['user']['is_logged']            = $is_logged;
$bars['user']['is_premium']           = $is_premium;
$bars['user']['file_upload_limit']    = $current_user->getLimit('file_upload');
$bars['user']['audio_duration_limit'] = // ↓
$current_user->getLimit('audio_duration');

$bars['user']['audio_duration_minutes_limit'] =
$current_user->getLimit('audio_duration') / 60;

// sidebar
// please note that the operator here is 'or' instead of '||'
$bars['sidebar']['show_ads'] = ! View::is('frame') or ! $is_premium;
// settings
if ($is_logged) {
    $bars['settings']['are_audios_public'] =
    'public' === $current_user->audios_privacy;

    $bars['settings']['are_favs_public']   =
    'public' === $current_user->favs_privacy;
}

// 
$bars['footer']                     = array();
$bars['footer']['current_year']     = date('Y');
$bars['footer']['show_ads']         = // ↓
! View::is('home_unlogged', 'text', 'frame', '404') or ! $is_premium;
// note that it is 'or' instead of '||'

$bars['footer']['show_page_footer'] = View::is('home_unlogged', 'text');

/**
 * the scripts
 */
if (\Config::get('is_production')) {
    $bars['footer']['scripts'] = array('vendor.js', 'app.js');
} else {
    // all the script are in this JSON. In the live website, this JSON
    // is read and they all are minified, resulting in vendor.css and app.css
    $scripts = file_get_contents(
            DOCUMENT_ROOT . '/assets/js/scripts.json'
        );
    $scripts = json_decode($scripts, true);
    foreach ($scripts as $directory => $all_scripts) {
        $all_scripts_count = count($all_scripts);
        for ($i = 0; $i < $all_scripts_count; $i++) {
            $bars['footer']['scripts'][] =
            $directory . '/' . $all_scripts[$i];
        }
    }
}
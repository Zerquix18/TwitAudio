<?php
/**
*
* Archivo de traducciones
*
* @author Zerquix18
* @package TrackYourPenguin
* @since 0.1.1
*
**/

$preg = sprintf("#%s#", basename(__FILE__) );
if( preg_match($preg, $_SERVER['PHP_SELF'])) exit();


//archivos necesarios para la traduccion
require_once( PATH . INC . 'i18n/gettext.php');
require_once( PATH . INC . 'i18n/streams.php');

$lenguajes = array("en");

function prefered_language( array $available_languages) {
    $available_languages = array_flip($available_languages);
    $langs;
    preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches, PREG_SET_ORDER);
    if( empty($matches) )
    	return "en";
    foreach($matches as $match) {
        list($a, $b) = explode('-', $match[1]) + array('', '');
        $value = isset($match[2]) ? (float) $match[2] : 1.0;
        if(isset($available_languages[$match[1]])) {
            $langs[$match[1]] = $value;
            continue;
        }
        if(isset($available_languages[$a])) {
            $langs[$a] = $value - 0.1;
        }
    }
    arsort($langs);
    return (string) array_shift( array_keys($langs) );
}
function getlang() {
	global $lenguajes, $db;
	if( is_logged() )
		return $db->query("SELECT lang FROM users WHERE id = ?", $_SESSION['id'])->lang;
	if( isset($_GET['l']) && is_string($_GET['l']) && in_array($_GET['l'], $lenguajes) ){
		if( ! isset($_COOKIE['lang']) )
			setcookie('lang', $auto);
		return $_GET['l'];
	}
	if( ! isset($_COOKIE['lang']) || ! is_string($_COOKIE['lang']) || ! in_array($_COOKIE['lang'], $lenguajes) ) {
		$auto = prefered_language( $lenguajes );
		setcookie('lang', $auto);
		return $auto;
	}elseif( isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $lenguajes) )
		return $_COOKIE['lang'];
	return "en";
}

// requerimos al lenguaje, dando excepción de que no sea el default.

if( getlang() !== "en" && file_exists(PATH . INC . 'leng/'. getlang() . '.mo') ):
	$tr = new gettext_reader( new CachedFileReader( PATH . INC . 'leng/' . getlang() . '.mo' ) );
	$tr->load_tables();
	#_textdomain('default');
else:
	$tr = new gettext_reader(null);
endif;
function __( $texto ) {
	return $GLOBALS['tr']->translate( $texto );
}
function _e( $texto ) {
	echo __($texto);
}
function _n( $singular, $plural, $numero ) {
	global $tr;
	if( (int) $numero = 1 )
		return $singular;
	else
		return $plural;
}
function esc_html( $texto ) {
	return htmlspecialchars( __($texto) );
}
function esc_html_e( $texto ) {
	echo esc_html($texto);
}
$lenguajest = array(
		"en"=> __("English"),
	);
$lenguajeso = array(
		"en" => "English",
	);
<?php
/**
*
* Translations file
*
* @author Zerquix18
*
**/

require dirname(__FILE__) . '/i18n/gettext.php';
require dirname(__FILE__) . '/i18n/streams.php';

$lenguajes = array("en");

function prefered_language( array $available_languages) {
	if( ! isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) )
		return 'en';
	$available_languages = array_flip($available_languages);
	$langs = array();
	preg_match_all(
		'~([\w-]+)(?:[^,\d]+([\d.]+))?~',
		strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']),
		$matches,
		PREG_SET_ORDER
	);
	if( empty($matches) )
		return 'en';
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
	$x=array_keys($langs);
	return (string) array_shift( $x );
}
function getlang() {
	global $lenguajes, $db;
	if( $x = is_logged() )
		return $db->query(
			"SELECT lang FROM users WHERE id = ?",
			$x)
		->lang;
	if( isset($_GET['l'])
		&& is_string($_GET['l'])
		&& in_array($_GET['l'], $lenguajes)
		){
		if( ! isset($_COOKIE['lang']) )
			setcookie('lang', $auto);
		return $_GET['l'];
	}
	if( ! isset($_COOKIE['lang'])
		|| ! is_string($_COOKIE['lang'])
		|| ! in_array($_COOKIE['lang'], $lenguajes) 
		) {
		$auto = prefered_language( $lenguajes );
		setcookie('lang', $auto);
		return $auto;
	}elseif( isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $lenguajes) )
		return $_COOKIE['lang'];
	return "en";
}

if( getlang() !== "en"
	&& file_exists(
			$_SERVER['DOCUMENT_ROOT'] .
				'/assets/leng/'. getlang() . '.mo'
			)
	):
	$tr = new gettext_reader( new CachedFileReader(
			$_SERVER['DOCUMENT_ROOT'] .
				'/assets/leng/' . getlang() . '.mo' ) );
	$tr->load_tables();
	#_textdomain('default');
else:
	$tr = new gettext_reader(null);
endif;
function __( $texto ) {
	return $GLOBALS['tr']->translate( $texto );
}
function _n( $sing, $plur, $numb ) {
	if( (int) $numb == 1 )
		return sprintf($sing, $numb);
	return sprintf($plur, $numb);
}
function _e( $texto ) {
	echo __($texto);
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
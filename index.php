<?php
require_once('load.php');
$p = isset($_GET['_p']) && is_string($_GET['_p']) ? $_GET['_p'] : '';
switch($p):
	case "audio":
	! preg_match("/^[A-Za-z0-9]{6}$/", $_GET['id']) and exit(load_full_template('404') );
	$a = $db->query("SELECT * FROM audios WHERE id = ?", $_GET['id']);
	if( $a->nums == 0 )
		return load_full_template('404');
	$u = $db->query("SELECT * FROM users WHERE id = ?", $a->user);
	$_BODY['page'] = $p
	and $_BODY['audio'] = $a
	and $_BODY['user'] = $u
	and load_full_template('audio');
	break;
	case "settings":
	is_logged() ?
		$_BODY['page'] = 'settings'
		and $_BODY['robots'] = false
		or load_full_template('settings')
	:
		header('Location: ' . url());
	break;
	case "search":
	$_BODY['page'] = 'search' and load_full_template('search');
	break;
	case "frame":
	!validate_args(@$_GET['id']) and exit( load_full_template('404') );
	!preg_match("/^[A-Za-z0-9]{6}$/", $_GET['id']) and exit( load_full_template('404') );
	$a = $db->query("SELECT * FROM audios WHERE id = ?", $_GET['id']);
	if( $a->nums == 0 )
		return load_full_template('404');
	$u = $db->query("SELECT audios_public FROM users WHERE id = " . $a->user);
	if( $u->audios_public == '0' )
		return load_full_template('404');
	$_BODY['audio'] = $a and load_full_template('frame');
	break;
	case "profile":
	! validate_args(@$_GET['u']) and exit();
	$u = $db->query("SELECT * FROM users WHERE user = ?", $db->real_escape($_GET['u']) );
	( $u->nums > 0 ) ?
		$_BODY['page'] = $p
		and $_BODY['user'] = $u
		and load_full_template('profile')
	:
		load_full_template('404');
	break;
	case "text":
	if( ! isset($_GET['txt']) || ! in_array($_GET['txt'], array("about", "privacy", "tos", "faq", "contact") ) )
		return load_full_template('404');
	$_BODY['txt'] = $_GET['txt'];
	load_full_template('text');
	break;
	default:
	is_logged() ?
		$_BODY['page'] = 'default' and load_full_template('default')
	:
		$_BODY['page'] = 'index' and load_full_template('index');
endswitch;
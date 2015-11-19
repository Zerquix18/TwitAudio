<?php
require_once('load.php');
$p = isset($_GET['_p']) && is_string($_GET['_p']) ? $_GET['_p'] : '';
switch($p):
	case "audio":
		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $_GET['id']) )
			exit(load_full_template('404'));
		$a = $db->query(
			"SELECT * FROM audios
			WHERE reply_to = '0' AND id = ?",
			$_GET['id']
		);
		if( $a->nums == 0 )
			return load_full_template('404');
		$u = $db->query("SELECT * FROM users WHERE id = ?", $a->user);
		$_BODY['page'] = $p;
		$_BODY['audio'] = $a;
		$_BODY['user'] = $u;
		load_full_template('audio');
		break;
	case "search":
		$_BODY['page'] = 'search';
		load_full_template('search');
		break;
	case "frame":
		if( ! validate_args(@$_GET['id']) )
			return load_full_template('404');
		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $_GET['id']) )
			return load_full_template('404');
		$a = $db->query(
			"SELECT * FROM audios WHERE id = ?",
			$_GET['id']
		);
		if( $a->nums == 0 )
			return load_full_template('404');
		$u = $db->query(
			"SELECT audios_public FROM users WHERE id = ?",
			$a->user
		);
		if( $u->audios_public == '0' )
			return load_full_template('404');
		$_BODY['audio'] = $a and load_full_template('frame');
		break;
	case "profile":
		if( ! validate_args(@$_GET['u']) )
			exit;
		$u = $db->query(
			'SELECT * FROM users WHERE user = ?',
			$db->real_escape($_GET['u'])
		);
		if( $u->nums === 0 )
			load_full_template('404');
		$_BODY['page'] = $p;
		$_BODY['user'] = $u;
		load_full_template('profile');
		break;
	case "text":
		if( ! isset($_GET['txt'])
			|| ! in_array(
				$_GET['txt'],
				array(
					'about',
					'privacy',
					'tos',
					'faq',
					)
				)
			)
		return load_full_template('404');
		$_BODY['txt'] = $_GET['txt'];
		load_full_template('text');
		break;
	default:
		is_logged() ?
			isset($_GET['logout']) ?
				$_BODY['page'] = 'index'
				and load_full_template('index')
			:
				$_BODY['page'] = 'default'
				and load_full_template('default')
		:
			$_BODY['page'] = 'index'
			and load_full_template('index');
endswitch;
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
$p = isset($_GET['_p']) && is_string($_GET['_p']) ? $_GET['_p'] : '';
switch($p):
	case "audio":
		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $_GET['id']) )
			return load_full_template('404');
		$a = $db->query(
			"SELECT * FROM audios
			WHERE id = ?",
			$_GET['id'] // protected by regex
		);
		if( $a->nums == 0 )
			return load_full_template('404');
		if( $a->reply_to != '0' )
			ta_redirect(
				url() . $a->reply_to . '?reply_id=' . $a->id 
			);
		$u = $db->query("SELECT * FROM users WHERE id = ?", $a->user);
		$_BODY['audio'] = $a;
		$_BODY['user'] = $u;
		load_full_template('audio');
		break;
	case "search":
		load_full_template('search');
		break;
	case "frame":
		if( ! validate_args(@$_GET['id']) )
			return load_full_template('404');
		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $_GET['id']) )
			return load_full_template('404');
		$a = $db->query(
			"SELECT * FROM audios WHERE id = ?",
			$_GET['id'] // protected by regex
		);
		if( $a->nums == 0 )
			return load_full_template('404');
		$u = $db->query(
			"SELECT audios_public FROM users
			WHERE id = ?",
			$a->user
		);
		if( $u->audios_public == '0' )
			return load_full_template('404');
		$_BODY['audio'] = $a;
		load_full_template('frame');
		break;
	case "profile":
		if( ! validate_args(@$_GET['u']) )
			exit;
		$u = $db->query(
			'SELECT * FROM users WHERE user = ?',
			$db->real_escape($_GET['u'])
		);
		if( $u->nums === 0 )
			return load_full_template('404');
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
		load_full_template('text');
		break;
	default: # its hard to read but funny :v
		is_logged() ?
			isset($_GET['logout']) ?
				load_full_template('index')
			:
				load_full_template('default')
		:
			load_full_template('index');
endswitch;
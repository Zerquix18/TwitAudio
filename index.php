<?php
/**
*
* The home page
* This routes everything and calls the templates
* @author Zerquix18 <zerquix18@hotmail.com>
* @copyright Copyright (c) 2015 Luis A. Martínez
*
**/
/* requires everything */
require $_SERVER['DOCUMENT_ROOT'] . '/load.php';
/*
* ?p_ is routed by the .htaccess file
* But it can be discovered, so don't trust it
*/
$page = isset($_GET['_p']) && is_string($_GET['_p']) ? $_GET['_p'] : '';
switch($page):
	case "audio":
		if( ! is_audio_id_valid( $_GET['id'] ) )
			return load_full_template('404');
		$audio = $db->query(
			"SELECT * FROM audios
			WHERE id = ? AND status = '1'",
			$_GET['id']
		);
		if( 0 == $audio->nums )
			return load_full_template('404');
		if( '0' != $audio->reply_to ) // if it's a reply
			ta_redirect(
				url() . $audio->reply_to .
				'?reply_id=' . $audio->id
			);
		$user = $db->query(
			'SELECT * FROM users WHERE id = ?',
			$audio->user
		);
		$_BODY['audio'] = $audio;
		$_BODY['user'] = $user;
		load_full_template('audio');
		break;
	case "search":
		load_full_template('search');
		break;
	case "frame":
		if( ! validate_args( $_GET['id'] ) )
			return load_full_template('404');
		if( ! is_audio_id_valid( $_GET['id'] ) )
			return load_full_template('404');
		$audio = $db->query(
			"SELECT * FROM audios
			WHERE id = ? AND status = '1'",
			$_GET['id']
		);
		if( 0 == $audio->nums )
			return load_full_template('404');
		$user = $db->query(
			"SELECT audios_public FROM users
			WHERE id = ?",
			$audio->user
		);
		if( '0' == $user->audios_public )
			return load_full_template('404');
		$_BODY['audio'] = $audio;
		load_full_template('frame');
		break;
	case "profile":
	// even if this is routed by the htaccess
	// imma never trust it ↓
		if( ! validate_args(@$_GET['u']) )
			return load_full_template('404');
		$user = $db->query(
			'SELECT * FROM users WHERE user = ?',
			$_GET['u']
		);
		if( $user->nums === 0 )
			return load_full_template('404');
		$_BODY['user'] = $user;
		load_full_template('profile');
		break;
	case "text":
	// I can see goku making a kame ha below
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
<?php
/**
* Login controller
* Handles the requests in the web
* For login
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace controllers;

use \application\Twitter;
use \application\HTTP;

class LoginController {

	public function __construct( $action ) {
		if( ! isset($_COOKIE['ta_session']) ) {
			$_SESSION['login_error'] = __('There was a problem while logging you in. Please, try again later.');
			HTTP::redirect( url() );
		}

		return $this->$action();
	}

	private function signin() {
		$twitter = new Twitter();
		if( isset($_GET['back_to']) && is_string($_GET['back_to']) ):
			// this will replace every non alphabetic character
			// into :{character}
			// so ':' will be '\:'
			// and it's protected
			$url = preg_replace('/([^\w])/', '\\\$1', url() );
			// check it gets back to the site, no outside
			if( preg_match(
					'/^(' . $url . ')/',
					$back_to = urldecode($_GET['back_to'])
					)
				)
				$_SESSION['back_to'] = $back_to;
		endif;
		$login_url = $twitter->get_login_url();
		if( ! $login_url ) {
			$_SESSION['login_error'] = __('There was a problem. Please, try again.');
			HTTP::redirect( url() );
		}
		HTTP::redirect( $login_url );
	}
	/**
	* Back from Twitter
	*
	**/
	private function callback() {
		if( isset($_SESSION['back_to']) ) { 
			$back_to = $_SESSION['back_to'];
			unset($_SESSION['back_to']);
		} else {
			$back_to = url();
		}

		if(! isset(
			$_SESSION['oauth_token'],
			$_SESSION['oauth_token_secret'])
			) {

			$_SESSION['login_error'] = __('There was a problem while logging you in.');
			HTTP::redirect( $back_to );
		}

		if( isset($_GET['denied'])
			&& $_GET['denied'] == $_SESSION['oauth_token']) {

			$_SESSION['login_error'] = __('There was a problem while logging you in.');
			HTTP::redirect( $back_to );
		}

		$oauth_token 	= HTTP::get('oauth_token');
		$oauth_verifier = HTTP::get('oauth_verifier');

		if( ! ( $oauth_token && $oauth_verifier)
			|| $_SESSION['oauth_token'] != $oauth_token ) {
			$_SESSION['login_error'] = __('There was a problem while logging you in.');
			HTTP::redirect( $back_to );
		}

		$twitter = new Twitter(
				$_SESSION['oauth_token'],
				$_SESSION['oauth_token_secret']
			);
		unset($_SESSION['oauth_token']);
		unset($_SESSION['oauth_token_secret']);

		$tokens = $twitter->callback( $oauth_verifier );
		$users = new \models\User();
		$create_user = $users->create(
				$tokens['oauth_token'],
				$tokens['oauth_token_secret'],
				'web'
			);
		if( false === $create_user ) {
			$_SESSION['login_error'] = __('There was a problem. Please, try again later.');
			HTTP::redirect( $back_to );
		}

		if( $create_user['first_time'] )
			$_SESSION['first_time'] = true;

		HTTP::redirect( $back_to );
	}
}
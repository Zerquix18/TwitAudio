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

use \application\Twitter,
	\application\HTTP,
	\application\exceptions\LoginException;


class LoginController {

	public function __construct( $action ) {
		try {
			if( ! isset($_COOKIE['ta_session']) ) {
				throw new LoginException('Cookies are needed to sign in');
			}
			$this->$action();
		} catch ( LoginException $e ) {
			HTTP::redirect( $e->redirect_to );
		}
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
		if( ! $login_url )
			throw new LoginException('Could not get login URL');

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

		if( ! isset(
			$_SESSION['oauth_token'],
			$_SESSION['oauth_token_secret'])
			) {
			throw new LoginException('No tokens were stored', $back_to);
		}

		$denied = HTTP::get('denied');

		if( false === empty($denied) && $denied == $_SESSION['oauth_token'] )
			throw new LoginException('Request was denied', $back_to);

		$oauth_token 	= HTTP::get('oauth_token');
		$oauth_verifier = HTTP::get('oauth_verifier');

		if( ! ( $oauth_token && $oauth_verifier)
			|| $_SESSION['oauth_token'] != $oauth_token ) {
			throw new LoginException('Oauth tokens does not match', $back_to);
		}

		$twitter = new Twitter(
				$_SESSION['oauth_token'],
				$_SESSION['oauth_token_secret']
			);
		unset($_SESSION['oauth_token']);
		unset($_SESSION['oauth_token_secret']);

		$tokens = $twitter->tw->oauth(
					"oauth/access_token",
					array("oauth_verifier" => $oauth_verifier)
				);
		$users = new \models\User();
		$create_user = $users->create(
				$tokens['oauth_token'],
				$tokens['oauth_token_secret'],
				'web'
			);

		if( false === $create_user )
			throw new LoginException(
				'Internal error while registering user',
				$back_to
			);

		if( $create_user['first_time'] )
			$_SESSION['first_time'] = true;

		HTTP::redirect( $back_to );
	}
}
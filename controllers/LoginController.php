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
	\application\HTTP;


class LoginController {

	// the place to redirect after a success or error
	private $redirect_to = '';

	public function __construct( $action ) {
		global $_CONFIG;
		try {
			if( ! isset($_COOKIE['ta_session']) ) {
				throw new \Exception('Cookies are needed to sign in');
			}
			$this->$action();
		} catch (\Exception $e) {
			if( $_CONFIG['display_errors'] ) {
				// are we in production? no.
				$_SESSION['login_error'] = $e->getMessage();
			} else {
				$_SESSION['login_error'] =
				'There was a problem while login you in. Please, try again.';
			}
			HTTP::redirect( $this->redirect_to ?: url() );
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
			throw new \Exception('Could not get login URL');

		HTTP::redirect( $login_url );
	}
	/**
	* Back from Twitter
	*
	**/
	private function callback() {
		if( isset($_SESSION['back_to']) ) { 
			$this->redirect_to = $_SESSION['back_to'];
			unset($_SESSION['back_to']);
		} else {
			$this->redirect_to = url();
		}

		if( ! isset(
			$_SESSION['oauth_token'],
			$_SESSION['oauth_token_secret'])
			) {
			throw new \Exception('No tokens were stored');
		}

		$denied = HTTP::get('denied');

		if( false === empty($denied) && $denied == $_SESSION['oauth_token'] )
			throw new \Exception('Request was denied');

		$oauth_token 	= HTTP::get('oauth_token');
		$oauth_verifier = HTTP::get('oauth_verifier');

		if( ! ( $oauth_token && $oauth_verifier)
			|| $_SESSION['oauth_token'] != $oauth_token ) {
			throw new \Exception('Oauth tokens does not match');
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
			throw new \Exception('Internal error while registering user');

		if( $create_user['first_time'] )
			$_SESSION['first_time'] = true;

		HTTP::redirect( $this->redirect_to );
	}
}
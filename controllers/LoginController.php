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
	\models\Users;
	
class LoginController {

	// the place to redirect after a success or error
	private $redirect_to = '';

	public function __construct( $action ) {
		$is_production = \Config::get('is_production');
		try {

			if( ! isset($_COOKIE['ta_session']) ) {
				throw new \ValidationException(
						'Cookies are needed to sign in'
					);
			}

			$this->$action();

		} catch ( \ProgrammerException $e ) {
			$message  =  $e->getMessage();
		} catch ( \VendorException $e ) {
			$message  = 'Error with ' . $e->vendor . ': ' . $e->getMessage();
		} catch ( \DBException $e ) {
			$message  = $e->getMessage() . ': '; // <- must say it where
			$message .= db()->error ? db()->error : '';
		} catch ( \ValidationException $e ) {
			$message  = $e->getMessage();
		} finally {
			if( isset($message) ) {
				if( $is_production ) {
					$_SESSION['login_error'] =
					'There was a problem while singing you in.' .
					' Please, try again.';
				} else {
					$_SESSION['login_error'] = $message;
				}
			}
			HTTP::redirect( $this->redirect_to ?: url() );
		}
	}

	private function signin() {
		$twitter = new Twitter();
		if( isset($_GET['back_to']) && is_string($_GET['back_to']) ) {
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
				) {
				$_SESSION['back_to'] = $back_to;
			}
		}

		$login_url = $twitter->get_login_url();

		$this->redirect_to = $login_url;
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
			throw new \ValidationException('No tokens were stored');
		}

		$denied = HTTP::get('denied');

		if( false === empty($denied) && $denied == $_SESSION['oauth_token'] ) {
			throw new \ValidationException('Request was denied');
		}

		$oauth_token 	= HTTP::get('oauth_token');
		$oauth_verifier = HTTP::get('oauth_verifier');

		if(    ! ( $oauth_token && $oauth_verifier )
			|| $_SESSION['oauth_token'] != $oauth_token
		) {
			throw new \ValidationException('Oauth tokens does not match');
		}

		$twitter = new Twitter(
				$_SESSION['oauth_token'],
				$_SESSION['oauth_token_secret']
			);

		unset($_SESSION['oauth_token']);
		unset($_SESSION['oauth_token_secret']);

		// if it fails, then it will itself throw an exception:
		$tokens = $twitter->tw->oauth(
					"oauth/access_token",
					array("oauth_verifier" => $oauth_verifier)
				);
		
		$create_user = Users::insert( array(
				'access_token'        => $tokens['oauth_token'],
				'access_token_secret' => $tokens['oauth_token_secret']
			)
		);

		if( $create_user['first_time'] ) {
			$_SESSION['first_time'] = true;
		}
	}
}
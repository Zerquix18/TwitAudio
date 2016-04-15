<?php
/**
* Class for Twitter manipulation
* 
* @author Zerquix18
*
**/
namespace application;

require dirname(__FILE__) . '/twitteroauth/autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth as TwitterOAuth;

class Twitter {
	const CONSUMER_KEY = 'jM8rVR9XiDRTuiV0qh7ULmi9b';
	const CONSUMER_KEY_SECRET = 
	'QKX2iCmKeKM6pOXFWYDdyIkqCNFjs9Jbf6T8QjcgpMopfaCUEp';
	public $tw;
	public function __construct($access_token = null,
								$access_token_secret = null
								) {
		$this->tw = new TwitterOAuth(
			self::CONSUMER_KEY,
			self::CONSUMER_KEY_SECRET,
			$access_token,
			$access_token_secret
		);
		$this->callback = url() . 'callback';

		if('www.twitaudio.com' !== $_SERVER['HTTP_HOST']) {
			/*
			* here the callback property can be overwritten safely
			* if you're in localhost, Twitter won't redirect to localhost
			* the solution is to redirect to a real url and then
			* change the URL in the browser bar
			* ex: arealurl123.com
			*/
			#$this->callback = 'http://arealurl123.com/callback';
		}
	}
	/**
	* Returns a string with the login URL
	*
	**/
	public function get_login_url() {
		if( null === $this->tw )
			return '';
		$request_token = $this->tw->oauth(
			'oauth/request_token',
			array('oauth_callback' => $this->callback)
		);
		if( array_key_exists('error', $request_token) )
			return '';
		$_SESSION['oauth_token'] 		= $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
		return $this->tw->url(
			'oauth/authorize',
			array('oauth_token' => $request_token['oauth_token'])
		);
	}
	/**
	* Posts a tweet a returns the ID
	*
	* @return string
	*
	**/
	public function tweet( $tweet, $reply_to = '' ) {
		$arr = array("status" => $tweet );
		if( ! empty($reply_to) )
			$arr['in_reply_to_status_id'] = $reply_to;

		$this->tweet = $this->tw->post('statuses/update', $arr );
		if( isset($this->tweet->errors, $this->tweet->error) )
			return '';
		return $this->tweet->id_str;
	}
}
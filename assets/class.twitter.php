<?php
/**
*
* Hai there
* @author Zerquix18
*
**/
require_once( PATH . INC . 'tw/TwitterOAuth.php' );

class Twitter {
	const CONSUMER_KEY = 'jM8rVR9XiDRTuiV0qh7ULmi9b';
	const CONSUMER_KEY_SECRET = 'QKX2iCmKeKM6pOXFWYDdyIkqCNFjs9Jbf6T8QjcgpMopfaCUEp';
	private $callback = 'https://twitaudio.com/callback.php';
	public $tw;
	public $id;
	public function __construct($access_token = null, $access_token_secret = null  ) {
		$this->tw = new TwitterOAuth(self::CONSUMER_KEY, self::CONSUMER_KEY_SECRET, $access_token, $access_token_secret);
	}
	public function getURL() {
		if( $this->tw === null ) return;
		$request_token = $this->tw->oauth('oauth/request_token', array('oauth_callback' => $this->callback) );
		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
		return $this->tw->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
	}
	public function callback($oauth_verifier) {
		return $this->tw->oauth("oauth/access_token", array("oauth_verifier" => $oauth_verifier));
	}
	private function get_error_code( $code ) {
		switch($code) {
			case 200:
			$error = __('200 - OK');
			break;
			case 304:
			$error = __('Error 304: Not Modified - There was no new data to return.');
			break;
			case 400:
			$error = __('Error 400: Bad Request - The request was invalid or cannot be otherwise served.');
			break;
			case 401:
			$error = __('Error 401: Unauthorized - Authentication credentials were missing or incorrect.');
			break;
			case 403:
			$error = __("Error 403: Forbidden - The request was understood, but it has been refused or access is not allowed.");
			break;
			case 404:
			$error = __("Error 404: Not found - The URI requested is invalid or the resource requested, such as a user, does not exists.");
			break;
			case 406:
			$error = __("Error 406: Not acceptable - Returned by the Search API when an invalid format is specified in the request.");
			break;
			case 410:
			$error = __("Error 410: Gone - This resource is gone.");
			break;
			case 420:
			$error = __("Error 420: Returned by the version 1 Search and Trends APIs when you are being rate limited.");
			break;
			case 422:
			$error = __("Error 422: Unprocessable Entity - Returned when an image uploaded to POST account / update_profile_banner is unable to be processed.");
			break;
			case 429:
			$error = __("Error 429: Too Many Requests - Returned in API v1.1 when a request cannot be served due to the application’s rate limit having been exhausted for the resource.");
			break;
			case 500:
			$error = __("Error 500: Internal Server Error - Something is broken. This is a Twitter error. Please, check Twitter Status.");
			break;
			case 502:
			$error = __("Error 502: Bad Gateway - Twitter is down or being upgraded.");
			break;
			case 503:
			$error = __("Error 503: Service Unavailable - The Twitter servers are up, but overloaded with requests. Try again later.");
			break;
			case 504:
			$error = __("Error 504: Gateway timeout - The Twitter servers are up, but the request couldn’t be serviced due to some failure within our stack. Try again later.");
			break;
			case 32:
			$error = __("Error: Could not authenticate you - Your call could not be completed as dialed.");
			break;
			case 34:
			$error = __("Error (404): Sorry, that page does not exist - The specified resource was not found.");
			break;
			case 68:
			$error = __("The Twitter REST API v1 is no longer active. Please update.");
			break;
			case 88:
			$error = __("Error: Rate limit exceeded - The request limit for this resource has been reached for the current rate limit window.");
			break;
			case 89:
			$error = __("Error: Invalid or expired token - The access token used in the request is incorrect or has expired. Used in API v1.1");
			break;
			case 64:
			$error = __("Error (403): Your account is suspended and is not permitted to access this feature");
			break;
			case 131:
			$error = __("Error (500): Internal error - Corresponds with an HTTP 500 - An unknown internal error occurred.");
			break;
			case 135:
			$error = __("Error (401): Could not authenticate you - Corresponds with a HTTP 401 - it means that your oauth_timestamp is either ahead or behind our acceptable range");
			break;
			case 187:
			$error = __("Error: Status is a duplicate - The status text has been Tweeted already by the authenticated account.");
			break;
			case 215:
			$error = __("Error (400): Bad authentication data - Typically sent with 1.1 responses with HTTP code 400. The method requires authentication but it was not presented or was wholly invalid.)");
			break;
			default:
			$error = __("Unknown error! ):");
		}
		return $error;
	}
	public function tweet( $tweet, $reply_to = '' ) {
		$arr =  array("status" => $tweet );
		if( ! empty($reply_to) )
			$arr['in_reply_to_status_id'] = $reply_to;
		$this->tweet = $this->tw->post('statuses/update', $arr );
		if( isset($this->tweet->errors) ) {
			$this->comp_error = true;
			$this->error = $this->obt_error( $this->tweet->errors[0]->code );
			return false;
		}
		$this->id = $this->tweet->id_str; //el ID del tweet
		return $this->tweet->id_str;
	}
}
if( is_logged() )
	$twitter = new Twitter($_USER->access_token, $_USER->access_token_secret);
else
	$twitter = new Twitter();
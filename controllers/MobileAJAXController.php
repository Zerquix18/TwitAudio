<?php
/**
*
* Handles all the requests for AJAX or Mobile API
* Every method here, except constructor and set_rules
* Are AJAX/Mobile API URLs
* so if you create a function, that function will
* answer to an URL (ajax|mob)/(get|post)/{function}
* You must use set_rules to establish the rules of the game.
* Please keep the methods ordered alphabetically.
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace controllers;
use \application\HTTP,
	\application\View,
	\models\Users,
	\models\Audios,
	\models\Search,
	\models\Payment;

class MobileAJAXController {

	private $via;

	private $method;

	public function __construct( $via, $method, $action ) {

		/**
		* Prevent calling a method in the wrong way
		* Example: User cannot call /ajax/post/cut using GET
		* That's illegal
		**/
		$method = strtoupper($method);
		if( $method !== $_SERVER['REQUEST_METHOD'] ) {
			View::exit_404();
		}
		// These methods should not be called
		if( in_array( $action, array('__construct', 'set_rules') ) ) {
			View::exit_404();
		}
		/**
		* The page called does not exist here
		* Ex: ajax/post/mecomounburrito
		**/
		if( ! method_exists($this, $action ) ) {
			View::exit_404();
		}

		$this->method  = $method;
		$this->via     = $via;
		$this->action  = $action;
		$is_production = \Config::get('is_production');

		try {
			$this->$action();
		} catch ( \ValidationException $e ) {
			$message     = $e->getMessage();
			$code        = $e->getCode();
			$options     = $e->options;
		} catch( \DBException $e ) {
			$message     = $e->getMessage() . ': ';
			$message    .= db()->error;
			$message    .= db()->query ? ' [ ' . db()->query . ' ]' : '';
			$code        = $e->getCode();
		} catch ( \VendorException $e ) {
			$message     = 'Error with ' . $e->vendor . ': ';
			$message    .= $e->getMessage();
			$code        = $e->getCode();
		} catch( \ProgrammerException $e ) {
			// because sometimes
			// one fucks it up
			$message     = $e->getMessage();
			$message    .= nl2br($e->getTraceAsString());
			$code        = $e->getCode();
		} finally {
			// if it was an error with the validation
			// and it can be shown to the user:
			$show_in_web = isset($options) &&
						   isset($options['show_in_web']) &&
						   $options['show_in_web'];
			if( ! $show_in_web && $is_production ) {
				// rewrite the message if it can't be shown to the user
				$message = 'There was a problem while processing your ' .
							'request.';
			}
			$result = array('success' => false, 'response' => $message);
			if( 0 != $code ) {
				$result['code'] = $code;
			}
			HTTP::result($result);
		}
	}
	/**
	*
	* Establish the rules of the game
	* Before each other method here,
	* Call this method and ask him
	* If your method can play and how to.
	* Options:
	* 'method' =>
	*	GET or POST, check that the URL matches the method used
	* Example:
	* /ajax/get/favorites => true, gets the favorites
	* /ajax/post/favorites => false, that's for getting data
	*
	* /ajax/get/audios => true, get the audios of a profile
	* /ajax/post/audios => false, get is the method.
	*
	* /ajax/post/upload => true, POST to upload audios
	* /ajax/get/upload => nope! that's to POST, not GET
	]
	* NOTE: A method can answer both if you separate it
	* with a |. Ex: "POST|GET", "GET|POST", "PUT|DELETE"
	* 
	* 'via' =>
	*	'mob xor ajax', establish the via, separated by commas,
	*    with no spaces
	* Example:
	* /mob/post/signin => yes, that's for signin in the mobile app
	* /ajax/post/signin => No! the web cannot respond to this.
	* 
	* /(ajax|mob)/post/play => yes, both can register plays
	*
	* 'require_login'
	*	=> true|false, establish if the user must be logged
	*	to access to that URL.
	* NOTE: if the via used is 'mob' then all the requests
	* will require login, except the signin one!
	**/
	public function set_rules( array $options ) {
		$default_options = array(
				'method'        => 'GET',
				'vias'          =>  'mob,ajax',
				'require_login' =>  false,
			);
		$options = array_merge($default_options, $options);
		/** set the method(s) **/
		$methods = explode("|", $options['method']);
		if( ! in_array($this->method, $methods) ) {
			View::exit_404();
		}
		/** set the vias **/
		$vias = explode(',', $options['vias'] );
		if( ! in_array($this->via, $vias) ) {
			View::exit_404();
		}

		// in the web we also return html
		// and it jquery returns error because the content
		// does not match with the headers
		if( 'mob' == $this->via ) {
			header('Content-Type: application/json');
		}

		if( $this->via !== 'mob' && ! $options['require_login'] ) {
			return;
		}

		if( 'ajax' == $this->via ) {
			if( ! is_logged() ) {
				HTTP::result( array(
						'success'  => false,
						'response' => 'Authorization required',
					)
				);
			}
			/* this is the most useless comment of this function */
		} else { 
			//any mobile request except signin requires login
			if( 'signin' !== $this->action ) {
				check_authorization();
			}
		}
	}

	/******************* HAPPY HACKING BITCHES ! *******************/

	/**
	*
	* Loads audios in the profile
	* Params:
	* 'user': the username
	* 'p'	: the page to load
	* Can be called from mobile and AJAX
	* Doesn't require login on the AJAX side
	*
	**/
	private function audios() {
		$this->set_rules( array(
				'method'        => 'GET',
				'vias'          => 'mob,ajax',
				'require_login' => false
			)
		);
		$user  = HTTP::get('user');
		if( ! $user && 'mob' == $this->via ) {
			$current_user = Users::get_current_user();
			$user = $current_user->id;
		}

		$page = HTTP::get('p');
		$page = HTTP::sanitize_page_number($page) ?: 1;

		if( ! $user ) {
			throw new \ValidationException(
				'Missing or wrong parameters',
				array('error_code' => 7)
			);
		}

		$user = Users::get( $user, array('id') );

		if( ! $user ) {
			throw new \ValidationException('User does not exist');
		}

		$result  = Audios::get_audios($user['id'], $page);
		// Mobile side:
		if( 'mob' == $this->via ) {
			HTTP::result( array('success' => true) + $result );
		}
		// AJAX side:
		$response = '';
		while( list(,$audio) = each($result['audios']) ) {
			$response .= View::get_partial('audio', $audio);
		}
		$response  = minify_html($response);
		$load_more = $result['load_more'];
		HTTP::result( array(
					'success'   => true,
					'response'  => $response,
					'load_more' => $result['load_more']
				)
			);

		// blow the roof of the place!
	}
	/**
	* Loads an individual audio
	* In the mobile side
	**/
	private function audio() {
		$this->set_rules( array(
				'method'	=> 'GET',
				'vias'		=> 'mob'
			)
		);
		$id = HTTP::get('id');
		$current_user = Users::get_current_user();

		if( ! $id ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}

		$audio = Audios::get($id);

		if( ! $audio ) {
			throw new \ValidationException(
					'This audio does not exist or is no longer available.'
				);
		}

		if( ! $current_user->can_listen($audio['user']['id']) ) {
			throw new \ValidationException(
					'You cannot listen to the audios of this user.'
				);
		}

		HTTP::result(array('success' => true) + $audio);
	}
	/**
	* Charges a user via stripe
	* Paypal is not supported yet
	**/
	private function charge() {
		$this->set_rules( array(
				// get will be supported to get the paypal url
				'method'        => 'POST',
				'via'           => 'mob,ajax',
				'require_login' => true,
			)
		);
		$method       = HTTP::post('method');
		$current_user = Users::get_current_user();
		if( $current_user->is_premium() ) {
			throw new \ValidationException('You are already premium.');
		}
		switch( $method ) {
			case "card":
				$token = HTTP::post('token');
				if( ! $token )
					throw new \ValidationException(
							'No token was specified'
						);
				$payment = new Payment('stripe', $current_user->id);
				$charge  = $payment->charge($token);
				if( ! $charge ) {
					throw new \ValidationException( $payment->error );
				}
				break;
			case "paypal":
				throw new \ValidationException('Paypal is not supported yet');
				break;
			default:
				throw new \ValidationException(
						'No right method was specified'
					);
		}
		HTTP::result( array(
				'success'       => true,
				'response'      => 'Thanks, you are now premium! Enjoy!',
				'premium_until' => $charge['premium_until']
			)
		);
	}
	/**
	*
	* Checks what effects were loaded already
	* Params:
	* 'id'	: The temporary ID stored in $_SESSION
	* see the upload option below to understand more.
	**/
	private function checkeffects() {
		$this->set_rules( array(
				'method'			=> 'GET',
				'vias'				=> 'mob,ajax',
				'require_login'		=> true,
			)
		);

		$id = HTTP::get('id');

		if( ! $id ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}
		if( ! isset($_SESSION[ $id ] ) ) {
			throw new \ValidationException(
					'Invalid ID'
				);
		}

		$loaded_effects = \application\Audio::get_finished_effects(
			$_SESSION[ $id ]['effects']
		);
		$loaded_effects_count = count($loaded_effects);

		$current_user = Users::get_current_user();

		$total          = count( $current_user->get_available_effects() );
		$are_all_loaded = $total === $loaded_effects_count;

		for($i = 0; $i < $loaded_effects_count; $i++) {
			/* replaces the 'file' key. Instead of a full path for backend
			* a full path for front-end. I mean https://...
			**/
			$loaded_effects[$i]['file'] = str_replace(
					DOCUMENT_ROOT . '/',
					url(),
					$loaded_effects[$i]['file']
				);
		}

		$return = array(
				'success'         => true,
				'loaded_effects'  => $loaded_effects,
				'are_all_loaded'  => $are_all_loaded
			);
		
		HTTP::result($return);
	}

	/**
	* Cuts an audio
	* Params:
	* 'id'		: The temporary id stored in $_SESSION
	* 'start'	: The second to start to cut or
	* 			  this format nn:nn (ex:0:34)
	* 'end'		: Same as 'start'
	*
	**/
	private function cut() {
		$this->set_rules( array(
				'method'        => 'POST',
				'vias'          => 'ajax',
				'require_login' => true,
			)
		);

		$id           = HTTP::post('id');
		$start        = HTTP::post('start');
		$end          = HTTP::post('end');
		$current_user = Users::get_current_user();

		if( ! ($id && $start && $end ) ) {
			throw new \ValidationException('Missing parameters');
		}
		if( ! isset($_SESSION[ $id ] ) ) {
			throw new \ValidationException(
					'Invalid ID'
				);
		}

		/** validate start **/
		if( ctype_digit($start) ) {
			$start = (int) $start;
		}else{ // if not a number, translate it to a number
			if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $start) ) {
				throw new \ValidationException('Start has a wrong format');
			}
			// 0 = mins, 1 = seconds
			$min_sec = explode(":", $start);
			$start = ( (int) $min_sec[0] * 60 ) + (int) $min_sec[1];
		}
		/** validate end **/
		if( ctype_digit($end) ) {
			$end = (int) $end;
		}else{
			if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $end) ) {
				throw new \ValidationException('End has a wrong format');
			}
			// 0 = mins, 1 = seconds
			$min_sec = explode(":", $end);
			$end = ( (int) $min_sec[0] * 60 ) + (int) $min_sec[1];
		}

		$difference = $end-$start;

		if( $start >= $end ) {
			throw new \ValidationException("Start can't be higher than end");
		}
		if( $difference > $current_user->get_limit('audio_duration') ) {
			throw new \ValidationException(
					'The difference between start and end is' .
					' higher than your current limit\'s.',
					array('show_in_web' => true)
				);
		}
		if( $difference < 1 ) {
			throw new \ValidationException(
					'The difference must be longer than 1 second',
					array('show_in_web' => true)
				);
		}

		$audio = new \application\Audio(
				$_SESSION[$id]['tmp_url'],
				array('validate' => false)
			);

		$new_audio = $audio->cut($start, $end);

		if( ! $new_audio ) {
			HTTP::result( array(
					'success'  => false,
					'response' => $audio->error,
				)
			);
		}

		$_SESSION[$id]['tmp_url']  = $new_audio;
		$_SESSION[$id]['duration'] =
									floor($audio->info['playtime_seconds']);

		$available_effects         = $current_user->get_available_effects();

		$_SESSION[$id]['effects']  =
			\application\Audio::apply_effects(
				$audio->audio,
				$available_effects
			);

		$total_effects = \application\Audio::get_effects();
		$effects = array();
		while( list($effect_name,$effect_name_public) = each($total_effects) ){
			if( in_array($effect_name, $available_effects) ) {
				$effects[] = array(
						'name'        => $effect_name,
						'name_public' => $effect_name_public
					);
			}
		}

		$tmp_url = url() . 'assets/tmp/' .
							last( explode('/', $audio->audio) );

		HTTP::result( array(
				'success' => true,
				'id'      => $id,
				'tmp_url' => $tmp_url,
				'effects' => $effects
			)
		);
	}
	/**
	* Deletes an audio
	* Params:
	* 'id' the ID of the audio to delete.
	**/
	private function delete() {
		$this->set_rules( array(
				'method'		=> 'POST',
				'vias'			=> 'mob,ajax',
				'require_login' => true
			)
		);

		$id           = HTTP::post('id');
		$current_user = Users::get_current_user();

		if( ! $id ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}

		$audio = Audios::get($id, array('id', 'user_id', 'audio_url') );

		if( ! $audio ) {
			throw new \ValidationException(
					'The audio you tried to delete does not exist or is no longer available.',
					array('show_in_web' => true)
				);
		}

		if( $audio['user']['id'] !== $current_user->id ) {
			throw new \ValidationException(
					'You are not the author of this audio'
				);
		}

		$delete = Audios::delete($id);

		HTTP::result( array(
				'success'   => true,
				'id'        => $id
			)
		);
	}

	/**
	*
	* Marks/Unmark as favorite an audio
	* Params:
	* 'id' 		: the id of the audio to favorite
	* 'action'	: the action (must be fav or unfav)
	**/
	private function favorite() {
		$this->set_rules( array(
				'method'		=> 'POST',
				'vias'			=> 'mob,ajax',
				'require_login' => true
			)
		);
		$id 	= HTTP::post('id');
		$action = HTTP::post('action');
		if( ! ($id && $action) ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 1)
				);
		}
		$audio = Audios::get($id, array('id', 'user_id', 'favorites') );
		if( ! $audio ) {
			throw new \ValidationException(
					'The audio you tried to favorite does not exist or is no longer available.',
					array('show_in_web' => true)
				);
		}

		$current_user = Users::get_current_user();

		if( ! $current_user->can_listen($audio['user']['id']) ) {
			throw new \ValidationException(
					'The audios of this users are private',
					array('show_in_web' => true)
				);
		}

		$count = $audio['favorites'];

		if( ! $audio['favorited'] && 'fav' == $action ) {
			Audios::register_favorite($audio['id']);
			$count += 1;
		}elseif( $audio['favorited'] && 'unfav' == $action ) {
			Audios::unregister_favorite($audio['id']);
			$count -= 1;
		}

		HTTP::result( array(
				'success' => true,
				'count'   => $count
			)
		);
	}
	/**
	*
	* Loads the favorites of an user
	* Params:
	* 'user'		: (optional) the username of the user
	* 'page'	: The page to load
	**/
	private function favorites() {
		$this->set_rules( array(
				'method'		=> 'GET',
				'vias'			=> 'mob,ajax',
				'require_login' => false
			)
		);
		$current_user = Users::get_current_user();
		$user         = HTTP::get('user');

		if( ! $user && 'mob' == $this->via ) {
			$user = $current_user->id;
		}

		$page = HTTP::get('p');
		$page = HTTP::sanitize_page_number($page) ?: 1;

		if( ! $user ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}

		$user = Users::get($user, array('id', 'favs_privacy') );

		if( ! $user ) {
			throw new \ValidationException('The user does not exist');
		}

		if(	   'public' !== $user['favs_privacy']
			|| ! is_logged()
			|| $current_user->id !== $user['id']
		) {
			throw new \ValidationException(
					'The favorites of this user are private'
				);
		}
		$result  = Audios::get_favorites($user['id'], $page);
		// Mobile side:
		if( 'mob' == $this->via ) {
			HTTP::result( array('success' => true) + $result );
		}
		// AJAX side:
		$response = '';
		while( list(,$audio) = each($result['audios']) ) {
			$response .= View::get_partial('audio', $audio);
		}
		$response = minify_html($response);
		HTTP::result( array(
				'success'   => true,
				'response'  => $response,
				'load_more' => $result['load_more']
			)
		);
	}
	/**
	* It will return the data for the home page
	*
	**/
	private function home() {
		$this->set_rules( array(
				'method'        => 'GET',
				'vias'          => 'mob',
				'require_login' => true,
			)
		);

		$data   = array(
				'recent_popular' => array(
					    'audios' => Audios::get_popular_audios()
				),
				'recent_audios'	 => array(
						'audios' => Audios::get_recent_audios_by_user()
				)
			);
		HTTP::result(array('success' => true) + $data);
	}
	/**
	* Will delete the sess_id from the table
	* In the mobile side
	**/
	private function logout() {
		$this->set_rules( array(
				'method' 	    => 'POST',
				'vias' 		    => 'mob',
				'require_login' => true,
			)
		);
		// ↓ declared in application/sessions.php
		session_logout();
	}
	/**
	* Will register a play from an IP
	* Params:
	* 'id'	: The ID of the audio to register
	*
	**/
	private function play() {
		$this->set_rules( array(
				'method'		=> 'POST',
				'vias'			=> 'mob,ajax',
				'require_login' => false
			)
		);

		$id = HTTP::post('id');

		if( ! $id ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}

		$audio  = Audios::get($id, array('plays', 'reply_to') );
		if( ! $audio ) {
			throw new \ValidationException(
					'The audio does not exist'
				);
		}
		if( $audio['reply_to'] ) {
			throw new \ValidationException(
					'Cannot register a play in a reply'
				);
		}

		$count         = $audio['plays'];
		$register_play = Audios::register_play($id);

		if( $register_play ) {
			$count += 1;
		}

		HTTP::result( array(
				'success' => true,
				'count'   => $count
			)
		);
	}
	/**
	*
	* Posts the temporary audio
	* Params:
	* 'id'			: The temporary ID stored in $_SESSION
	* 'description' : The description of the ID
	* 's_twitter'	: 1 or 0 to send or not to Twitter
	* 'effect'		: The effect to apply
	*
	**/
	private function post() {
		$this->set_rules( array(
				'method'		=> 'POST',
				'vias'			=> 'mob,ajax',
				'require_login' => true,
			)
		);
		$id 		 	 = HTTP::post('id');
		$description 	 = HTTP::post('description');
		$send_to_twitter = HTTP::post('s_twitter');
		$effect 		 = HTTP::post('effect');
		$current_user    = Users::get_current_user();

		if( ! ($id && $effect) ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}

		if( ! isset($_SESSION[ $id ] ) ) {
			throw new \ValidationException('Invalid ID');
		}

		if( $_SESSION[$id]['duration'] >
							$current_user->get_limit('audio_duration') ) {
			throw new \ValidationException(
					"The duration of the audio is longer" .
					" than your current limit's"
				);
		}

		if( mb_strlen( $description, 'utf-8' ) > 200 ) {
			throw new \ValidationException(
					'Description can\'t be longer than 200 characters',
					array('show_in_web' => true)
				);
		}

		$available_effects = $current_user->get_available_effects();

		if(    'original' != $effect
			&& ! in_array($effect, $available_effects ) ) {
			$effect = 'original'; // no hack!
		}

		while( file_exists(
			DOCUMENT_ROOT . '/assets/audios/' .
			$new_name = substr( md5( uniqid() . rand(1,100) ), 0, 26 ) . '.mp3'
			)
		);

		if( 'original' !== $_POST['effect'] ) {
			$tmp_url =
			$_SESSION[ $id ]['effects'][ $_POST['effect'] ]['filename'];
		} else {
			$tmp_url = $_SESSION[ $id ]['tmp_url'];
		}

		rename(
			$tmp_url,
			DOCUMENT_ROOT . '/assets/audios/' . $new_name
		);

		Audios::insert( array(
				'audio_url'       => $new_name,
				'description'     => $description,
				'duration'        => $_SESSION[$id]['duration'],
				'is_voice'        => $_SESSION[$id]['is_voice'],
				'send_to_twitter' => ( '1' === $send_to_twitter ),
			)
		);

		\application\Audio::clean_tmp($_SESSION[$id]);
		unset($_SESSION[$id]);

		HTTP::result( array(
				'success'   => true,
				'response'  => 'Audio posted successfully!'
			)
		);
	}
	/**
	*
	* Loads the profile in the mobile side
	* This will only load the basic info, not the audios/favorites
	*
	* Params:
	* 'user' : (optional) The username of the user to load
	**/
	private function profile() {
		$this->set_rules( array(
				'method'	=> 'GET',
				'vias'		=> 'mob,ajax'
			)
		);
		$user  = HTTP::get('user');
		if( ! $user ) {
			$current_user = Users::get_current_user();
			$user = $current_user->user;
		}

		$user_info = Users::get($user);

		if( ! $user_info ) {
			throw new \ValidationException('Requested user does not exist');
		}

		HTTP::result(array('success' => true) + $user_info);
	}
	/**
	* Loads the replies of an audio
	*
	* Params:
	* 'id|q'	: The ID of the audio to load the replies
	* 'page'	: The page to load
	**/ 
	private function replies() {
		$this->set_rules( array(
				'method'		=> 'GET',
				'vias'			=> 'mob,ajax',
				'require_login' => false,
			)
		);
		$audio_id     =  HTTP::get('id');
		$page         =  HTTP::get('p');
		$page         =  HTTP::sanitize_page_number($page) ?: 1;
		$current_user = Users::get_current_user();

		if( ! $audio_id ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}
		$audio = Audios::get( $audio_id, array('reply_to', 'user_id', 'id') );
		if( ! $audio ) {
			throw new \ValidationException(
					'The audio you request does not exist or is no longer available',
					array('show_in_web' => true)
				);
		}
		if( $audio['reply_to'] ) {
			throw new \ValidationException(
					'Replies does not have replies'
				);
		}
		if( ! $current_user->can_listen($audio['user']['id']) ) {
			throw new \ValidationException(
					"You cannot listen to the audios of this user",
					array('show_in_web')
				);
		}
		$replies = Audios::get_replies($audio_id, $page);
		/** LINKED REPLIES **/
		// here be dragons...
		if(    'ajax' == $this->via
			&& $reply_id = HTTP::get('reply_id')
			) {
			// was the param sent?
			$reply = Audios::get($reply_id, array('reply_to') );

			if( $reply && $reply['reply_to'] == $audio_id ) {
				// if the reply exists and it's replying to this audio
				$all_replies = $replies;
				$replies = array( // move everything
						'audios'    => array(),
						'load_more' => $all_replies['load_more'],
						'page'      => $all_replies['page'],
						'total'     => $all_replies['total']
					);
				$count_replies = count($all_replies['audios']);
				// re-fill 'audios' key'
				for( $i = 0; $i < $count_replies; $i++ ) {
					if( $all_replies['audios'][$i]['id'] == $reply_id ) {
						/** don't add the linked reply, cuz it was added first **/
						continue;
					}
					$replies['audios'][] = $all_replies['audios'][$i];
				}
			}
		}
		/** / LINKED REPLIES **/

		// Mobile side:
		if( 'mob' == $this->via ) {
			HTTP::result( array('success' => true) + $replies );
		}
		// AJAX side:
		$response = '';
		while( list(,$audio) = each($replies['audios']) ) {
			$response .= View::get_partial('audio', $audio);
		}
		$response = minify_html($response);

		HTTP::result( array(
				'success'   => true,
				'response'  => $response,
				'load_more' => $replies['load_more']
			)
		);
	}

	/**
	*
	* Replies to an audio
	*
	* Params:
	* 'id'			: The ID of the audio to reply
	* 'reply'		: The reply text
	* 's_twitter'	: 1 or 0 to send to twitter
	* 
	**/
	private function reply() {
		$this->set_rules( array(
				'method'        => 'POST',
				'vias'          => 'mob,ajax',
				'require_login' => true
			)
		);
		$audio_id        = HTTP::post('id');
		$reply           = HTTP::post('reply');
		$send_to_twitter = HTTP::post('s_twitter');
		$current_user    = Users::get_current_user();

		if( ! ($audio_id && $reply) ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}
		$audio = Audios::get(
				$audio_id,
				array('reply_to', 'twitter_id', 'user_id')
			);
		if( ! $audio ) {
			throw new \ValidationException(
					'The audio you try to reply does not exist or is no longer available',
					array('show_in_web' => true)
				);
		}
		if( $audio['reply_to'] ) {
			throw new \ValidationException(
					'You cannot reply a reply'
				);
		}

		if( ! $current_user->can_listen($audio['user']['id']) ) {
			throw new \ValidationException(
					'You cannot listen to the audios of this user'
				);
		}

		$reply_length = mb_strlen($reply, 'utf-8');

		if( 0 === $reply_length ) {
			throw new \ValidationException(
					'The reply cannot be empty',
					array('show_in_web' => true)
				);
		}
		if( $reply_length > 200 ) {
			throw new \ValidationException(
					'Reply cannot be longer than 200 characters',
					array('show_in_web' => true)
				);
		}

		$reply = Audios::insert( array(
				'reply_to'        => $audio_id,
				'reply'           => $reply,
				'send_to_twitter' => '1' === $send_to_twitter,
				'user_id'         => $audio['user']['id'],
				'twitter_id'      => $audio['twitter_id']
			)
		);
		// Mobile SIDE:
		if( 'mob' == $this->via ) {
			HTTP::result(array('success' => true) + $reply);
		}
		// AJAX side:
		$response = View::get_partial('audio', $reply);
		$response = minify_html($response);
		HTTP::result( array(
				'success'  => true,
				'response' => $response
			)
		);
	}

	/**
	*
	* Searches audios/users
	*
	* Params:
	* 'q'		: The search criteria
	* 't'		: The type of search (a=audios,u=users)
	* 'o'		: The order of the search (d=date,p=plays)
	* 'p'		: The page
	*
	**/
	private function search() {
		$this->set_rules( array(
				'method'		=> 'GET',
				'vias'			=> 'mob,ajax',
				'require_login' => false
			)
		);
		$query = HTTP::get('q');
		$type  = HTTP::get('t');
		$order = HTTP::get('o');
		$page  = HTTP::get('p');
		$page  = HTTP::sanitize_page_number($page) ?: 1;

		if( ! $query )
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);

		$result = Search::do_search( array(
				'query'		=>	$query,
				'type'		=>  $type,
				'order'		=>  $order,
				'page' 		=>  $page
			)
		);
		//Mobile Side:
		if( 'mob' == $this->via ) {
			HTTP::result( array('success' => true) + $result);
		}
		//AJAX Side:

		$response = '';
		$partial  = $result['type'] == 'a' ? 'audio' : 'user';

  		while( list(,$audio) = each($result['audios']) ) {
  			$response .= View::get_partial($partial, $audio);
  		}
  		$response = minify_html($response);

  		HTTP::result( array(
  				'success'   => true,
  				'response'  => $response,
  				'load_more' => $result['load_more']
  			)
  		);
	}
	/**
	* Updates the settings
	* The params must be 1 or 0
	* If they aren't sent or aren't 1 or 0
	* The defaults will be taken
	**/
	private function settings() {
		$this->set_rules( array(
				'method'		=> 'POST|GET',
				'vias'			=> 'mob,ajax',
				'require_login' => true,
			)
		);
		$current_user = Users::get_current_user();
		if( 'GET' == $this->method ) {
			// to get the settings:
			HTTP::result( array(
					'success'        => true,
					'audios_privacy' => $current_user->audios_privacy,
					'favs_privacy'   => $current_user->favs_privacy,
					'date_added'     => $current_user->date_added
				)
			);
		}

		// to update the settings:

		$favs_privacy   = HTTP::post('favs_privacy');
		$audios_privacy = HTTP::post('audios_privacy');
		

		if( ! in_array( $favs_privacy, array('public','private') ) ) {
			throw new \ValidationException(
					'favs privacy must be public or private'
				);
		}
		if( ! in_array( $audios_privacy, array('public','private') ) ) {
			throw new \ValidationException(
					'audios privacy must be public or private'
				);
		}
		$result = $current_user->update_settings( array(
				'audios_privacy'     => $audios_privacy,
				'favs_privacy'       => $favs_privacy
			)
		);

		HTTP::result( array(
				'success'   => true,
				'response'  => 'Settings updated successfully!',
			)
		);
	}
	/**
	*
	* Signins an user in the mobile side
	*
	* Params:
	* 'access_token'
	* 'access_token_secret'
	**/
	private function signin() {
		$this->set_rules( array(
				'method'		=> 'POST',
				'vias'			=> 'mob'
			)
		);
		$access_token           = HTTP::post('access_token');
		$access_token_secret    = HTTP::post('access_token_secret');

		if( ! ($access_token && $access_token) ) {
			throw new \ValidationException(
					'Missing parameters',
					array('error_code' => 7)
				);
		}
		$create_user = Users::insert( array(
				'access_token'        => $access_token,
				'access_token_secret' => $access_token_secret
			)
		);

		if( ! $create_user ) {
			throw new \ValidationException(
					'Error while logging you in'
				);
		}

		HTTP::result(array('success' => true) + $create_user);
	}
	/**
	* Uploads an audio
	* And stores it in the tmp/ directory
	* And saves all the data in the $_SESSION var
	* Params:
	* 'up_file'		: If it was an uploaded file in the web, up_file
	*				will be the file uploaded. In mobile, this will the file
	* 'bin'			: Instead, if it was a voice note in the web,
	* 				the binary will come encoded in base64
	* 'is_voice'	: 1 or 0
	**/
	private function upload() {
		$this->set_rules( array(
				'method'		=> 'POST',
				'vias'			=> 'mob,ajax',
				'require_login' => true
			)
		);
		$current_user = Users::get_current_user();
		$file_limit   = $current_user->get_limit('file_upload');

		if( 'mob' === $this->via ) {
			$is_voice = true; #always gna be true cuz it doesn support uploads
		} else {
			$is_voice = isset($_POST['bin']) && 'mob' !== $this->via;
		}

		if( isset($_POST['bin']) && ! empty($_FILES['up_file']['name']) ) {
			/** someone is tryna trick**/
			throw new \ValidationException(
					'You cannot send both bin and up_file!'
				);
		}
		/**
		*
		* To date, mobile and web work different
		* Mobile does not support files, only recorded,
		* which are uploaded with the 'up_file' param.
		* Web sends the binary of a recorded audio with the browser
		* as 'bin' in base64.
		* But an uploaded file is sent with the param 'up_file'
		* If you wonder why is it like this, read the Javascript :)
		*
		**/
		if( 'mob' === $this->via || ! $is_voice ) {
			/** validates uploaded file **/
			if( empty( $_FILES['up_file'] )
			 || is_array( $_FILES['up_file']['name'] ) ) {
				throw new \ValidationException(
						'Missing parameters',
						array('error_code' => 7)
					);
			}

			if( isset($_FILES['up_file']["error"] )
			 		&& $_FILES['up_file']["error"] != 0 ) {
				throw new \ValidationException(
						'Error code: ' . $_FILES['up_file']['error']
					);
			}

			$format = last( explode('.', $_FILES['up_file']['name']) );

			if( ! in_array(
				strtolower($format),
				\application\Audio::$allowed_formats
				) ) {
				/*
				* this is just a kick validation
				* the real way to know the format
				* is by checking the content inside
				*/
				throw new \ValidationException(
						'The format of the uploaded audio is not allowed',
						array('show_in_web' => true)
					);
			}

			$file_size = ( ( $_FILES['up_file']['size'] / 1024) / 1024);

			if( $file_size > $file_limit ) {
	 			throw new \ValidationException(
	 					sprintf(
	 						"The file size is greater than your current limit's, %d mb",
	 						$file_limit
	 					),
	 					array('show_in_web' => true)
	 				);
			}
			$move = move_uploaded_file(
					$_FILES['up_file']['tmp_name'],
					$file = DOCUMENT_ROOT . // <- it's a point!
					'/assets/tmp/' . uniqid() . '.' . $format
				);
			if( ! $move ) {
				throw new \ValidationException(
						'Could not move file'
					);
			}
		} elseif( $is_voice ) {
			/** validate the binary uploaded as base64 **/
			$binary = $_POST['bin'];
			$binary = substr($binary, strpos($binary, ",") + 1);

			if( ! ( $binary = base64_decode($binary, true ) ) ) {
				throw new \ValidationException(
						'Binary is corrupt'
					);		
			}
			file_put_contents(
				$file = DOCUMENT_ROOT .
					'/assets/tmp/' . uniqid() . '.mp3',
				$binary
			);
			$file_size = ( filesize($file) / 1024 ) / 1024;
			if( $file_size > $file_limit ) {
				/** someone could upload a b64 with a very high filesize */
	 			throw new \ValidationException(
	 					sprintf(
	 						"The file size is greater than your current limit's, %d mb",
	 						$file_limit
	 					)
	 				);
			}
		} else {
			throw new \ValidationException(
					'jah this must never happen'
				);
		}

		/** Now, $file needs a deep validation :) **/
		$audio = new \application\Audio($file, array(
			'validate'         => true,
			'max_duration'     => $current_user->get_limit('audio_duration'),
			'is_voice'         => $is_voice,
			'decrease_bitrate' => ! $current_user->is_premium(),
			)
		);
		// 3 is the error code when audio needs to be cut.
		if( $audio->error && $audio->error_code != 3 ) {
			throw new \ValidationException($audio->error);
		}
		
		$id = uniqid();
		// saves some info
		$_SESSION[$id] = array(
			'tmp_url'  => $audio->audio,
			'is_voice' => $is_voice,
			'duration' => floor($audio->info['playtime_seconds']),
		);

		#needs cut:
		if( $audio->error && $audio->error_code == 3 )
			HTTP::result( array(
					'success'  => false,
					'response' => $audio->error,
					'id'       => $id,
					'tmp_url'  =>
					url() . 'assets/tmp/'. last( explode('/', $audio->audio) )
				)
			);

		$available_effects        = $current_user->get_available_effects();
		$_SESSION[$id]['effects'] = \application\Audio::apply_effects(
				$audio->audio,
				$available_effects
		);

		/**
		* send the available effects and its names
		* so we could display "loading..."
		* and show the effects that are loading
		**/
		$total_effects = \application\Audio::get_effects();
		$effects = array();
		while( list($effect_name,$effect_name_public) = each($total_effects) ){
			if( in_array($effect_name, $available_effects) ) {
				$effects[] = array(
						'name'        => $effect_name,
						'name_public' => $effect_name_public
					);
			}
		}

		HTTP::result( array(
				'success'   => true,
				'id'        => $id,
				'tmp_url'   =>
				url() . 'assets/tmp/'. last( explode('/', $audio->audio) ),
				'effects'   => $effects,
			)
		);
	} // end upload
} // end class
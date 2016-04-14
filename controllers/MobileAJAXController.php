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

# without this the life would be hollow
use \application\HTTP;
use \application\View;
use \application\MobileAJAXException;

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
		if( $method !== $_SERVER['REQUEST_METHOD'] )
			View::exit_404();

		
		// These methods should not be called
		if( in_array( $action, array('__construct', 'set_rules') ) )
			View::exit_404();

		/**
		* The page called does not exist here
		* Ex: ajax/post/mecomounburrito
		**/
		if( ! method_exists($this, $action ) )
			View::exit_404();

		$this->method = $method;
		$this->via    = $via;
		$this->action = $action;

		try {
			$this->$action();
		} catch ( MobileAJAXException $e ) {
			$e->print_result( $this->via );
		} catch ( \Exception $e ) {
			exit('Something terrible happened');
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
		global $_USER;
		$default_options = array(
				'method'        => 'GET',
				'vias'          =>  'mob,ajax',
				'require_login' =>  false,
			);
		$options = array_merge( $default_options, $options );
		/** set the method(s) **/
		$methods = explode("|", $options['method']);
		if( ! in_array( $this->method, $methods ) )
			View::exit_404();
		/** set the vias **/
		$vias = explode(',', $options['vias'] );
		if( ! in_array( $this->via, $vias) )
			View::exit_404();

		// in the web we also return html
		// and it jquery returns error because the content
		// does not match with the headers
		if( 'mob' == $this->via )
			header('Content-Type: application/json');

		if( $this->via !== 'mob' && ! $options['require_login'] )
			return;

		if( 'ajax' == $this->via ) {
			if( ! is_logged() ) {
				HTTP::result( array(
						'success'  => false,
						'response' => __('Authorization required'),
					)
				);
			}
			/* this is the most useless comment of this function */
		} else { 
			//any mobile request except signin requires login
			if( 'signin' !== $this->action )
				check_authorization();
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
		$users = new \models\User();
		$param = 'mob' == $this->via ? 'user' : 'q';
		$user  = HTTP::get( $param );
		if( empty($user) && 'mob' == $this->via ) {
			$current_user = $users->get_current_user();
			$user = $current_user->id;
		}

		$page = HTTP::get('p');
		$page = HTTP::sanitize_page_number($page) ?: 1;

		if( empty($user) )
			throw new MobileAJAXException(
				'Missing or wrong parameters',
				array('error_code' => 7)
			);

		$info = $users->get_user_info($user, 'id');
		if( empty($info) )
			throw new MobileAJAXException('User does not exist');

		$user_id = $info['id'];

		$audios = new \models\Audio();
		$result = $audios->load_audios($user_id, $page);
		// Mobile side:
		if( 'mob' == $this->via )
			HTTP::result(array('success' => true) + $result);
		// AJAX side:
		while( list(,$audio) = each($result['audios']) )
			View::display_audio( $audio );
		if( $result['load_more'] )
			View::load_more('audios', $result['page'] + 1);

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
		$audios = new \models\Audio();
		$current_user = (new \models\User())->get_current_user();

		if( empty($id) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);

		$audio = $audios->get_audio_info(
				$id,
				'id,user,audio,reply_to,description,time,plays,favorites,duration'
			);
		if( empty($audio) )
			throw new MobileAJAXException(
					__('This audio does not exist or is no longer available.')
				);

		if( ! $current_user->can_listen( $audio['user']['id'] ) )
			throw new MobileAJAXException(
					__('You cannot listen to the audios of this user.')
				);

		HTTP::result( array('success' => true) + $audio );
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

		if( empty($id) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);

		if( ! isset($_SESSION[ $id ] ) )
			throw new MobileAJAXException(
					'Invalid ID'
				);


		$loaded_effects = \application\Audio::get_finished_effects(
			$_SESSION[ $id ]['effects']
		);
		$loaded_effects_count = count($loaded_effects);

		$current_user = (new \models\User)->get_current_user();

		$are_all_loaded =
		count( $current_user->get_available_effects() )
						=== $loaded_effects_count;

		for($i = 0; $i < $loaded_effects_count; $i++) {
			/* replaces the 'file' key. Instead of a full path for backend
			* a full path for front-end. I mean https://...
			**/
			$loaded_effects[$i]['file'] = str_replace(
					$_SERVER['DOCUMENT_ROOT'] . '/',
					url(),
					$loaded_effects[$i]['file']
				);
		}

		$return = array(
				'success'         => true,
				'loaded_effects'  => $loaded_effects,
				'are_all_loaded'  => $are_all_loaded
			);
		
		HTTP::result( $return );
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
		$id     = HTTP::post('id');
		$start  = HTTP::post('start');
		$end    = HTTP::post('end');
		$users  = new \models\User();
		$current_user = $users->get_current_user();

		if( ! ($id && $start && $end ) )
			throw new MobileAJAXException('Missing parameters');

		if( ! isset($_SESSION[ $id ] ) )
			throw new MobileAJAXException(
					'Invalid ID'
				);

		/** validate start **/
		if( ctype_digit($start) ) {
			$start = (int) $start;
		}else{ // if not a number, translate it to a number
			if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $start) )
				throw new MobileAJAXException('Start has a wrong format');
			// 0 = mins, 1 = seconds
			$min_sec = explode(":", $start);
			$start = ( (int) $min_sec[0] * 60 ) + (int) $min_sec[1];
		}
		/** validate end **/
		if( ctype_digit($end) ) {
			$end = (int) $end;
		}else{
			if( ! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $end) )
				throw new MobileAJAXException('End has a wrong format');
			// 0 = mins, 1 = seconds
			$min_sec = explode(":", $end);
			$end = ( (int) $min_sec[0] * 60 ) + (int) $min_sec[1];
		}
		$difference = $end-$start;

		if( $start >= $end )
			throw new MobileAJAXException("Start can't be higher than end");

		if( $difference > $current_user->get_limit('audio_duration') )
			throw new MobileAJAXException(
					'The difference between start and end is' .
					' higher than your current limit\'s.',
					array('show_in_web' => true)
				);

		if( $difference < 1 )
			throw new MobileAJAXException(
					'The difference must be longer than 1 second',
					array('show_in_web' => true)
				);

		$audio = new \application\Audio(
				$_SESSION[$id]['tmp_url'],
				array('validate' => false)
			);

		$new_audio = $audio->cut( $start, $end );

		if( empty($new_audio) )
			HTTP::result( array(
					'success'  => false,
					'response' => $audio->error,
				)
			);

		$_SESSION[$id]['tmp_url']  = $new_audio;
		$_SESSION[$id]['duration'] =
									floor($audio->info['playtime_seconds']);

		$available_effects = $current_user->get_available_effects();

		$_SESSION[$id]['effects'] =
			\application\Audio::apply_effects(
				$audio->audio,
				$available_effects
			);

		$total_effects = \application\Audio::get_effects();
		$effects = array();
		while( list(,$effect) = each($available_effects) )
			$effects[ $effect ] = $total_effects[ $effect ];

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
		$id = HTTP::post('id');
		$audios = new \models\Audio();
		$current_user = ( new \models\User() )->get_current_user();

		if( ! $id )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);
		$audio = $audios->get_audio_info($id, 'id,user,audio');
		if( $audio )
			throw new MobileAJAXException(
					__('The audio you tried to delete does not exist or is no longer available.'),
					array('show_in_web' => true)
				);

		if( $audio['user']['id'] !== $current_user->id )
			throw new MobileAJAXException(
					'You are not the author of this audio'
				);

		$delete = $audios->delete( $audio );

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
		$audios = new \models\Audio();
		if( ! ($id && $action) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 1)
				);
		$audio = $audios->get_audio_info($id, 'id,user,favorites');
		if( ! $audio )
			throw new MobileAJAXException(
					__('The audio you tried to favorite does not exist or is no longer available.'),
					array('show_in_web' => true)
				);

		$current_user = ( new \models\User() )->get_current_user();

		if( ! $current_user->can_listen( $audio['user']['id'] ) )
			throw new MobileAJAXException(
					__('The audios of this users are private'),
					array('show_in_web' => true)
				);

		$count = $audio['favorites'];
		if( ! $audio['favorited'] && 'fav' == $action ) {
			$audios->favorite( $audio['id'] );
			$count += 1;
		}elseif( $audio['favorited'] && 'unfav' == $action ) {
			$audios->unfavorite( $audio['id'] );
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
		$users = new \models\User();
		$current_user = $users->get_current_user();
		$param = 'mob' == $this->via ? 'user' : 'q';
		$user  = HTTP::get( $param );
		if( empty($user) && 'mob' == $this->via ) {
			$user = $current_user->id;
		}

		$page = HTTP::get('p');
		$page = HTTP::sanitize_page_number($page) ?: 1;

		if( empty($user) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);

		$info = $users->get_user_info( $user, 'id,favs_public');

		if( empty($info) )
			throw new MobileAJAXException('The user does not exist');

		if( ! $info['favs_public'] || ! is_logged()
								   || $current_user->id !== $info['id'] ) {
			throw new MobileAJAXException(
					'The favorites of this user are private'
				);
		}
		$user_id = $info['id'];

		$audios = new \models\Audio();
		$result = $audios->load_favorites( $user_id, $page );
		// Mobile side:
		if( 'mob' == $this->via )
			HTTP::result( array('success' => true) + $result );
		// AJAX side:
		while( list(,$audio) = each($result['audios']) )
			View::display_audio( $audio );
		if( $result['load_more'] )
			View::load_more('audios', $result['page'] + 1 );
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
		$audios = new \models\Audio();
		$data = array(
				'recent_popular'	=> array(
					    'audios'    => $audios->get_popular_audios()
				),
				'recent_audios'		=> array(
						'audios'    => $audios->get_recent_audios_by_user()
				)
			);
		HTTP::result( array('success' => true) + $data );
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
		if( empty($id) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);

		$audios = new \models\Audio();
		$audio  = $audios->get_audio_info( $id, 'plays,reply_to');
		if( empty($audio) )
			throw new MobileAJAXException(
					'The audio does not exist'
				);

		if( 0 != $audio['reply_to'] )
			throw new MobileAJAXException(
					'Cannot register a play in a reply'
				);

		$count = $audio['plays'];
		$register_play = $audios->register_play( $id );

		if( $register_play )
			$count += 1;

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
		$current_user    = (new \models\User())->get_current_user();

		if( ! ( $id && $effect) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);

		if( ! isset($_SESSION[ $id ] ) )
			throw new MobileAJAXException('Invalid ID');

		if( $_SESSION[$id]['duration'] >
							$current_user->get_limit('audio_duration') )
			throw new MobileAJAXException(
					"The duration of the audio is longer" .
					" than your current limit's"
				);

		if( mb_strlen( $description, 'utf-8' ) > 200 )
			throw new MobileAJAXException(
					'Description can\'t be longer than 200 characters',
					array('show_in_web' => true)
				);

		$available_effects = $current_user->get_available_effects();

		if( 'original' != $effect &&
			! in_array($effect, $available_effects ) )
			$effect = 'original'; // no hack!

		while( file_exists(
			$_SERVER['DOCUMENT_ROOT'] . '/assets/audios/' .
			$new_name = substr( md5( uniqid() . rand(1,100) ), 0, 26 ) . '.mp3'
			)
		);

		if( 'original' !== $_POST['effect'] )
			$tmp_url =
			$_SESSION[ $id ]['effects'][ $_POST['effect'] ]['filename'];
		else
			$tmp_url = $_SESSION[ $id ]['tmp_url'];

		rename(
			$tmp_url,
			$_SERVER['DOCUMENT_ROOT'] . '/assets/audios/' . $new_name
		);

		$audios = new \models\Audio();
		$audios->create_audio( array(
				'audio_url'   => $new_name,
				'description' => $description,
				'duration'    => $_SESSION[$id]['duration'],
				'is_voice'    => (bool) $_SESSION[$id]['is_voice'],
				'send_to_twitter' => ( '1' === $send_to_twitter ),
			)
		);

		\application\Audio::clean_tmp( $_SESSION[$id] );
		unset( $_SESSION[$id] );

		HTTP::result( array(
				'success'   => true,
				'response'  => __('Audio posted successfully!')
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
		$users = new \models\User();
		$user  = HTTP::get('user');
		if( empty($user) ) {
			$current_user = $users->get_current_user();
			$user = $current_user->user;
		}

		$user_info = $users->get_user_info(
				$user,
				'id,user,name,avatar,bio,verified,favs_public,audios_public'
			);

		if( empty($user_info) )
			throw new MobileAJAXException('Requested user does not exist');

		HTTP::result( array('success' => true) + $user_info );
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
		if( 'mob' == $this->via )
			$audio_id = HTTP::get('id');
		else
			$audio_id = HTTP::get('q');

		$page = HTTP::get('p');
		$page = HTTP::sanitize_page_number($page) ?: 1;
		$audios = new \models\Audio();
		$current_user = ( new \models\User() )->get_current_user();

		if( empty($audio_id) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);
		$audio = $audios->get_audio_info(
				$audio_id,
				'reply_to,user,id'
			);
		if( empty($audio) )
			throw new MobileAJAXException(
					__('The audio you request does not exist or is no longer available'),
					array('show_in_web' => true)
				);

		if( $audio['reply_to'] != '0' )
			throw new MobileAJAXException(
					'Replies does not have replies'
				);

		if( ! $current_user->can_listen( $audio['user']['id'] ) )
			throw new MobileAJAXException(
					__("You cannot listen to the audios of this user"),
					array('show_in_web')
				);

		$replies = $audios->load_replies( $audio_id, $page );
		/** LINKED REPLIES **/
		// here be dragons...
		if( 'ajax' == $this->via
			&& $reply_id = HTTP::get('reply_id') ) { // was the param sent?
			$reply = $audios->get_audio_info(
					$reply_id,
					'reply_to'
				);
			if( ! empty($reply) && $reply['reply_to'] == $audio_id ) {
				// reply exists and it's replying to this audio
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
		if( 'mob' == $this->via )
			HTTP::result( array('success' => true) + $replies );
		// AJAX side:
		while( list(,$audio) = each($replies['audios']) )
			View::display_audio( $audio );
		if( $replies['load_more'] )
			View::load_more('audios', $replies['page'] + 1 );
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
		$audios          = new \models\Audio();
		$current_user    = (new \models\User())->get_current_user();

		if( ! ($audio_id && $reply) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);
		$audio = $audios->get_audio_info(
				$audio_id,
				'reply_to,tw_id,user'
			);
		if( ! $audio )
			throw new MobileAJAXException(
					__('The audio you try to reply does not exist or is no longer available'),
					array('show_in_web' => true)
				);

		if( $audio['reply_to'] != '0' )
			throw new MobileAJAXException(
					'You cannot reply a reply'
				);

		if( ! $current_user->can_listen( $audio['user']['user'] ) )
			throw new MobileAJAXException(
					'You cannot listen to the audios of this user'
				);

		$reply_length = mb_strlen($reply, 'utf-8');
		if( 0 === $reply_length )
			throw new MobileAJAXException(
					__('The reply cannot be empty'),
					array('show_in_web' => true)
				);
		if( $reply_length > 200 )
			throw new MobileAJAXException(
					'Reply cannot be longer than 200 characters',
					array('show_in_web' => true)
				);

		$reply = $audios->reply_audio( array(
				'audio_id'        => $audio_id,
				'reply'           => $reply,
				'send_to_twitter' => '1' === $send_to_twitter,
				'user_id'         => $audio['user']['id'],
				'tw_id'           => $audio['tw_id']
			)
		);

		if( 'mob' == $this->via )
			HTTP::result( array('success' => true) + $reply );
		else
			View::display_audio( $reply );
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
		$page = HTTP::sanitize_page_number($page) ?: 1;

		if( empty($query) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);

		$search = new \models\Search();
		$result = $search->do_search( array(
				'query'		=>	$query,
				'type'		=>  $type,
				'order'		=>  $order,
				'page' 		=>  $page
			)
		);
		if( 'mob' == $this->via )
			HTTP::result( array('success' => true ) + $result );

		$function_to_call =
  			$result['type'] == 'a' ? 'display_audio' : 'display_user';

  		while( list(,$audio) = each($result['audios']) )
  			View::$function_to_call( $audio );

  		if( $result['load_more'] )
  			View::load_more('search', $page + 1);
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
		$current_user = (new \models\User())->get_current_user();
		if( 'GET' == $this->method ) {
			// to get the settings:
			HTTP::result( array(
					'success'       => true,
					'audios_public' => $current_user->audios_public,
					'favs_public'   => $current_user->favs_public,
					'time'          => $current_user->time
				)
			);
		}

		// to update the settings:

		$favs_public	= HTTP::post('favs_public');
		$audios_public	= HTTP::post('audios_public');
		

		if( ! in_array( $favs_public, array('1','0') ) )
			throw new MobileAJAXException(
					'favs public must be 1 or 0'
				);

		if( ! in_array( $audios_public, array('1','0') ) )
			throw new MobileAJAXException(
					'audios public must be 1 or 0'
				);

		$result = $current_user->update_settings( array(
				'audios_public'     => $audios_public,
				'favs_public'       => $favs_public
			)
		);

		HTTP::result( array(
				'success'   => true,
				'response'  => __('Settings updated successfully!'),
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

		if( ! ($access_token && $access_token) )
			throw new MobileAJAXException(
					'Missing parameters',
					array('error_code' => 7)
				);
		$users = new \models\User();
		$create_user = $users->create(
				$access_token,
				$access_token_secret,
				'mobile'
			);

		if( empty($create_user) )
			throw new MobileAJAXException(
					'Error while logging you in'
				);

		HTTP::result( array('success' => true) + $create_user );
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
		$current_user = (new \models\User)->get_current_user();
		$file_limit   = $current_user->get_limit('file_upload');

		if( 'mob' === $this->via )
			$is_voice = true; #always gna be true cuz it doesn support uploads
		else
			$is_voice = isset($_POST['bin']) && 'mob' !== $this->via;

		if( isset($_POST['bin']) && ! empty($_FILES['up_file']['name']) ) {
			/** someone is tryna trick**/
			throw new MobileAJAXException(
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
				throw new MobileAJAXException(
						'Missing parameters',
						array('error_code' => 7)
					);
			}

			if( isset($_FILES['up_file']["error"] )
			 		&& $_FILES['up_file']["error"] != 0 ) {
				throw new MobileAJAXException(
						'Error code: ' . $_FILES['up_file']['error']
					);
			}

			$format = last( explode('.', $_FILES['up_file']['name']) );

			if( ! in_array(
				strtolower($format),
				array("mp3", "m4a", "aac", "ogg", "wav")
				) ) {
				/*
				* this is just a kick validation
				* the real way to know the format
				* is by checking the content inside
				*/
				throw new MobileAJAXException(
						__('The format of the uploaded audio is not allowed'),
						array('show_in_web' => true)
					);
			}

			$file_size = ( ( $_FILES['up_file']['size'] / 1024) / 1024);

			if( $file_size > $file_limit ) {
	 			throw new MobileAJAXException(
	 					sprintf(
	 						__("The file size is greater than your current limit's, %d mb"),
	 						$file_limit
	 					),
	 					array('show_in_web' => true)
	 				);
			}
			$move = move_uploaded_file(
					$_FILES['up_file']['tmp_name'],
					$file = $_SERVER['DOCUMENT_ROOT'] . // <- it's a point!
					'/assets/tmp/' . uniqid() . '.' . $format
				);
			if( ! $move ) {
				throw new MobileAJAXException(
						'Could not move file'
					);
			}
		} elseif( $is_voice ) {
			/** validate the binary uploaded as base64 **/
			$binary = $_POST['bin'];
			$binary = substr($binary, strpos($binary, ",") + 1);

			if( ! ( $binary = base64_decode($binary, true ) ) ) {
				throw new MobileAJAXException(
						'Binary is corrupt'
					);		
			}
			file_put_contents(
				$file = $_SERVER['DOCUMENT_ROOT'] .
					'/assets/tmp/' . uniqid() . '.mp3',
				$binary
			);
			$file_size = ( filesize( $file ) / 1024 ) / 1024;
			if( $file_size > $file_limit ) {
				/** someone could upload a b64 with a very high filesize */
	 			throw new MobileAJAXException(
	 					sprintf(
	 						__("The file size is greater than your current limit's, %d mb"),
	 						$file_limit
	 					)
	 				);
			}
		} else {
			throw new MobileAJAXException(
					'jah this must never happen'
				);
		}

		/** Now, $file needs a deep validation :) **/
		$audio = new \application\Audio( $file, array(
			'validate'         => true,
			'max_duration'     => $current_user->get_limit('audio_duration'),
			'is_voice'         => $is_voice,
			'decrease_bitrate' => ! $current_user->is_premium(),
			)
		);
		// 3 is the error code when audio needs to be cut.
		if( $audio->error && $audio->error_code != 3 ) {
			throw new MobileAJAXException($audio->error);
		}
		
		$id = uniqid();
		// saves some info
		$_SESSION[$id] = array(
			'tmp_url'  => $audio->audio,
			'is_voice' => $is_voice,
			'duration' => floor( $audio->info['playtime_seconds']),
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

		$available_effects = $current_user->get_available_effects();

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
		while( list(,$effect) = each($available_effects) )
			$effects[ $effect ] = $total_effects[ $effect ];

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
<?php
/**
* User Model
* Manages all the user data
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace models;

class User extends \application\ModelBase {

	public function __construct() {
		parent::__construct();
	}

	/**
	* Returns an array with all the data
	* of the current user.
	*
	* @return array
	* @param $user_content array (optional) - The data
	* that the array will return
	*
	**/
	public function get_current_user( $user_context = array() ) {
		// if $user_context was passed, it's because it's completed
		// so it won't call complete_user again
		// cuz complete_user calls this function
		// and it causes a loop

		if( ! empty($user_context) ) {
			return new \models\CurrentUser($user_context);
		}

		$user = is_logged() ?
			$this->complete_user( (array) $this->user )
		:
			array();

		return new \models\CurrentUser( $user );
	}
	/**
	* Fills the array $user
	* With more info about the user,
	* forces the types and deletes
	* useless stuff
	*
	* @param  $user array - They array with the data to work with
	* @return array
	**/
	public function complete_user( array $user ) {

		$has = function( $key ) use ($user) {
			return array_key_exists( $key, $user );
		};
		/** complete **/

		if( $has('avatar') ) {
			$user['avatar_bigger'] = get_avatar( $user['avatar'], 'bigger');
			$user['avatar_big']    = get_avatar( $user['avatar'] );
		}

		if( $has('id') ) {
			$user['id']         = (int) $user['id'];
			$current_user       = $this->get_current_user( $user );
			$user['can_listen'] = $current_user->can_listen( $user['id'] );
		}

		/** force types **/

		if( $has('favs_public') ) {
			$user['favs_public']   = !! $user['favs_public'];
		}

		if( $has('audios_public') ) {
			$user['audios_public'] = !! $user['audios_public'];
		}

		if( $has('verified') ) {
			$user['verified']      = !! $user['verified'];
		}

		if( $has('time') ) {
			$user['time']          = (int) $user['time'];
		}

		/** remove **/

		if( $has('r') ) {
			// mysqli result
			unset($user['r']);
		}

		if( $has('nums') ) {
			// num rows
			unset($user['nums']);
		}
		return $user;
	}
	/**
	* Gets the info about the given user
	* From the database.
	*
	* @param $id_or_user - The ID or USER to extract the info
	* @param $which_info - The columns of the database
	* @return array
	**/
	public function get_user_info( $id_or_user, $which_info = '*' ) {
		$id_or_user = (string) $id_or_user;
		$column     = ctype_digit( $id_or_user ) ? 'id' : 'user';
		if( null !== $this->user && $id_or_user === $this->user->$column ) {
			// if it's the same user, don't do extra queries
			if( '*' == $which_info ) {
				return (array) $this->get_current_user();
			}
			// return only the columns required
			$result = array();
			foreach( explode(',', $which_info) as $column ) {
				$result[$column] = $this->user->$column;
			}
			return $this->complete_user($result);
		}
		$user = db()->select('users', $which_info)
				->where($column, $id_or_user)
				->execute();

		if( 0 === $user->nums ) {
			return array();
		}

		return $this->complete_user( (array) $user );
	}
	/**
	* Get the count of audios of $id
	* @return integer
	**/
	public function get_audios_count( $id = null ) {
		if( $id === null ) {
			$current_user = $this->get_current_user();
			$id = $current_user->id;
		}
		$audios = db()->query(
			'SELECT COUNT(*) AS size FROM audios
			WHERE reply_to = \'0\'
			AND user = ?
			AND status = \'1\'',
			$id
		);
		return (int) $audios->size;
	}
	/**
	* Get the count of favorites of $id
	* @return integer
	**/
	public function get_favorites_count( $id = null ) {
		if( $id === null ) {
			$current_user = $this->get_current_user();
			$id = $current_user->id;
		}
		$favorites = db()->query(
			'SELECT COUNT(*) AS size FROM audios
			 AS A INNER JOIN favorites AS F ON A.id = F.audio_id
			 AND F.user_id = ? AND A.status = 1',
			$id
		);
		return (int) $favorites->size;
	}
	/**
	* Registers an user in the database if it does not
	* exist. If it does exist, then it re-updates its info.
	*
	* @param $access_token - The Twitter access token after a successful
	* login.
	* @param $access_token_secret - The Twitter access token secret
	* after a successful login.
	* @param $via - Tell the function if you're calling if from
	* an AJAX request or the mobile app
	* @return array - An array with the user data
	**/
	public function create( $access_token, $access_token_secret, $via ) {
		$twitter = new \application\Twitter(
						$access_token,
						$access_token_secret
					);
		$details = $twitter->tw->get('account/verify_credentials');

		if( ! is_object($details) || ! property_exists($details, 'id') ) {
			// twitter error
			return array();
		}

		$id         = $details->id;
		$user       = $details->screen_name;
		$name       = $details->name;
		$bio        = $details->description;
		$avatar     = $details->profile_image_url_https;
		$verified   = (int) $details->verified;

		$first_time = db()->query(
				'SELECT COUNT(*) AS size FROM users
				 WHERE id = ?',
				$id
			);
		$first_time = '0' === $first_time->size;

		if( ! $first_time ) {
			// re-update
			$r = db()->update("users", array(
				"user"			=> $user,
				"name"			=> $name,
				"avatar" 		=> $avatar,
				"bio" 			=> $bio,
				"verified" 		=> $verified,
				"access_token" 	=> $access_token,
				"access_token_secret" => $access_token_secret,
			) )->where('id', $id)->_();
		}else{
			// welcome, new user!
			$favs_public   =
			$audios_public = (int) ! $details->protected;
			$time          = time();
			$lang          = $details->lang;
			$register_user = db()->insert("users", array(
					'id'                  => $id,
					'user'                => $user,
					'name'                => $name,
					'avatar'              => $avatar,
					'bio'                 => $bio,
					'verified'            => $verified,
					'access_token'        => $access_token,
					'access_token_secret' => $access_token_secret,
					'favs_public'         => $favs_public,
					'audios_public'       => $audios_public,
					'time'                => $time,
					'lang'                => $lang
				)
			);
			if( ! $register_user ) {
				throw new \Exception('Insert user error: ' . db()->error);
			}
		}

		///////////// this is a well comented line
		$sess_id = 'mobile' == $via ?
					generate_id('session')
				:
					session_id();
		$sess_time = time();

		$register_session = db()->insert("sessions", array(
				'user_id'    => $id,
				'sess_id'    => $sess_id,
				'time'       => $sess_time,
				'ip'         => get_ip(),
				'is_mobile'  => ('mobile' == $via ? '1' : '0')
			)
		);
		if( ! $register_session ) {
			throw new \Exception('Insert session error: ' . db()->query);
		}

		return array(
				'id'		 => !! $id,
				'user'		 => $user,
				'name'		 => $name,
				'avatar'	 => $avatar,
				'verified'	 => !! $verified,
				'sess_id'	 => $sess_id,
				'first_time' => $first_time
			);
	}
	/**
	* @todo
	**/
	public function ban() {}
	/**
	* @todo
	**/
	public function delete() {}
}
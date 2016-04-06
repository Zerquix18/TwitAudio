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
		// if $user was passed, it's because it's complete
		// so don't call complete_user again
		// cuz complete_user calls this function
		// and it causes a loop

		if( ! empty($user_context) )
			return new \models\CurrentUser( $user_context );

		$user = $this->user !== null ?
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
	* @param  $user array - They array with the data to be threated
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

		if( $has('favs_public') )
			$user['favs_public']   = (bool) $user['favs_public'];

		if( $has('audios_public') )
			$user['audios_public'] = (bool) $user['audios_public'];

		if( $has('verified') )
			$user['verified']      = (bool) $user['verified'];

		if( $has('time') )
			$user['time']          = (int) $user['time'];

		/** remove **/

		if( $has('r') ) // mysqli result
			unset($user['r']);

		if( $has('nums') ) // num rows
			unset($user['nums']);

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

		$column = ctype_digit( $id_or_user ) ? 'id' : 'user';
		if( null !== $this->user && $id_or_user === $this->user->$column ) {
			// if it's the same user, don't do extra queries
			if( '*' == $which_info )
				return (array) $this->get_current_user();
			// return only the columns required
			$result = array();
			foreach( explode(',', $which_info) as $column ) {
				$result[$column] = $this->user->$column;
			}
			return $this->complete_user($result);
		}
		$user = $this->db->select('users', $which_info)
				->where($column, $id_or_user)
				->execute();

		if( 0 == $user->nums )
			return array();

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
		$audios = $this->db->query(
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
		$favorites = $this->db->query(
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
		if( ! is_object($details) || ! property_exists($details, 'id') )
			return array();

		$id 		= $details->id;
		$user 		= $details->screen_name;
		$name 		= $details->name;
		$bio 		= $details->description;
		$avatar 	= $details->profile_image_url_https;
		$verified 	= (int) $details->verified;

		$user_exists = $this->db->query(
				'SELECT COUNT(*) AS size FROM users
				WHERE id = ?',
				$id
			);
		if( (int) $user_exists->size > 0 ) {
			// re-update
			$first_time = false;
			$r = $this->db->update("users", array(
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
			$first_time = true;
			$favs_public =
			$audios_public = (int) ! $details->protected;
			$time = time();
			$lang = $details->lang;
			$db->insert("users", array(
					$id,
					$user,
					$name,
					$avatar,
					$bio,
					$verified,
					$access_token,
					$access_token_secret,
					$favs_public,
					$audios_public,
					$time,
					$lang
				)
			);
		}

		///////////// this is a well comented line
		$sess_id = 'mobile' == $via ?
				\generate_id_for('session'):
				session_id();

		$this->db->insert("sessions", array(
				$id,
				$sess_id,
				$sess_time = time(),
				\get_ip(),
				'mobile' == $via ? '1' : '0'
			)
		);

		return array(
				'id'		 => (bool) $id,
				'user'		 => $user,
				'name'		 => $name,
				'avatar'	 => $avatar,
				'verified'	 => (bool) $verified,
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
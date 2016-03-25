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
	} // do nothing just call ModelBase

	/** functions exclusively for the logged user **/

	public function can_listen( $id ) {
		$is_logged = ( null !== $this->user );

		if( $is_logged && $this->user->id == $id ) // same user
			return true;
		// check if audios of $id are private.
		$check = $this->get_user_info( $id, 'audios_public' );

		if( '1' == $check->audios_public )
			return true;
		if( ! $is_logged )
			return false; // not logged and audios aren't public.
		// not public. check if cached ...
		$this->db->query( // cleans
			"DELETE FROM following_cache WHERE time < ?",
			time() - 1800 // (60*30) half hour
		);
		$is_following = $db->query(
				'SELECT result FROM following_cache
				 WHERE user_id = ? AND following = ?',
				$this->user->id,
				$id
			);
		if( 0 != $is_following->nums )
			return (bool) $is_following->result;
		// not cached, make twitter requests
		$twitter = new \application\Twitter(
				$this->user->access_token,
				$this->user->access_token_secret
			);
		$g = $twitter->tw->get(
			'friendships/lookup',
			array('user_id' => $id)
		);
		if( array_key_exists('errors', $g ) ) {
			// API rate limit reached :( try another
			$t = $twitter->tw->get(
				'users/lookup',
				array('user_id' => $id)
			);
			if( array_key_exists('errors', $t )
			|| array_key_exists('error', $t)
				)
				return false; // both limits reached... ):
			$check = array_key_exists('following', $t[0]) && $t[0]->following;
		}else
			$check = in_array('following', $g[0]->connections);

		$this->db->insert("following_cache", array(
				$_USER->id,
				$id,
				time(),
				(string) (int) $check // result
			)
		);

		return $check;
	}

	public function get_limit( $limit ) {
		$duration = (int) $this->user->upload_seconds_limit;
		switch( $limit ) {
			case 'file_upload':
				$duration = (string) ( $duration / 60 );
				return (int) $duration . '0';
				/**
				* example: duration = 120 then
				* 120/60 = 2
				* return 20(mb)
				* 50 for 5 minutes, 100 for 10 minutes
				* una hermosa simetría <3
				**/
				break;
			case "audio_duration":
				return $duration;
				break;
		}
	}

	public function get_available_effects() {
		$all_effects = array(
				/** effects for all the users **/
				'echo',
				'quick',
				'reverse',
				/** effects for paid users */
				'slow',
				'reverse_quick',
				'hilbert',
				'flanger',
				'delay',
				'deep',
				'low',
				'fade',
				'tremolo'
			);

		$is_paid = $this->is_paid();

		if( ! $is_paid ) // normal user
			return array_splice($all_effects, 0, 3);

		return $all_effects;
	}

	public function get_audios_count( $id = null ) {
		$audios = $this->db->query(
			'SELECT COUNT(*) AS size FROM audios
			WHERE reply_to = \'0\'
			AND user = ?
			AND status = \'1\'',
			$id !== null ? $id : $this->user->id
		);
		return (int) $audios->size;
	}
	public function get_favorites_count( $id = null ) {
		$favorites = $this->db->query(
			'SELECT COUNT(*) AS size FROM audios
			AS A INNER JOIN favorites AS F ON A.id = F.audio_id
			AND F.user_id = ? AND A.status = 1',
			$id !== null ? $id : $this->user->id
		);
		return (int) $favorites->size;
	}

	/** functions for global user actions **/

	/**
	* Loads the info of $id_or_user
	* $which_info are the columns of the database to request.
	*
	**/

	public function get_user_info( $id_or_user, $which_info = '*' ) {
		$column = ctype_digit( $id_or_user ) ? 'id' : 'user';
		if( null !== $this->user && $id_or_user === $this->user->$column ) {
			/** if it's the same user, don't do extra queries **/
			if( '*' == $which_info )
				return $this->user; // return everything
			// return only the columns required
			$result = new \stdClass;
			foreach( explode(',', $which_info) as $column ) {
				$result->$column = $this->user->$column;
			}
			if( property_exists($result, 'avatar') ) {
				$result->avatar_bigger = \get_avatar( $result->avatar, 'bigger');
				$result->avatar_big    = \get_avatar( $result->avatar );
			}
			return $result;
		}
		$user = $this->db->select('users', $which_info)
				->where($column, $id_or_user)
				->execute();

		if( 0 == $user->nums )
			return false;

		if( property_exists($user, 'avatar') ) {
			$user->avatar_bigger = \get_avatar( $user->avatar, 'bigger');
			$user->avatar_big    = \get_avatar( $user->avatar );
		}

		if( property_exists($user, 'id') )
			$user->id = (int) $user->id;

		if( property_exists($user, 'favs_public') )
			$user->favs_public = (bool) $user->favs_public;

		if( property_exists($user, 'audios_public') )
			$user->audios_public = (bool) $user->audios_public;

		$user->can_listen = $this->can_listen( $user->id );

		return $user;
	}

	function is_paid() {
		$duration = (int) $this->user->upload_seconds_limit;
		return $duration > 120;
	}

	/** actions **/

	public function create( $access_token, $access_token_secret, $via ) {
		$twitter = new \application\Twitter(
						$access_token,
						$access_token_secret
					);

		$details = $twitter->tw->get('account/verify_credentials');
		if( ! is_object($details) || ! property_exists($details, 'id') )
			return false;

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
				'id'		 => $id,
				'user'		 => $user,
				'name'		 => $name,
				'avatar'	 => $avatar,
				'verified'	 => $verified,
				'sess_id'	 => $sess_id,
				'first_time' => $first_time
			);
	}
	public function update_settings( array $settings ) {
		return $this->db->update(
				'users',
				$settings
			)->where( 'id', $this->user->id )
			 ->execute();
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
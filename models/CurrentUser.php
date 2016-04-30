<?php
/**
* This class has the functions and
* properties for the logged users.
* It should be called from the User model
**/
namespace models;

class CurrentUser extends \application\ModelBase {

	/**
	* @param $user_info comes from the User model
	* @see \models\User::get_current_user()
	*
	**/
	public function __construct( array $user_info = array() ) {
		parent::__construct(); // call to modelbase

		if( empty($user_info) )
			return;

		foreach($user_info as $key => $value ) {
			$this->$key = $value;
		}
	}
	/**
	* Checks if the logged user
	* can listen to the audios of $id
	* @return bool
	**/

	public function can_listen( $id ) {
		// if user is not logged, then $this will not have
		// the property 'id', which is added in the constructor

		$is_logged = property_exists($this, 'id');

		if( $is_logged && $this->id == $id ) // same user
			return true;

		$users = new \models\User();
		$check = $users->get_user_info( $id, 'audios_public' );

		if( $check['audios_public'] )
			return true;

		if( ! $is_logged )
			return false; // not logged and audios aren't public.

		// not public. check if cached ...
		$this->db->query( // cleans
			"DELETE FROM following_cache WHERE time < ?",
			time() - 1800 // (60*30) half hour
		);
		$is_following = $this->db->query(
				'SELECT result FROM following_cache
				 WHERE user_id = ? AND following = ?',
				$this->id,
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
				'user_id'   => $_USER->id,
				'following' => $id,
				'time'      => time(),
				'result'    => (string) (int) $check
			)
		);

		return $check;
	}
	/**
	* Get a limit of the current user
	**/
	public function get_limit( $limit ) {
		$duration = (int) $this->upload_seconds_limit;
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
	/**
	* @return array
	**/
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

		if( ! $this->is_premium() ) // normal user
			return array_splice($all_effects, 0, 3);

		return $all_effects;
	}
	/**
	* Checks if user is premium
	* @return bool
	**/
	function is_premium() {
		$duration = (int) $this->upload_seconds_limit;
		return $duration > 120;
	}
	public function update_settings( array $settings ) {
		return $this->db->update(
				'users',
				$settings
			)->where( 'id', $this->id )
			 ->execute();
	}
}
<?php
/**
* This class has the functions and
* properties for the logged users.
* It should be called from the User model
**/
namespace models;

class CurrentUser {

	/**
	 * @param $user_info comes from the User model
	 * @see \models\Users::get()
	 *
	**/
	public function __construct( array $user_info = array() ) {

		if( empty($user_info) ) {
			return;
		}

		foreach( $user_info as $key => $value ) {
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

		if( $is_logged && $this->id == $id ) {
			// same user.
			return true;
		}

		$user = Users::get( $id, array('audios_privacy') );

		if( 'public' == $user['audios_privacy'] ) {
			return true;
		}

		if( ! $is_logged ) {
			return false; // not logged and audios aren't public.
		}

		// not public. check if cached ...
		db()->query( // cleans
			"DELETE FROM following_cache WHERE date_added < ?",
			time() - 1800 // (60*30) half hour
		);

		$is_following = db()->query(
				'SELECT result FROM following_cache
				 WHERE user_id = ? AND following = ?',
				$this->id,
				$id
			);

		if( 0 !== $is_following->nums ) {
			return !! $is_following->result;
		}

		// not cached, make twitter requests
		$twitter = new \application\Twitter(
				$this->access_token,
				$this->access_token_secret
			);
		$g = $twitter->tw->get(
			'friendships/lookup',
			array('user_id' => $id)
		);
		if( array_key_exists('errors', $g) ) {
			// API rate limit reached :( try another
			$t = $twitter->tw->get(
				'users/lookup',
				array('user_id' => $id)
			);
			if(    array_key_exists('error', $t)
				|| array_key_exists('errors', $t)
				) {
				// both limits reached... ):
				return false;
			}

			$check = array_key_exists('following', $t[0]) && $t[0]->following;
		} else {
			$check = in_array('following', $g[0]->connections);
		}
		db()->query(
				'INSERT INTO following_cache
				 SET
				 	user_id    = ?,
				 	following  = ?,
				 	date_added = ?,
				 	result     = ?
				',
				$this->id,
				$id,
				time(),
				(string) (int) $check
			);

		return $check;
	}
	/**
	 * Get a limit of the current user
	 * Current limits are:
	 * 'file_upload'    (will return it in mbs)
	 * 'audio_duration' (will return it in seconds)
	 *  @param  string $limit
	**/
	public function get_limit( $limit ) {
		if( ! property_exists($this, 'id') ) {
			return 0;
		}
		$duration = (int) $this->upload_limit;
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
	 * Returns the list of available effects for the logged user
	 * 
	 * @return array
	**/
	public function get_available_effects() {
		$all_effects = array(
				/** effects for all the users **/
				'echo',
				'faster',
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

		if( ! $this->is_premium() ) {
			return array_splice($all_effects, 0, 3);
		}
		return $all_effects;
	}
	/**
	 * Checks if user is premium
	 * @return bool
	**/
	function is_premium() {
		if( ! property_exists($this, 'id') ) {
			// not logged
			return false;
		}
		$duration      = (int) $this->upload_limit;
		$premium_until = (int) $this->premium_until;
		return ($duration > 120) && (time() < $premium_until);
	}
	/**
	 * Updates the settings of the logged user
	 * @param  array  $settings The settings, must be keys of the database
	 * @throws \DBException
	 * @return bool
	 */
	public function update_settings( array $settings ) {
		$column_value = '';
		$params       = array();
		$last         = end($settings);
		reset($settings);
		while( list($option, $value) = each($settings) ) {
			// this way the params are protected
			$column_value .= "{$option} = ?,";
			$params[]     = $value;
		}
		/**
		* Delete the last comma because there was no way
		* to check for the last value inside the loop
		**/
		$column_value = substr($column_value, 0, -1);
		//add the id
		$params[] = $this->id;
		//
		$result   = db()->query(
				"UPDATE users
				 SET
				 	{$column_value}
				 WHERE id = ?
				",
				$params
			);
		if( ! $result ) {
			throw new \DBException('UPDATE user settings error');
		}
		return $result;
	}
}
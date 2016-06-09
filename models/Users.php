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
use \application\interfaces\ModelInterface,
	\application\Twitter,
	\models\CurrentUser;

class Users implements ModelInterface {
	/**
	 * Returns an pobject with all the data
	 * of the current user.
	 *
	 * @param  array $user_content The data that the array will return
	 * @return object
	*
	**/
	public static function get_current_user( array $user_context = array() ) {
		// if $user_context was passed, it's because it's completed
		// so it won't call complete_user again
		// cuz complete_user calls this function
		// and it causes a loop

		if( ! empty($user_context) ) {
			return new CurrentUser($user_context);
		}

		$user = is_logged() ?
			self::complete( (array) $GLOBALS['_USER'] )
		:
			array();

		return new CurrentUser( $user );
	}
	/**
	 * Fills the array $user
	 * With more info about the user,
	 * forces the types and deletes
	 * useless stuff
	 *
	 * @param  array $user - They array with the data to work with
	 * @return array
	**/
	public static function complete( array $user ) {

		/**
		 * Check if $key is un $user
		 * @var Clousure
		 * @return bool
		 */
		$has = function( $key ) use ( $user ) {
			return array_key_exists($key, $user);
		};
		/** complete **/

		if( $has('avatar') ) {
			$user['avatar_bigger'] = get_avatar($user['avatar'], 'bigger');
			$user['avatar_big']    = get_avatar($user['avatar']);
		}

		if( $has('id') ) {
			$user['id']         = (int) $user['id'];
			$current_user       = self::get_current_user($user);
			$user['can_listen'] = $current_user->can_listen($user['id']);
		}

		/** force types **/

		if( $has('is_verified') ) {
			$user['is_verified']   = !! $user['is_verified'];
		}

		if( $has('date_added') ) {
			$user['date_added']    = (int) $user['date_added'];
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
		if( ! is_mobile() ) {
			$user = self::complete_web($user);
		}
		return $user;
	}
	/**
	 * Completes the array $user, adding bars for the web
	 * @param  array  $user The array to complete
	 * @return array        Array with the bars
	 */
	private static function complete_web( array $user ) {
		$has = function( $key ) use ( $user ) {
			return array_key_exists($key, $user);
		};

		if( $has('username') ) {
			$user['profile_url']    = url('audios/' .    $user['username']);
			$user['favorites_url']  = url('favorites/' . $user['username']);
		}

		return $user;
	}
	/**
	 * Gets the info about the given user
	 * From the database.
	 *
	 * @param string $id_or_user    The ID or USER to extract the info
	 * @param array  $which_columns The columns of the database
	 * @return array
	**/
	public static function get( $id_or_user, array $which_columns = array() ) {
		if( array() === $which_columns ) {
			// default
			$which_columns = array(
					'id',
					'username',
					'name',
					'avatar',
					'bio',
					'is_verified',
					'favs_privacy',
					'audios_privacy'
			);
		}
		$id_or_user   = (string) $id_or_user;
		$column       = ctype_digit( $id_or_user ) ? 'id' : 'username';
		$current_user = self::get_current_user();
		if( is_logged() && $id_or_user == $current_user->$column ) {
			// if it's the same user, don't do extra queries
			// return only the columns required
			$result = array();
			while( list(,$column) = each($which_columns) ) {
				$result[$column] = $current_user->$column;
			}
			// to add missing keys:
			return self::complete($result);
		}
		$columns = implode(',', $which_columns);
		$user = db()->query(
				"SELECT {$columns} FROM users WHERE {$column} = ?",
				$id_or_user
			);
		if( ! $user ) {
			throw new \DBException('SELECT user error');
		}
		if( 0 === $user->nums ) {
			return array();
		}

		return self::complete( (array) $user );
	}
	/**
	 * Registers an user in the database if it does not
	 * exist. If it does exist, then it re-updates its info.
	 * @param  array $options An array with the access tokens
	 * @throws ProgrammerException
	 * @return array The user data
	**/
	public static function insert( array $options = array() ) {
		$required_options = array('access_token', 'access_token_secret');
		if( 0 !== count(
					array_diff( $required_options, array_keys($options) )
				)
			) {
			// ups
			throw new \ProgrammerException('Missing required options');
		}
		$twitter = new Twitter(
						$options['access_token'],
						$options['access_token_secret']
					);
		$details = $twitter->tw->get('account/verify_credentials');

		if( ! is_object($details) || ! property_exists($details, 'id') ) {
			throw new \VendorException(
				'Twitter did not return anything: ' . print_r($details, true)
			);
		}

		$id                  = $details->id;
		$username            = $details->screen_name;
		$name                = $details->name;
		$bio                 = $details->description;
		$avatar              = $details->profile_image_url_https;
		$is_verified         = (string) (int) $details->verified;
		$access_token        = $options['access_token'];
		$access_token_secret = $options['access_token_secret'];

		$first_time = db()->query(
				'SELECT COUNT(*) AS size FROM users
				 WHERE id = ?',
				$id
			);
		$first_time = '0' === $first_time->size;

		if( ! $first_time ) {
			// re-update
			$update_user = db()->query(
					'UPDATE users
					 SET
					 	username            = ?,
					 	name                = ?,
					 	avatar              = ?,
					 	bio                 = ?,
					 	is_verified         = ?,
					 	access_token        = ?,
					 	access_token_secret = ?
					 WHERE id = ?',
					$username,
					$name,
					$avatar,
					$bio,
					$verified,
					$access_token,
					$access_token_secret,
					$id
				);
			if( ! $update_user ) {
				throw new \DBException('UPDATE user error');
			}
		} else {
			// welcome, new user!
			$favs_privacy   = //↓
			$audios_privacy = $details->protected ? 'private' : 'public';
			$date_added     = time();
			$register_ip    = get_ip();
			$register_user  = db()->query(
					'INSERT INTO users
					 SET
					 	id                  = ?,
					 	username            = ?,
					 	name                = ?,
					 	avatar              = ?,
					 	bio                 = ?,
					 	is_verified         = ?,
					 	access_token        = ?,
					 	access_token_secret = ?,
					 	favs_privacy        = ?,
					 	audios_privacy      = ?,
					 	date_added          = ?
					',
					$id,
					$username,
					$name,
					$avatar,
					$bio,
					$is_verified,
					$access_token,
					$access_token_secret,
					$favs_privacy,
					$audios_privacy,
					$date_added
				);
			if( ! $register_user ) {
				throw new \DBException('Insert user error: ');
			}
		}

		///////////// this is a well comented line
		$sess_id   = is_mobile() ? generate_id('session') : session_id();
		$sess_time = time();
		$ip        = get_ip();
		$register_session = db()->query(
				'INSERT INTO sessions
				 SET
				 	id         = ?,
				 	user_id    = ?,
				 	date_added = ?,
				 	user_ip    = ?,
				 	is_mobile  = ?',
				$sess_id,
				$id,
				$sess_time,
				$ip,
				is_mobile() ? '1' : '0'
			);
		if( ! $register_session ) {
			throw new \DBException('Insert session error');
		}
		return array(
				'id'          =>    $id,
				'username'    =>    $user,
				'name'        =>    $name,
				'avatar'	  =>    $avatar,
				'is_verified' => !! $is_verified,
				'sess_id'     =>    $sess_id,
				'first_time'  =>    $first_time
			);
	}
	/**
	* @todo
	**/
	public static function ban( array $user ) {}
	/**
	* @todo
	**/
	public static function delete( $id ) {}
}
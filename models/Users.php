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
	* Returns an array with all the data
	* of the current user.
	*
	* @return array
	* @param $user_content array (optional) - The data
	* that the array will return
	*
	**/
	public static function get_current_user( $user_context = array() ) {
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
	* @param  $user array - They array with the data to work with
	* @return array
	**/
	public static function complete( array $user ) {
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
	* @param $which_columns - The columns of the database
	* @return array
	**/
	public static function get( $id_or_user, array $which_columns = array() ) {
		if( array() === $which_columns ) {
			// default
			$which_columns = array(
				'id',
				'user',
				'name',
				'avatar',
				'bio',
				'verified',
				'favs_public',
				'audios_public'
			);
		}
		$id_or_user   = (string) $id_or_user;
		$column       = ctype_digit( $id_or_user ) ? 'id' : 'user';
		$current_user = self::get_current_user();
		if( is_logged() == $current_user->id ) {
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
				'SELECT {$columns} FROM users WHERE {$column} = ?',
				$id_or_user
			);

		if( 0 === $user->nums ) {
			return array();
		}

		return self::complete( (array) $user );
	}
	/**
	* Registers an user in the database if it does not
	* exist. If it does exist, then it re-updates its info.
	*
	* @param $access_token - The Twitter access token after a successful
	* login.
	* @param $access_token_secret - The Twitter access token secret
	* after a successful login.
	* @return array - An array with the user data
	**/
	public static function insert( array $options = array() ) {
		$required_options = array('access_token', 'access_token_secret');
		if( 0 !== count(
					array_diff( $required_options, array_keys($options) )
				)
			) {
			// ups
			trigger_error('Missing required options');
			return array();
		}
		$twitter = new Twitter(
						$options['access_token'],
						$options['access_token_secret']
					);
		$details = $twitter->tw->get('account/verify_credentials');

		if( ! is_object($details) || ! property_exists($details, 'id') ) {
			throw new \Exception(
				'Twitter did not return anything: ' . print_r($details)
			);
		}

		$id         = $details->id;
		$user       = $details->screen_name;
		$name       = $details->name;
		$bio        = $details->description;
		$avatar     = $details->profile_image_url_https;
		$verified   = (string) (int) $details->verified;
		$access_token = $options['access_token'];
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
					 	user                = ?,
					 	name                = ?,
					 	avatar              = ?,
					 	bio                 = ?,
					 	verified            = ?,
					 	access_token        = ?,
					 	access_token_secret = ?
					 WHERE id = ?',
					$user,
					$name,
					$avatar,
					$bio,
					$verified,
					$access_token,
					$access_token_secret,
					$id
				);
			if( ! $update_user ) {
				throw new \Exception('UPDATE error: ' . db()->error);
			}
		} else {
			// welcome, new user!
			$favs_public   = //↓
			$audios_public = (int) ! $details->protected;
			$time          = time();
			$lang          = $details->lang;
			$register_user = db()->query(
					'INSERT INTO users
					 SET
					 	id                  = ?,
					 	user                = ?,
					 	name                = ?,
					 	avatar              = ?,
					 	bio                 = ?,
					 	verified            = ?,
					 	access_token        = ?,
					 	access_token_secret = ?,
					 	favs_public         = ?,
					 	audios_public       = ?,
					 	`time`              = ?,
					 	lang                = ?
					',
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
				);
			if( ! $register_user ) {
				throw new \Exception('Insert user error: ' . db()->error);
			}
		}

		///////////// this is a well comented line
		$sess_id   = is_mobile() ? generate_id('session') : session_id();
		$sess_time = time();
		$ip        = get_ip();
		$register_session = db()->query(
				'INSERT INTO sessions
				 SET
				 	user_id    = ?,
				 	sess_id    = ?,
				 	`time`     = ?,
				 	ip         = ?,
				 	is_mobile  = ?',
				$id,
				$sess_id,
				$sess_time,
				$ip,
				is_mobile() ? '1' : '0'
			);
		if( ! $register_session ) {
			throw new \Exception('Insert session error: ' . db()->error);
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
	public static function ban( array $user ) {}
	/**
	* @todo
	**/
	public static function delete( array $user ) {}
}
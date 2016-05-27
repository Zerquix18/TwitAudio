<?php
/**
* Audio Model
* Manages all the audios/replies data
*
* @author Zerquix18 <zerquix18@outlook.com>
*
**/
namespace models;

use \application\interfaces\ModelInterface,
	\application\Twitter,
	\application\HTTP,
	\models\Users;

class Audios implements ModelInterface {
	/**
	 * Loads the user info, how many favorites
	 * and how many replies, etc. the audio has.
	 * And adds it to the array.
	 *
	 * @param  array $audio The array to be completed
	 * @return array        The audio completed
	 *
	**/
	public static function complete( array $audio ) {

		$has = function( $key ) use ( $audio ) {
			return array_key_exists( $key, $audio );
		};
		
		if( $has('status') ) {
			unset($audio['status']);
		}

		if( $has('user') ) {
			$audio['user'] = Users::get($audio['user']);
		}

		if( $has('id') ) {

			if( is_logged() ) {
				$current_user = Users::get_current_user();
				$favorited = db()->query(
					'SELECT COUNT(*) AS size FROM favorites
					 WHERE audio_id = ? AND user_id = ?',
					$audio['id'],
					$current_user->id
				);
				$audio['favorited'] = !! $favorited->size;
			} else {
				$audio['favorited'] = false;
			}

			$replies_count = db()->query(
				'SELECT COUNT(*) AS size FROM audios
				WHERE status = \'1\' AND reply_to = ?',
				$audio['id']
			);
			$audio['replies_count'] = (int) $replies_count->size;

		}
		if( $has('favorites') ) {
			$audio['favorites'] = (int) $audio['favorites'];
		}
		if( $has('plays') ) {
			$audio['plays']     = (int) $audio['plays'];
		}
		if( $has('time') ) {
			$audio['time']      = (int) $audio['time'];
		}
		if( $has('duration') ) {
			$audio['duration']  = (int) $audio['duration'];
		}
		
		if( ! empty($audio['audio']) ) {
			$audio['original_name'] = $audio['audio'];
			$audio['audio']         = url('assets/audios/' . $audio['audio']);
		}

		/** remove **/

		if( $has('r') ) {
			// mysqli result
			unset($audio['r']);
		}
		if( $has('nums') ) {
			// num rows
			unset($audio['nums']);
		}
		// if we are in web we need extra stuff for the template
		if( ! is_mobile() ) {
			$audio = self::complete_web($audio);
		}

		return $audio;
	}
	/**
	 * Add bars for handlebars.
	 * They're only needed in the web side.
	 *
	 * @param array $audio The array to complete
	 */
	public static function complete_web( $audio ) {

		$has = function( $key ) use ( $audio ) {
			return array_key_exists( $key, $audio );
		};
		$current_user = Users::get_current_user();

		if( $has('user') ) {
			$audio['user']['can_favorite'] = is_logged();
			// is_logged() returns the user ID
			$is_logged = is_logged();
			$audio['user']['can_delete']   =
			$is_logged && $audio['user']['id'] == $current_user->id;
		}
		if( $has('plays') ) {
			$audio['plays_count'] = format_number($audio['plays']);
			if( $audio['plays'] == 1 ) {
				$plays_count_text = '%d person played this';
			} else {
				$plays_count_text = '%d people played this';
			}
			$audio['plays_count_text'] = //↓
			sprintf($plays_count_text, $audio['plays']);
		}
		if( $has('replies_count') ) {
			$audio['replies_count'] = format_number($audio['replies_count']);
		}
		if( $has('favorites') ) {
			$audio['favorites_count'] = //↓
			format_number($audio['favorites']);
		}
		if( $has('description') ) {
			// add links, @mentions and avoid XSS
			$audio['description'] = HTTP::sanitize($audio['description']);
		}

		if( $has('id') ) {
			if( $has('reply_to') && $audio['reply_to'] != '0' ) {
				/*
					If it's a reply, then add a link to the original audio
					But with this reply appearing first
				 */
				$audio['audio_url'] =
				url() . $audio['reply_to'] .'?reply_id=' . $audio['id'];
			} else {
				$audio['audio_url'] = url() . $audio['id'];
			}
		}

		if(    $has('id')
			&& $has('audio')
			&& $has('reply_to')
			&& '0' == $audio['reply_to']
			) {
			$audio['player'] = array(
				'id'       => $audio['id'],
				'audio'    => $audio['audio'],
				'autoload' => true
			);
		}

		return $audio;

	}
	/**
	 * Returns an array with the info of the audio $id
	 *
	 * @param  string $id       The ID of the audio to load
	 * @param  array $whichinfo The database columns
	 * @throws \Exception 
	 * @return array
	*
	**/
	public static function get( $id, array $which_columns = array() ) {

		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id) ) {
			return array();
		}

		if( array() === $which_columns ) {
			// default columns
			$which_columns = array(
					'id',
					'user',
					'audio',
					'reply_to',
					'description',
					'time',
					'plays',
					'favorites',
					'duration'
				);
		}
		$columns = implode(',', $which_columns);

		$audio   = db()->query(
				"SELECT {$columns} FROM audios
				 WHERE id = ? AND status = 1",
				$id
			);
		if( ! $audio ) {
			throw new \Exception('SELECT error: ' . db()->error);
		}

		if( 0 === $audio->nums ) {
			return array();
		}
		return self::complete( (array) $audio );
	}
	/**
	 * Returns an array with the last 3 recent audios
	 * of the logged user
	 *
	 * @return array
	 *
	**/
	public static function get_recent_audios() {
		$result = array();
		if( ! is_logged() ) {
			return $result;
		}
		$current_user = Users::get_current_user();
		$recent_audios_by_user = db()->query(
					'SELECT * FROM audios
					 WHERE reply_to = \'0\'
					 AND status = \'1\'
					 AND user = ?
					 ORDER BY `time` DESC
					 LIMIT 3',
					$current_user->id
				);
		while( $audio = $recent_audios_by_user->r->fetch_assoc() ) {
			$result[] = self::complete($audio);
		}
		return $result;
	}
	/**
	 * Returns an array with the
	 * 3 most listened audios of
	 * the last 30 days
	 * @return array
	**/
	public static function get_popular_audios() {
		$result          = array();
		/**
		* @todo
		* LEARN TO USE JOIN
		**/
		$recents_popular = db()->query(
			'SELECT * FROM audios
			WHERE user NOT IN (
					SELECT id
					FROM users
					WHERE audios_public = \'0\'
					)
			AND reply_to = \'0\'
			AND status = \'1\'
			AND `time` BETWEEN ? AND ?
			ORDER BY plays DESC
			LIMIT 3',
			time() - strtotime('-30 days'),
			time()
		);
		while( $audio = $recents_popular->r->fetch_assoc() ) {
			$result[] = self::complete($audio);
		}
		return $result;
	}

	/**
	 * Returns an array with the last 10 audios of $user_id
	 *
	 * @param string  $user_id The ID of the user
	 * @param integer $page    The page number
	 * @return array
	*
	**/
	public static function get_audios( $user_id, $page = 1 ) {
		$query = "SELECT
						id,
						user,
						audio,
						reply_to,
						description,
						`time`,
						plays,
						favorites,
						duration
					FROM audios
				  WHERE user   = ?
				  AND reply_to = '0'
				  AND status   = '1'
				  ORDER BY `time` DESC";
		$count = db()->query(
				"SELECT COUNT(*) AS size FROM audios
				 WHERE user = ? AND reply_to = '0'",
				$user_id
			);
		$count = (int) $count->size;
		if( 0 === $count ) {
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);
		}
		$total_pages = ceil( $count / 10 );
		if( $page > $total_pages ) {
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);
		}

		$query .= ' LIMIT '. ($page-1) * 10 . ',10';
		$audios = db()->query($query, $user_id);
		$result = array(
				'audios' => array()
			);
		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = self::complete($audio);
		}
	
		$result['load_more'] = $page < $total_pages;
		$result['page'] 	 = $page + 1;
		$result['total'] 	 = $count;
		return $result;
	}

	/**
	 * Returns an array with the last 10 replies of $audio_id
	 *
	 * @param string  $audio_id The ID of the audio
	 * @param integer $page     The page number
	 * @return array
	 *
	**/
	public static function get_replies( $audio_id, $page = 1 ) {
		$query = "SELECT id,user,audio,reply_to,description,
						 time,plays,favorites,duration
					FROM audios
				  WHERE reply_to = ?
				  AND status = '1'
				  ORDER BY time DESC";
		$count = db()->query(
				"SELECT COUNT(*) AS size FROM audios
				 WHERE reply_to = ?",
				 $audio_id
				);
		$count = (int) $count->size;

		if( 0 == $count ) {
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);
		}

		$total_pages = ceil( $count / 10 );
		if( $page > $total_pages ) {
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);
		}

		$query .= ' LIMIT '. ($page-1) * 10 . ',10';
		$audios = db()->query($query, $audio_id);
		$result = array(
				'audios'	=>	array()
			);
		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = self::complete($audio);
		}
	
		$result['load_more'] = ($page < $total_pages);
		$result['page']      = $page + 1;
		$result['total']     = $count;
		return $result;
	}

	/**
	 * Returns an array with the last 10 favorites of $user_id
	 *
	 * @param $user_id - The ID of the user
	 * @param $page    - The page number
	 * @return array
	 *
	**/
	public static function get_favorites( $user_id, $page = 1 ) {
		$query = "SELECT DISTINCT A.* FROM audios
					AS A INNER JOIN favorites AS F ON A.id = F.audio_id
					AND F.user_id = ? AND A.status = '1'
					ORDER BY F.time DESC";
		$count = db()->query(
				"SELECT COUNT(*) AS size FROM audios
				 AS A INNER JOIN favorites AS F ON A.id = F.audio_id
				 AND F.user_id = ? AND A.status = '1'",
				$user_id
			);
		$count = (int) $count->size;

		if( 0 == $count ) {
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);
		}

		$total_pages = ceil( $count / 10 );
		if( $page > $total_pages ) {
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);
		}
			
		$query .= ' LIMIT '. ($page-1) * 10 . ',10';
		$audios = db()->query($query, $user_id);
		$result = array(
				'audios'	=>	array()
			);
		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = self::complete($audio);
		}
	
		$result['load_more'] = $page < $total_pages;
		$result['page']      = $page + 1;
		$result['total']     = $count;
		return $result;
	}
	/**
	 * Get the count of audios of the user $id
	 *
	 * @param string $id
	 * @return integer
	**/
	public static function get_audios_count( $id ) {
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
	 * Get the count of favorites of the user $id
	 * @return integer
	**/
	public static function get_favorites_count( $id ) {
		$favorites = db()->query(
			'SELECT COUNT(*) AS size FROM audios
			 AS A INNER JOIN favorites AS F ON A.id = F.audio_id
			 AND F.user_id = ? AND A.status = 1',
			$id
		);
		return (int) $favorites->size;
	}
	/**
	 * Inserts an audio|reply in the database
	 * @param  array  $options The keys to insert
	 * @return array           An array with everything inserted so it can
	 *                         be displayed in screen instantly.
	 */
	public static function insert( array $options ) {
		if( empty($options['audio']) && empty($options['reply_to']) ) {
			// come on! need one of both
			trigger_error('Missing option audios || reply');
			return array();
		}
		$is_reply = ! empty($options['reply_to']) && empty($options['audio']);
		if( ! $is_reply ) {
			// required keys for audios
			$required_options = array(
					'description', 'duration',
					'is_voice', 'send_to_twitter',
					'audio',
			);
		} else {
			$required_options = array(
					'reply', 'send_to_twitter',
					'reply_to', 'tw_id', 'user_id'
				);
		}
		if( 0 !== count(
					array_diff( $required_options, array_keys($options) )
					)
			) {
			// ups
			trigger_error('Missing required options');
			return array();
		}
		$audio_id     = generate_id('audio');
		$current_user = Users::get_current_user();
		$result       = db()->query(
				'INSERT INTO audios
				 SET
				 	id          = ?,
				 	user        = ?,
				 	audio       = ?,
				 	reply_to    = ?,
				 	description = ?,
				 	tw_id       = ?,
				 	`time`      = ?,
				 	plays       = ?,
				 	favorites   = ?,
				 	duration    = ?,
				 	is_voice    = ?
				',
				//id:
				$audio_id,
				//user:
				$current_user->id,
				//audio:
				! $is_reply ? $options['audio']    : '0',
				//reply to:
				$is_reply   ? $options['reply_to'] : '0',
				// description/reply:
				! $is_reply ? $options['description'] : $options['reply'],
				// twitter reply id:
				'0',
				// time:
				time(),
				//plays:
				'0',
				//favorites:
				'0',
				// duration:
				! $is_reply ? $options['duration'] : '0',
				// is_voice:
				! $is_reply ? '1' : '0'
			);
		if( ! $result ) {
			throw new \Exception('INSERT error: ' . db()->error - ' [' . db()->query . ' ]');
		}
		$audio = self::get($audio_id);
		// now proceed to tweet
		if( ! $options['send_to_twitter'] ) {
			// we got nothing else to do
			return $audio;
		}
		$twitter = new Twitter(
				$current_user->access_token,
				$current_user->access_token_secret
			);
		if( ! $is_reply ) {
			/**
			* Make the tweet for audios.
			* I'll explain this nightmare.
			**/
			// here's the link, forget about www here
			$link         = 'https://twitaudio.com/'. $audio_id;
			$link_length  = strlen($link);
			$description  = $options['description'];
			if( mb_strlen($description, 'utf-8') > (140-$link_length) ) {
				// if the description is longer than (140 - the link length)
				// then make it shorter
				$description  = substr(
						$description,
						0,
						140-$link_length-4 // 4= the 3 periods below + space
					);
				$description .= '...';
			}
			// we're done :)
			$tweet = $description . ' ' . $link;
		} else {
			/**
			* Make the tweet for replies
			* This nightmare is bigger than
			* the one above.
			**/
			// we got the final part
			$link        = ' - https://twitaudio.com/'. $audio_id;
			$link_length = strlen($link);

			$at    = db()->query(
				'SELECT user FROM users WHERE id = ?',
				$options['user_id']
			);
			$at    = $at->user;

			$reply = sprintf('@%s %s', $at, $options['reply']);

			if( mb_strlen($reply, 'utf-8') > (140-$link_length) ) {
				$reply  = substr($reply, 0, 140-$link_length-3);
				$reply .= '...';
			}
			$tweet = $reply . $link;
		}
		//->tweet() returns the ID
		$tweet_id = $twitter->tweet($tweet);
		if( $tweet_id ) {
			// now re-update the ID in the db
			$result = db()->query(
					'UPDATE audios
					 SET
					 	tw_id = ?
					 WHERE id = ?
					',
					$tweet_id,
					$audio_id
				);
			if( ! $result ) {
				throw new \Exception('UPDATE error: ' . db()->error);
			}
		}
		return $audio;
	}

	/**
	 * Will delete an audio or a reply
	 *
	 * This function is BLIND
	 * It will delete the audio without any
	 * comprobation.
	 * @return void
	**/
	public static function delete( array $audio ) {
		$id = $audio['id'];
		db()->query('DELETE FROM audios    WHERE id       = ?', $id);
		db()->query('DELETE FROM audios    WHERE reply_to = ?', $id);
		db()->query('DELETE FROM plays     WHERE audio_id = ?', $id);
		db()->query('DELETE FROM favorites WHERE audio_id = ?', $id);

		if( ! empty($audio['audio']) ) {
			@unlink(
				$_SERVER['DOCUMENT_ROOT'] .
				'/assets/audios/' . $audio['original_name']
			);
		}
	}
	/**
	 * Favorites an audio
	 * @param string $audio_id
	**/
	public static function register_favorite( $audio_id ) {
		$update = db()->query(
				'UPDATE audios
				 SET favorites = favorites + 1
				 WHERE id = ?',
				$audio_id
			);
		if( ! $update ) {
			throw new \Exception('UPDATE error: ' . db()->error);
		}

		$current_user = Users::get_current_user();

		$insert = db()->query(
				'INSERT INTO favorites
				 SET
				 	user_id  = ?,
				 	audio_id = ?,
				 	`time`   = ?
				',
				$current_user->id,
				$audio_id,
				time()
			);
		if( ! $insert ) {
			throw new \Exception('INSERT error: ' . db()->error);
		}
	}
	/**
	 * Unfavorites an audio
	 * @param  string $audio_id
	**/
	public static function unregister_favorite( $audio_id ) {
		$update = db()->query(
				'UPDATE audios
				 SET favorites = favorites - 1
				 WHERE id = ?',
				$audio_id
			);
		if( ! $update ) {
			throw new \Exception('UPDATE error: ' . db()->error);
		}
		$current_user = Users::get_current_user();
		$delete = db()->query(
			'DELETE FROM favorites
			 WHERE audio_id = ?
			 AND    user_id = ?
			',
			$audio_id,
			$current_user->id
		);
		if( ! $delete ) {
			throw new \Exception('DELETE error: '. db()->error);
		}
	}
	/**
	* Registers a play for $audio_id
	*
	* @param $audio_id str
	* @return bool
	**/
	public static function register_play( $audio_id ) {
		$user_ip    = get_ip(); // ← /application/functions.php
		$was_played = db()->query(
				'SELECT COUNT(*) AS size
				 FROM plays
				 WHERE user_ip = ?
				 AND  audio_id = ?',
				$user_ip,
				$audio_id
			);
		$was_played  = (int) $was_played->size;
		if( $was_played ) {
			return false;
		}

		$insert = db()->query(
			'UPDATE audios SET plays = plays + 1 WHERE id = ?',
			$audio_id
		);
		if( ! $insert ) {
			throw new \Exception('UPDATE error: ' . db()->error);
		}
		$update = db()->query(
			'INSERT INTO plays
			SET
				user_ip  = ?,
				audio_id = ?,
				`time`   = ?
			',
			$user_ip,
			$audio_id,
			time()
		);
		if( ! $update ) {
			throw new \Exception('INSERT error: ' . db()->error);
		}
		return true;
	}
}
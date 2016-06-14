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
	 * The default columns to select from audios
	 */
	public static $columns = array(
								'id',
								'user_id',
								'audio_url',
								'reply_to',
								'description',
								'date_added',
								'plays',
								'favorites',
								'duration'
							);
	public static $per_page = 10;
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

		if( $has('user_id') ) {
			$audio['user'] = Users::get($audio['user_id']);
			unset($audio['user_id']);
		}

		if( $has('id') ) {

			if( is_logged() ) {
				$current_user = Users::get_current_user();
				$is_favorited = db()->query(
					'SELECT COUNT(*) AS size FROM favorites
					 WHERE audio_id = ? AND user_id = ?',
					$audio['id'],
					$current_user->id
				);
				$audio['is_favorited'] = !! $is_favorited->size;
			} else {
				$audio['is_favorited'] = false;
			}

			$audio['replies_count'] = self::get_replies_count($audio['id']);

		}
		if( $has('favorites') ) {
			$audio['favorites']  = (int) $audio['favorites'];
		}
		if( $has('plays') ) {
			$audio['plays']      = (int) $audio['plays'];
		}
		if( $has('date_added') ) {
			$audio['date_added'] = (int) $audio['date_added'];
		}
		if( $has('duration') ) {
			$audio['duration']   = (int) $audio['duration'];
		}
		
		if( ! empty($audio['audio_url']) ) {
			$audio['original_name'] = $audio['audio_url'];
			$audio['audio']         = 
			url('assets/audios/' . $audio['audio_url']);
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
			if( $has('reply_to') && $audio['reply_to'] ) {
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
			&& ! $audio['reply_to']
			) {
			$audio['player'] = array(
				'id'        => $audio['id'],
				'audio'     => $audio['audio'],
				'autoload'  => true
			);
		}

		return $audio;

	}
	/**
	 * Returns an array with the info of the audio $id
	 *
	 * @param  string $id       The ID of the audio to load
	 * @param  array $whichinfo The database columns
	 * @throws \DBException 
	 * @return array
	*
	**/
	public static function get( $id, array $which_columns = array() ) {

		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id) ) {
			return array();
		}

		if( array() === $which_columns ) {
			$which_columns = self::$columns;
		}

		$columns = implode(',', $which_columns);

		$audio   = db()->query(
				"SELECT {$columns} FROM audios
				 WHERE id = ? AND status = '1'",
				$id
			);
		if( ! $audio ) {
			throw new \DBException('SELECT audio error');
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
		$current_user = Users::get_current_user();
		$columns      = implode(',', self::$columns);
		$recent_audios_by_user = db()->query(
					"SELECT
						{$columns}
					 FROM audios
					 WHERE reply_to IS NULL
					 AND   status  = '1'
					 AND   user_id = ?
					 ORDER BY date_added DESC
					 LIMIT 3",
					$current_user->id
				);
		if( ! $recent_audios_by_user ) {
			throw new \DBException('SELECT recents error');
		}
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
		$columns = self::$columns;
		/**
		 * Here we do a JOIN, so before putting the columns,
		 * place the 'A.' (for the Audios table) before every
		 * column.
		 * @var array
		 */
		$columns = array_map(
				function( $value ) {
					return 'A.' . $value;
				},
				$columns
			);
		$columns = implode(',', $columns);
		$recents_popular = db()->query(
				"SELECT DISTINCT
					{$columns}
				 FROM audios AS A
				 INNER JOIN users AS U
				 ON  A.user_id        = U.id
				 AND U.audios_privacy = 'public'
				 AND A.reply_to IS NULL
				 AND A.status         = '1'
				 AND A.date_added BETWEEN ? AND ?
				 ORDER BY A.plays DESC
				 LIMIT 3",
				time() - strtotime('-30 days'),
				time()
			);
		if( ! $recents_popular ) {
			throw new \DBException('SELECT popular error: ' . db()->error);
		}
		$result = array();
		while( $audio = $recents_popular->r->fetch_assoc() ) {
			$result[] = self::complete($audio);
		}
		return $result;
	}
	/**
	 * Get the count of audios of the user $id
	 *
	 * @param  string $id
	 * @return integer
	**/
	public static function get_audios_count( $user_id ) {
		$audios = db()->query(
			"SELECT
				COUNT(*) AS size
			 FROM audios
			 WHERE reply_to IS NULL
			 AND user_id = ?
			 AND status  = '1'",
			$user_id
		);
		if( ! $audios ) {
			throw new \DBException('SELECT COUNT audios error');
		}
		return (int) $audios->size;
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
		$count  = self::get_audios_count($user_id);
		// default result
		$result = array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);

		if( 0 === $count ) {
			return $result;
		}

		$total_pages = ceil( $count / self::$per_page );

		if( $page > $total_pages ) {
			return $result;
		}

		$columns = self::$columns;
		$columns = implode(',', $columns);
		$query   = sprintf(
					"SELECT
						%s
					FROM audios
					WHERE reply_to IS NULL
					AND   status  = '1'
					AND   user_id = ?
					ORDER BY date_added DESC
					LIMIT %d, %d",
					$columns,
					($page-1) * self::$per_page,
					self::$per_page
				);
		$audios  = db()->query($query, $user_id);

		if( ! $audios ) {
			throw new \DBException('SELECT audios error:');
		}

		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = self::complete($audio);
		}

		$result['load_more'] = $page < $total_pages;
		$result['page'] 	 = $page + 1;
		$result['total'] 	 = $count;
		return $result;
	}
	/**
	 * Get the count of replies of the audio $id
	 *
	 * @param  string $id
	 * @return integer
	**/
	public static function get_replies_count( $audio_id ) {
		$audios = db()->query(
			"SELECT
				COUNT(*) AS size
			 FROM audios
			 WHERE reply_to = ?
			 AND   status   = '1'",
			$audio_id
		);
		if( ! $audios ) {
			throw new \DBException('SELECT COUNT replies error');
		}
		return (int) $audios->size;
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
		$count  = self::get_replies_count($audio_id);
		// default result
		$result = array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);

		if( 0 === $count ) {
			return $result;
		}

		$total_pages = ceil($count / self::$per_page);

		if( $page > $total_pages ) {
			return $result;
		}

		$columns = self::$columns;
		$columns = implode(',', $columns);
		$query   = sprintf(
					"SELECT
						%s
					FROM audios
					WHERE reply_to = ?
					AND   status   = '1'
					ORDER BY date_added DESC
					LIMIT %d, %d",
					$columns,
					($page-1) * self::$per_page,
					self::$per_page
				);
		$audios  = db()->query($query, $audio_id);

		if( ! $audios ) {
			throw new \DBException('SELECT replies error');
		}

		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = self::complete($audio);
		}
	
		$result['load_more'] = ($page < $total_pages);
		$result['page']      = $page + 1;
		$result['total']     = $count;
		return $result;
	}
	/**
	 * Get the count of favorites of the $audio_id
	 * @param  string $user_id
	 * @return integer
	**/
	public static function get_favorites_count( $user_id ) {
		$audios = db()->query(
			'SELECT
				COUNT(*) AS size
			 FROM audios AS A
			 INNER JOIN favorites AS F
			 ON A.id       = F.audio_id
			 AND F.user_id = ?
			 AND A.status  = 1',
			$user_id
		);
		if( ! $audios ) {
			throw new \DBException('SELECT COUNT favorites error');
		}
		return (int) $audios->size;
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
		$count  = self::get_favorites_count($user_id);
		$result = array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count
				);

		if( 0 == $count ) {
			return $result;
		}

		$total_pages = ceil( $count / 10 );

		if( $page > $total_pages ) {
			return $result;
		}

		$columns = self::$columns;
		$columns = array_map(
				function( $value ) {
					return 'A.' . $value;
				},
				$columns
			);
		$columns = implode(',', $columns);
		$query   = sprintf(
					"SELECT DISTINCT
						%s
					 FROM audios AS A
					 INNER JOIN favorites AS F
					 ON A.id       = F.audio_id
					 AND F.user_id = ?
					 AND A.status  = '1'
					 ORDER BY F.date_added DESC
					 LIMIT %d, %d",
					$columns,
					($page-1) * self::$per_page,
					self::$per_page
				);
		$audios  = db()->query($query, $user_id);

		if( ! $audios ) {
			throw new \DBException('SELECT favorites error');
		}

		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = self::complete($audio);
		}
	
		$result['load_more'] = $page < $total_pages;
		$result['page']      = $page + 1;
		$result['total']     = $count;
		return $result;
	}
	/**
	 * Inserts an audio|reply in the database
	 * @param  array  $options The keys to insert
	 * @return array           An array with everything inserted so it can
	 *                         be displayed in screen instantly.
	 * @throws ProgrammerException
	 * @throws DBException
	 */
	public static function insert( array $options ) {
		if( empty($options['audio_url']) && empty($options['reply_to']) ) {
			// come on! need one of both
			throw new \ProgrammerException(
					'Missing option audios_url || reply'
				);
		}
		$is_reply = ! empty($options['reply_to']) && empty($options['audio']);
		if( ! $is_reply ) {
			// required keys for audios
			$required_options = array(
					'description', 'duration',
					'is_voice', 'send_to_twitter',
					'audio_url',
			);
		} else {
			$required_options = array(
					'reply', 'send_to_twitter',
					'reply_to', 'twitter_id', 'user_id'
				);
		}
		if( 0 !== count(
					array_diff( $required_options, array_keys($options) )
					)
			) {
			// ups
			throw new \ProgrammerException('Missing required options');
		}
		$audio_id     = generate_id('audio');
		$current_user = Users::get_current_user();
		$result       = db()->query(
				'INSERT INTO audios
				 SET
				 	id          = ?,
				 	user_id     = ?,
				 	audio_url   = ?,
				 	reply_to    = ?,
				 	description = ?,
				 	date_added  = ?,
				 	duration    = ?,
				 	is_voice    = ?
				',
				//id:
				$audio_id,
				//user:
				$current_user->id,
				//audio:
				! $is_reply ? $options['audio_url'] : NULL,
				//reply to:
				$is_reply   ? $options['reply_to']  : NULL,
				// description/reply:
				! $is_reply ? $options['description'] : $options['reply'],
				// time:
				time(),
				// duration:
				! $is_reply ? $options['duration'] : '0',
				// is_voice:
				! $is_reply ? '1' : '0'
			);
		if( ! $result ) {
			throw new \DBException('INSERT audio/reply error');
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
					 	twitter_id = ?
					 WHERE
					 	id         = ?
					',
					$tweet_id,
					$audio_id
				);
			if( ! $result ) {
				throw new \DBException('UPDATE audios [twitter_id] error');
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
	**/
	public static function delete( $id ) {
		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id) ) {
			return;
		}
		$audio        = self::get($id, array('audio_url') );
		$delete_audio = db()->query(
				"DELETE FROM audios WHERE id = '{$id}'"
			);

		if( ! $delete_audio ) {
			throw new \DBException('DELETE audio error');
		}
		/*
			I tried making only one query but it generated a lot of
			warnings and did not delete the audio :(
		 */
		$delete_replies = db()->query(
				"DELETE FROM audios WHERE reply_to = '{$id}'"
			);
		if( ! $delete_replies ) {
			throw new \DBException('DELETE audio [replies] error');
		}

		if( $audio['audio_url'] ) {
			@unlink(
				DOCUMENT_ROOT .
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
			throw new \DBException('UPDATE audios [favorites] error');
		}

		$current_user = Users::get_current_user();

		$insert = db()->query(
				'INSERT INTO favorites
				 SET
				 	user_id    = ?,
				 	audio_id   = ?,
				 	date_added = ?
				',
				$current_user->id,
				$audio_id,
				time()
			);
		if( ! $insert ) {
			throw new \DBException('INSERT favorite error');
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
			throw new \DBException('UPDATE favorites error');
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
			throw new \DBEXception('DELETE favorite error');
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
		if( ! $was_played ) {
			throw new \DBException('SELECT COUNT plays error');
		}
		$was_played  = (int) $was_played->size;
		if( $was_played ) {
			return false;
		}

		$insert = db()->query(
			'UPDATE audios SET plays = plays + 1 WHERE id = ?',
			$audio_id
		);
		if( ! $insert ) {
			throw new \DBException('UPDATE audio [plays] error');
		}
		$update = db()->query(
			'INSERT INTO plays
			SET
				user_ip    = ?,
				audio_id   = ?,
				date_added = ?
			',
			$user_ip,
			$audio_id,
			time()
		);
		if( ! $update ) {
			throw new \DBException('INSERT play error');
		}
		return true;
	}
}
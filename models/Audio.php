<?php
/**
* Audio Model
* Manages all the audios/replies data
*
* @author Zerquix18 <zerquix18@outlook.com>
*
**/
namespace models;

class Audio extends \application\ModelBase {
	public function __construct() {
		parent::__construct();
	}
	/**
	* Loads the user info, how many favorites
	* and how many replies, etc. the audio has.
	* And adds it to the array.
	*
	* @param  $audio array - The array to be completed
	* @return array - The audio completed
	*
	**/
	public function complete_audio( array $audio ) {

		$has = function( $key ) use ( $audio ) {
			return array_key_exists( $key, $audio );
		};
		
		if( $has('status') ) {
			unset($audio['status']);
		}

		if( $has('user') ) {

			$users = new \models\User();
			$audio['user'] = $users->get_user_info(
					$audio['user'],
					'id,user,name,avatar,verified,audios_public'
				);
		}

		if( $has('id') ) {

			if( null !== $this->user ) {
				$favorited = $this->db->query(
					'SELECT COUNT(*) AS size FROM favorites
					 WHERE audio_id = ? AND user_id = ?',
					$audio['id'],
					$this->user->id
				);
				$audio['favorited'] = !! $favorited->size;
			}else{
				$audio['favorited'] = false;
			}

			$replies_count = $this->db->query(
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
		return $audio;
	}
	/**
	* Returns an array with the info of the audio $id
	*
	* @param $id - the ID of the audio to load
	* @param $whichinfo - The database columns
	* @return array
	*
	**/
	public function get_audio_info( $id, $whichinfo = '*' ) {

		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id ) ) {
			return false;
		}

		$audio = $this->db->select('audios', $whichinfo)
				 ->where( array(
				 		'id'     => $id,
				 		'status' => 1
				 	)
				 )
				 ->execute();
				 
		if( 0 === $audio->nums ) {
			return array();
		}
		return $this->complete_audio( (array) $audio );
	}
	/**
	* Returns an array with the last 3 recent audios
	* of the logged user
	*
	* @return array
	*
	**/
	public function get_recent_audios_by_user() {
		$result                = array();
		$recent_audios_by_user = $this->db->query(
					'SELECT * FROM audios
					 WHERE reply_to = \'0\'
					 AND status = \'1\'
					 AND user = ?
					 ORDER BY `time` DESC
					 LIMIT 3',
					$this->user->id
				);
		while( $audio = $recent_audios_by_user->r->fetch_assoc() ) {
			$result[] = $this->complete_audio($audio);
		}
		return $result;
	}
	/**
	* Returns an array with the
	* 3 most listened audios of
	* the last 30 days
	* @return array
	**/
	public function get_popular_audios() {
		$result          = array();
		$recents_popular = $this->db->query(
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
			$result[] = $this->complete_audio($audio);
		}
		return $result;
	}

	/************************ loaders *******************/

	/**
	* Returns an array with the last 10 audios of $user_id
	*
	* @param $user_id - The ID of the user
	* @param $page    - The page number
	* @return array
	*
	**/
	public function load_audios( $user_id, $page = 1) {
		$query = "SELECT id,user,audio,reply_to,description,
						 time,plays,favorites,duration
					FROM audios
				  WHERE user = ?
				  AND reply_to = '0'
				  AND status = '1'
				  ORDER BY time DESC";
		$count = $this->db->query(
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
		$audios = $this->db->query($query, $user_id);
		$result = array(
				'audios' => array()
			);
		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = $this->complete_audio($audio);
		}
	
		$result['load_more'] = $page < $total_pages;
		$result['page'] 	 = $page + 1;
		$result['total'] 	 = $count;
		return $result;
	}

	/**
	* Returns an array with the last 10 replies of $audio_id
	*
	* @param $user_id - The ID of the user
	* @param $page    - The page number
	* @return array
	*
	**/
	public function load_replies( $audio_id, $page = 1 ) {
		$query = "SELECT id,user,audio,reply_to,description,
						 time,plays,favorites,duration
					FROM audios
				  WHERE reply_to = ?
				  AND status = '1'
				  ORDER BY time DESC";
		$count = $this->db->query(
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
		$audios = $this->db->query($query, $audio_id);
		$result = array(
				'audios'	=>	array()
			);
		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = $this->complete_audio($audio);
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
	public function load_favorites( $user_id, $page = 1 ) {
		$query = "SELECT DISTINCT A.* FROM audios
					AS A INNER JOIN favorites AS F ON A.id = F.audio_id
					AND F.user_id = ? AND A.status = '1'
					ORDER BY F.time DESC";
		$count = $this->db->query(
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
		$audios = $this->db->query($query, $user_id);
		$result = array(
				'audios'	=>	array()
			);
		while( $audio = $audios->r->fetch_assoc() ) {
			$result['audios'][] = $this->complete_audio($audio);
		}
	
		$result['load_more'] = $page < $total_pages;
		$result['page']      = $page + 1;
		$result['total']     = $count;
		return $result;
	}
	
	/** actions ! **/

	public function create_audio( array $options ) {
		$required_options = array(
				'audio_url', 'description', 'duration',
				'is_voice', 'send_to_twitter'
			);
		if( 0 !== count(
					array_diff( $required_options, array_keys($options) )
					)
			) {
			return false;
		}

		$audio_id = generate_id_for('audio');

		$this->db->insert("audios", array(
				'id'          => $audio_id,
				'user'        => $this->user->id,
				'audio'       => $options['audio_url'], // nameofthefile.mp3
				'reply_to'    => 0,
				'description' => $options['description'],
				'tw_id'       => 0,
				'time'        => time(),
				'plays'       => 0,
				'favorites'   => 0,
				'duration'    => (string) $options['duration'],
				'is_voice'    => (string) (int) $options['is_voice']
			)
		);

		if( '1' == $options['send_to_twitter'] ) {
			// magic!
			$twitter = new Twitter(
					$this->user->access_token,
					$this->user->access_token_secret
				);
			$tweet = 'https://twitaudio.com/'. $audio_id;
			$tweet_length = strlen($tweet);
			$description = $options['description'];
			if( strlen($description) > (140-$tweet_length) )
				$description = substr(
						$description,
						0,
						140-$tweet_length-4
					) . '...';
			$tweet = $description . ' ' . $tweet;
			if( $tweet_id = $twitter->tweet($tweet) )
				$this->db->update("audios", array(
						"tw_id" => $tweet_id
					)
				)->where("id", $audio_id)->_();
		}
		return true;
	}

	public function reply_audio( array $options ) {

		$required_options = array(
				'audio_id', 'send_to_twitter', 'reply', 'user_id', 'tw_id'
			);
		if( 0 !== count(
				array_diff( $required_options, array_keys($options) )
				)
		) {
			return false;
		}

		$audio_id = generate_id_for('audio');
		$this->db->insert("audios", array(
				'id'          => $audio_id,
				'user'        => $this->user->id,
				'audio'       => '', // audio.mp3 (not used here)
				'reply_to'    => $options['audio_id'],
				'description' => $options['reply'],
				'tw_id'       => 0,
				'time'        => time(),
				'plays'       => 0,
				'favorites'   => 0,
				'duration'    => 0,
				'is_voice'    => '0' // surely its not
			)
		);

		if( $options['send_to_twitter'] ) {

			$twitter = new \application\Twitter(
					$this->user->access_token,
					$this->user->access_token_secret
				);

			$tweet        = ' - https://twitaudio.com/'. $audio_id;
			$tweet_length = strlen($tweet);

			$at = $this->db->query(
				"SELECT user FROM users WHERE id = ?",
				$options['user_id']
			);
			$at = $at->user;

			$reply = "@$at " . $options['reply'];
			if( strlen($reply) > (140-$tweet_length) )
				$desc = substr($desc, 0, 140-$tweet_length-3) . '...';
			$tweet = $reply . $tweet;
			$in_reply_to = $options['tw_id'] !== '' ? $options['tw_id'] : '';
			if( $tweet_id = $twitter->tweet($tweet, $in_reply_to) )
				$this->db->update("audios", array(
						"tw_id" => $tweet_id
					)
				)->where("id", $audio_id)->_();
		}
		$new_reply = $this->get_audio_info(
				$audio_id,
				'id,user,audio,reply_to,description,' .
				'time,plays,favorites,duration'
			);
		return $new_reply;
	}

	/**
	* Will delete an audio or a reply
	*
	* This function is BLIND
	* It will delete the audio without any
	* comprobation.
	* @return void
	**/
	public function delete( array $audio ) {
		$id = $audio['id'];
		$this->db->delete('audios',    array('id'       => $id) )->_();
		$this->db->delete('audios',    array('reply_to' => $id) )->_();
		$this->db->delete('plays',     array('audio_id' => $id) )->_();
		$this->db->delete('favorites', array('audio_id' => $id) )->_();

		if( ! empty($audio['audio']) ) {
			@unlink(
				$_SERVER['DOCUMENT_ROOT'] .
				'/assets/audios/' . $audio['original_name']
			);
		}
	}
	/**
	* Favorites an audio
	* @param $audio_id string
	* @return void
	**/
	public function favorite( $audio_id ) {
		$this->db->query(
			"UPDATE audios SET favorites = favorites+1 WHERE id = ?",
			$audio_id
		);

		$this->db->insert("favorites", array(
				'user_id'  => $this->user->id,
				'audio_id' => $audio_id,
				'time'     => time()
			)
		);
	}
	/**
	* Favorites a tweet
	* @param $audio_id string
	* @return void
	**/
	public function unfavorite( $audio_id ) {
		$this->db->query(
			"UPDATE audios SET favorites = favorites-1 WHERE id = ?",
			$audio_id
		);
		$this->db->query(
			"DELETE FROM favorites WHERE audio_id = ? AND user_id = ?",
			$audio_id,
			$this->user->id
		);
	}
	/**
	* Registers a play for $audio_id
	*
	* @param $audio_id str
	* @return bool
	**/
	public function register_play( $audio_id ) {
		$user_ip    = get_ip(); // ← /application/functions.php
		$was_played = $this->db->query(
				"SELECT COUNT(*) AS size FROM plays
				 WHERE user_ip = ?
				 AND  audio_id = ?",
				$user_ip,
				$audio_id
			);
		$was_played  = (int) $was_played->size;
		if( $was_played ) {
			return false;
		}

		$this->db->query(
			"UPDATE audios SET plays = plays+1 WHERE id = ?",
			$audio_id
		);
		
		$this->db->insert("plays", array(
				'user_ip'  => $user_ip,
				'audio_id' => $audio_id,
				'time'     => time()
			)
		);
		return true;
	}
}
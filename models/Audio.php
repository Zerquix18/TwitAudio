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
	* and how many replies the audio has.
	* And adds it to the object.
	*
	**/
	public function complete_audio( \stdClass $audio ) {
		
		if( property_exists($audio, 'status') )
			unset($audio->status);

		if( property_exists($audio, 'user') ) {

			$users = new \models\User();
			$audio->user = $users->get_user_info(
					$audio->user,
					'id,user,name,avatar,verified,audios_public'
				);
		}

		if( property_exists($audio, 'id') ) {

			if( null !== $this->user ) {
				$favorited = $this->db->query(
					'SELECT COUNT(*) AS size FROM favorites
					 WHERE audio_id = ? AND user_id = ?',
					$audio->id,
					$this->user->id
				);
				$audio->favorited = (bool) (int) $favorited->size;
			}else{
				$audio->favorited = false;
			}

			$replies_count = $this->db->query(
				'SELECT COUNT(*) AS size FROM audios
				WHERE status = \'1\' AND reply_to = ?',
				$audio->id
			);
			$audio->replies_count = (int) $replies_count->size;

		}

		if( ! empty($audio->audio) )
			$audio->audio = url('assets/audios/' . $audio->audio);

		return $audio;
	}
	/** GETS things :O **/
	public function get_audio_info( $id, $whichinfo = '*' ) {

		if( ! preg_match("/^[A-Za-z0-9]{6}$/", $id ) )
			return false;

		$audio = $this->db->select('audios', $whichinfo)
				 ->where( array(
				 		'id'		=> $id,
				 		'status'	=> 1
				 	)
				 )
				 ->execute();
				 
		if( 0 == $audio->nums )
			return false;
		return $this->complete_audio($audio);
	}

	public function get_recent_audios_by_user() {
		$result = array();
		$recent_audios_by_user = $this->db->query(
					'SELECT * FROM audios
					WHERE reply_to = \'0\'
					AND status = \'1\'
					AND user = ?
					ORDER BY `time` DESC
					LIMIT 3',
					$this->user->id
				);
		while( $audio = $recent_audios_by_user->r->fetch_object() )
			$result[] = $this->complete_audio($audio);

		return $result;
	}

	public function get_popular_audios() {
		$result = array();
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
		while( $audio = $recents_popular->r->fetch_object() )
			$result[] = $this->complete_audio($audio);

		return $result;
	}

	/************************ loaders *******************/

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
			if( 0 == $count )
				return array(
						'audios'	 => array(),
						'load_more'  => false,
						'page' 		 => $page,
						'total'		 => $count
					);
			$total_pages = ceil( $count / 10 );
			if( $page > $total_pages )
				return array(
						'audios'	 => array(),
						'load_more'  => false,
						'page' 		 => $page,
						'total'		 => $count
					);

			$query .= ' LIMIT '. ($page-1) * 10 . ',10';
			$audios = $this->db->query($query, $user_id);
			$result = array(
					'audios'	=>	array()
				);
			while( $audio = $audios->r->fetch_object() )
				$result['audios'][] = $this->complete_audio($audio);
	
			$result['load_more'] = $page < $total_pages;
			$result['page'] 	 = $page + 1;
			$result['total'] 	 = $count;
			return $result;
	}
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
			if( 0 == $count )
				return array(
						'audios'	 => array(),
						'load_more'  => false,
						'page' 		 => $page,
						'total'		 => $count
					);

			$total_pages = ceil( $count / 10 );
			if( $page > $total_pages )
				return array(
						'audios'	 => array(),
						'load_more'  => false,
						'page' 		 => $page,
						'total'		 => $count
					);

			$query .= ' LIMIT '. ($page-1) * 10 . ',10';
			$audios = $this->db->query($query, $audio_id);
			$result = array(
					'audios'	=>	array()
				);
			while( $audio = $audios->r->fetch_object() )
				$result['audios'][] = $this->complete_audio($audio);
	
			$result['load_more'] = ($page < $total_pages);
			$result['page'] = $page + 1;
			$result['total'] = $count;
			return $result;
	}
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
			if( 0 == $count )
				return array(
						'audios'	 => array(),
						'load_more'  => false,
						'page' 		 => $page,
						'total'		 => $count
					);

			$total_pages = ceil( $count / 10 );
			if( $page > $total_pages )
				return array(
						'audios'	 => array(),
						'load_more'  => false,
						'page' 		 => $page,
						'total'		 => $count
					);
			
			$query .= ' LIMIT '. ($page-1) * 10 . ',10';
			$audios = $this->db->query($query, $user_id);
			$result = array(
					'audios'	=>	array()
				);
			while( $audio = $audios->r->fetch_object() )
				$result['audios'][] = $this->complete_audio($audio);
	
			$result['load_more'] = $page < $total_pages;
			$result['page'] = $page + 1;
			$result['total'] = $count;
			return $result;
	}
	/** actions ! **/

	public function create_audio( array $options ) {
		$required_options = array(
				'audio_url', 'description', 'duration',
				'is_voice', 'send_to_twitter'
			);
		if( 0 !== count( array_diff( $required_options, array_keys($options) ) ) )
			return false;

		$this->db->insert("audios", array(
				$audio_id = generate_id_for('audio'),
				$this->user->id,
				$options['audio_url'], // nameofthefile.mp3
				0, // reply_to 
				$options['description'],
				0, // twitter id
				time(),
				0, // plays
				0, // favorites
				(string) $options['duration'],
				(string) (int) $options['is_voice']
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
		if( 0 !== count( array_diff( $required_options, array_keys($options) ) ) )
			return false;

		$this->db->insert("audios", array(
				$audio_id = generate_id_for('audio'),
				$this->user->id,
				'', // audio.mp3 (not used here)
				$options['audio_id'], // reply_to
				$options['reply'],
				0,
				time(),
				0,
				0,
				0,
				'0' // is_voice (the answer is no)
			)
		);

		if( $options['send_to_twitter'] ) {

			$twitter = new \application\Twitter(
					$this->user->access_token,
					$this->user->access_token_secret
				);

			$tweet = ' - https://twitaudio.com/'. $audio_id;
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
	**/
	public function delete( \stdClass $audio ) {
		$this->db->query(
			"DELETE FROM audios WHERE id = ?",
			$audio->id
		);
		$this->db->query(
			"DELETE FROM favorites WHERE audio_id = ?",
			$audio->id
		);
		$this->db->query(
			"DELETE FROM plays WHERE audio_id = ?",
			$audio->id
		);
		$this->db->query(
			"DELETE FROM audios WHERE reply_to = ?",
			$audio->id
		);

		if( ! empty($audio->audio) )
			@unlink(
				$_SERVER['DOCUMENT_ROOT'] .
				'assets/audios/' . $audio->audio
			);
	}

	public function favorite( $audio_id ) {
		$this->db->query(
			"UPDATE audios SET favorites = favorites+1 WHERE id = ?",
			$audio_id
		);
		$this->db->insert("favorites", array(
				$this->user->id,
				$audio_id,
				time()
			)
		);
	}

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

	public function register_play( $audio_id ) {
		$was_played = $this->db->query(
				"SELECT COUNT(*) AS size FROM plays
				 WHERE user_ip = ?
				 AND audio_id = ?",
				\get_ip(), // ← /application/functions.php
				$audio_id
			);
		$was_played = (int) $was_played->size;
		if( $was_played )
			return false;

		$this->db->query(
			"UPDATE audios SET plays = plays+1 WHERE id = ?",
			$audio_id
		);
		$this->db->insert("plays", array(
				$ip,
				$id,
				time()
			)
		);
		return true;
	}
}
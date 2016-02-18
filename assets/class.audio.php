<?php
/**
*
* TwitAudio class for audio manipulation
* Requires getID3, SoX & FFMEPG
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2015 Luis A. Martínez
* @since {27/9/2015}
**/

// consider that without this the entire class will fail
require dirname(__FILE__) . '/getid3/getid3.php';

class Audio {

	public $audio;
	
	public $original_name;
	
	public $info;
	
	public $error = false;
	
	public $error_code;

	public $options;

	public $allowed_formats = array('aac', 'mp4', 'mp3', 'ogg', 'wav');

	private $format;

	public function __construct($audio_path, array $options) {
		$id3 = new getID3();
		$this->info = $id3->analyze($audio_path);
		$this->audio = $this->original_name = $audio_path;
		$this->format = last( explode(".", $audio_path) );
		$this->load_options( $options );
		if( $this->options['validate'] )
			$this->validate();
	}
	private function load_options( array $options ) {
		$default_options = array(
				'validate'		=> true,
				'is_voice'		=> false,
				'decrease_bitrate' 	=> false,
				'max_duration'		=> '120',
			);
		$this->options = array_merge( $default_options, $options );
	}
	private function last( array $array ) {
	#PHP Strict Standards:
		#Only variables should be passed by reference
		return end($array); // <- fak u
	}
	private function validate() {
		// if getid3 couldn't get the format or not allowed
		if( ! array_key_exists('fileformat', $this->info)
			|| ! in_array(
				$this->format = $this->info['fileformat'],
				$this->allowed_formats
				)
			) {
			$this->error = __("The format of the audio is not allowed...");
			return false;
		}
		if( $this->format == 'mp4' )
			$this->format = 'm4a'; // same shit

		/** create a new name **/
		$new_name = $this->generate_name(
				$this->info['filenamepath']
			);
		rename($this->audio, $new_name);
		$this->audio = $new_name;
		/** ... **/
		$decrease_bitrate = '';
		if( $this->options['decrease_bitrate'] ) {
			if( in_array($this->format, array(
					'mp3',
					'ogg',
					'wav'
					)
				)
			) {
				$decrease_bitrate = ' -C ';
				$decrease_bitrate .=
				$this->options['is_voice'] ? '64' : '128';
			}
		}
		// correct format done.

		if( 'mp3' === $this->format ) {
			// if mp3, check it's not malformed ...
			$new_name = $this->generate_name($this->audio);
			// remake the file just to see
			// it there's not an EOF
			$l = $this->exec("sox $this->audio $decrease_bitrate $new_name");
			if( trim($l) !== '' ) { // <-- EOF BABE
				unlink($this->audio);
				$this->error = __("There was a problem while proccessing the audio");
				$this->error_code = 2;
				return false;
			}
			unlink($this->audio); //delete the old one
			$this->audio = $new_name;
		}
		// not an mp3... gotta convert it.
		// and check for an EOF at the same time.
		$name = $this->get_name($this->audio);
		if( in_array($this->format,
				array("ogg", "wav", "aac", "m4a") ) ):

			// if the format is ogg or wav, sox can handle it
			if( in_array($this->format, array("ogg", "wav") ) ) {
				$r = $this->exec(
					"sox $this->audio $decrease_bitrate $name.mp3"
				);
			// else, ffmpeg then save us all
			}elseif( in_array($this->format, 
					array("aac", "m4a") ) ) {
				$r = $this->exec("ffmpeg -v 5 -y -i $this->audio -acodec libmp3lame -ac 2 $name.mp3");
		}else // this should never occur ;-;
			return false;
		// they both return an empty response
		// when successful...

		if( trim($r) !== '' ) {
			$this->error = __("There was a problem while proccessing the audio...");
			$this->error_code = 2;
			return false;
		}else{
			unlink($this->audio);
			$this->format = 'mp3';
			$this->audio = $name . '.mp3';
			$id3 = new getID3();
			$this->info = $id3->analyze($this->audio);
		}
		endif;
		$duration = floor($this->info['playtime_seconds']);
		if( 0 == $duration ) {
			$this->error = __('The audio must be longer than 1 second');
			return false;
		}
		## -- should we cut?
		if( $duration > $this->options['max_duration'] ) {
			$this->error = true;
			$this->error_code = 3;
			return false;
		}

	}
	
	public function get_name($name) {
		// get the path without the format
		$name = explode(".", $name);
		array_pop($name);
		$name = implode($name); // get the name
		return $name;
	}
	
	private function generate_name( $base ) {
		// get the path
		$path = explode("/", $base);
		array_pop($path);
		$path = implode("/", $path) . '/';
		// generate the new name
		$name = md5( uniqid() . rand(1,100) );
		$path .= $name . '.' . $this->format;
		return $path;
	}
	
	private function exec( $command ) {
		exec($command . " 2>&1", $output);
		return implode("\n", $output);
	}
	
	public function cut( $start, $end ) {
		if( $this->error )
			return false;
		// full time
		$duration = floor($this->info['playtime_seconds']);
		if( $start < 0 || $end > $duration ) {
			// cannot be cut m8
			$this->error = __("There was an error while cutting your audio...");
			$this->error_code = 8;
			return false;
		}
		$difference = $end-$start;
		// trims...
		$new_name = $this->generate_name( $this->audio );
		$r = $this->exec("sox $this->audio $new_name trim $start $difference");
		$result = trim($result);
		if( ! in_array($r, 
			array("", "sox WARN mp3: MAD lost sync" ) ) ) {
			$this->error = __("Oh snap! There was an error while cutting your audio...");
			$this->error_code = 6;
			return false;
		}
		unlink($this->audio);
		$this->audio = $new_name;
		$id3 = new getID3();
		$this->info = $id3->analyze($this->audio);
		return $this->audio;
	}
}
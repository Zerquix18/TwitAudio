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
	/**
	* Contains the path of the audio
	* @access public
	* @param string
	*
	**/
	public $audio;
	/**
	* Contains the original path of the audio
	* @access public
	* @param string
	*
	**/
	public $original_name;
	/**
	* All the info of getid3
	* @access public
	* @param array
	*
	**/
	public $info;
	/**
	* Max duration of the audio
	* @access public
	* @param integer
	* @todo deprecate this for the premium version
	*
	**/
	public $max_duration = 120; // 2 mins
	/**
	* The string with the error
	* @access public
	* @param string
	*
	**/
	public $error = false;
	/**
	* Error code. Random numbers I put
	* @access public
	* @param string
	*
	**/
	public $error_code;

	public $allowed_formats = array('aac', 'mp4', 'mp3', 'ogg', 'wav');

	public $is_voice;

	private $format;

	public function __construct($audio_path, $valid = false, $is_voice = false) {
		$id3 = new getID3();
		$this->info = $id3->analyze($audio_path);
		$this->audio = $this->original_name = $audio_path;
		$this->format = last( explode(".", $audio_path) );
		$this->is_voice = $is_voice;
		if( ! $valid )
			$this->prepare();
	}
	/**
	* PHP doesn't let me pass expressions
	* with the end function.
	* I can't do this: end( array('lol') )
	* But I can do this $this->last( array('lol') )
	* @access private
	* @return mixed
	*
	**/
	private function last( array $array ) {
	#PHP Strict Standards:
		#Only variables should be passed by reference
		return end($array); // <- fak u
	}
	private function prepare() {
		if( ! array_key_exists('fileformat', $this->info) // <- malformed
			|| ! in_array($this->format = $this->info['fileformat'], $this->allowed_formats) ){
			$this->error = __("Format not allowed");
			return false;
		}
		if( $this->format == 'mp4' )
			$this->format = 'm4a';
		$this->audio = $this->generate_name( $this->info['filenamepath'] );
		rename($this->original_name, $this->audio);
		$this->original_name = $this->last( explode("/", $this->original_name) );
		// correct format done.
		// is it mp3??
		if( 'mp3' === $this->format ) {
			// if mp3, check it's not malformed ...
			$new_name = $this->generate_name($this->audio);
			// remake the file just to see
			// it there's not an EOF
			$l = $this->exec("sox $this->audio $new_name");
			if( trim($l) !== '' ) { // <-- MALFORMED
				unlink($this->audio);
				$this->error = __("There was a problem while proccessing the audio");
				$this->error_code = 2;
				return false;
			}
			unlink($this->audio);
			$this->audio = $new_name;
		}
		// not an mp3... gotta convert it.
		$name = $this->get_name($this->audio);
		if( in_array($this->format, array("ogg", "wav", "aac", "m4a") ) ):
		if( in_array($this->format, array("ogg", "wav") ) ) {
			$r = $this->exec("sox $this->audio $name.mp3"); // convert to mp3...
		}elseif( in_array($this->format, array("aac", "m4a") ) ) {
			$r = $this->exec("ffmpeg -v 5 -y -i $this->audio -acodec libmp3lame -ac 2 $name.mp3");
		}else
			return false;
		if( trim($r) !== '' ) { // <-- malformed, a warning was thrown
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
		## --
		if( floor($this->info['playtime_seconds']) > $this->max_duration ) {
			$this->error = true;
			$this->error_code = 3;
			return false;
		}
	}
	/**
	* Gets the name of the file
	* By removing format
	* @access private
	* @return string
	**/
	private function get_name($name) {
		// get the path without the format
		$name = explode(".", $this->audio);
		array_pop($name);
		$name = implode($name); // get the name
		return $name;
	}
	/**
	* Generates a new filename
	* @access private
	* @return string
	**/
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
	/**
	* Executes $command in the shell
	* @access private
	* @return string
	**/
	private function exec( $command ) {
		exec($command . " 2>&1", $output);
		return implode("\n", $output);
	}
	/**
	* Cuts an audio from $start to $end
	* @access public
	* @return string|bool
	**/
	public function cut( $start, $end ) {
		if( $this->error )
			return false;
		// full time
		$t = floor($this->info['playtime_seconds']);
		if( $start < 0 || $end > $t ) {
			// cannot be cut m8
			$this->error = __("There was an error while cutting your audio...");
			$this->error_code = 8;
			return false;
		}
		$d = $end-$start;
		// trims...
		$new_name = $this->generate_name( $this->audio );
		$r = $this->exec($l="sox $this->audio $new_name trim $start $d");
		$r = trim($r);
		if( ! in_array($r, array("", "sox WARN mp3: MAD lost sync" ) ) ) {
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
	/**
	* Decreases the bit rate (filesize)
	* 
	* @access private
	* @return bool
	**/
	private function proccess() {
		// everything is ok
		$condition = $this->is_voice ? 64 : 128;
		if( ($this->info['bitrate'] / 1000) > $condition ) {
			$new_name = $this->generate_name($this->audio);
			$r = $this->exec("sox $this->audio -C $condition $new_name");
			if( trim($r) !== '' ){
				$this->error = __("There was an error while processing your audio");
				$this->error_code = 7;
				return false;
			}
			$this->audio = $new_name;
		}
		return true;
	}
}
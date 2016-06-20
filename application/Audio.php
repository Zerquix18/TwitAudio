<?php
/**
 *
 * TwitAudio class for audio manipulation
 * Requires getID3 and SoX
 *
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/
namespace application;

class Audio
{
    /**
     * The audio.mp3 file
     * @var string
     */
    public $audio;
    /**
     * The original name given in the constructor
     * @var string
     */
    public $original_name;
    /**
     * Info returned by getID3
     * @var array
     */
    public $info;
    /**
     * To check if there was an error
     * @var boolean
     */
    public $error = false;
    /**
     * The error code
     * @var integer
     */
    public $error_code;
    /**
     * Options passed in the constructor
     * @var array
     */
    public $options;
    /**
     * The list of allowed formats
     * @var array
     */
    public static $allowed_formats = array('mp3', 'ogg');
    /**
     * The format of the audio evaluated.
     * @var string
     */
    private $format;
    /**
     * @param string $audio_path
     * @param array  $options
     */
    public function __construct($audio_path, array $options)
    {
        $getid3              = new \getID3();
        $this->info          = $getid3->analyze($audio_path);
        $this->audio         =
        $this->original_name = $audio_path;
        $this->format        = last(explode(".", $audio_path));

        $this->loadOptions($options);

        if ($this->options['validate']) {
            $this->validate();
        }
    }
    /**
     * Loads the option to the array $this->options
     * 
     * @param  array $options
     */
    private function loadOptions(array $options)
    {
        $default_options = array(
                'validate'          => true,
                'is_voice'          => false,
                'decrease_bitrate'  => false,
                'max_duration'      => '120',
            );
        $this->options   = array_merge($default_options, $options);
    }
    /**
     * Validates the current $this->audio checking that it is a real
     * audio and that it doesn't have EOFs.
     * 
     */
    private function validate()
    {
        // if getid3 couldn't get the format or it's not allowed
        if (   ! array_key_exists('fileformat', $this->info)
            || ! in_array(
                    $this->format = $this->info['fileformat'],
                    self::$allowed_formats
                )
            ) {
            $this->error = 'The format of the audio is not allowed...';
            return false;
        }

        $decrease_bitrate = '';
        if ($this->options['decrease_bitrate']) {
            $decrease_bitrate  = ' -C ';
            $decrease_bitrate .= $this->options['is_voice'] ? '64' : '128';
        }
        // correct format done.
        $new_name = self::generateFileName($this->audio);
        // remake the file to find EOF or change formats
        $r = $this->exec(
            "sox $this->audio $decrease_bitrate $new_name"
        );
        if ('' !== trim($r)) {
            $this->error      =
                 "There was a problem while proccessing the audio...";
            $this->error_code = 2;
            return false;
        } else {
            unlink($this->audio);
            $this->format = 'mp3';
            $this->audio  = $new_name;
            $getid3       = new \getID3();
            $this->info   = $getid3->analyze($this->audio);
        }

        $duration = floor($this->info['playtime_seconds']);
        if (0 === $duration) {
            $this->error = 'The audio must be longer than 1 second';
            return false;
        }
        ## -- should we cut?
        if ($duration > $this->options['max_duration']) {
            $this->error      = true;
            $this->error_code = 3;
            return false;
        }
        return true;
    }
    /**
     * Returns the path without the format of $name
     * 
     * @param  string $name A path to the file
     * @return string
     */
    public static function getFileName($name)
    {
        $name = explode(".", $name);
        array_pop($name);
        $name = implode($name);
        return $name;
    }
    /**
     * Generates a name and returns it, using $base to get the path
     * 
     * @param  string $base
     * @return string
     */
    private function generateFileName($base)
    {
        // get the path
        $path = explode("/", $base);
        array_pop($path);
        $path = implode("/", $path) . '/';
        // generate the new name
        $name = md5(uniqid() . rand(1,100));
        $path .= $name . '.mp3';
        return $path;
    }
    /**
     * Executes a command in the system.
     * 
     * @param  string $command
     * @return $string The result of the execution
     */
    private function exec($command)
    {
        exec($command . " 2>&1", $output);
        return implode("\n", $output);
    }
    /**
     * Cuts $this->audio from $start to $end
     * @param string $start Must be a number bigger than 0
     * @param string $end   Must be a number lower than the duration
     *                      of the current audio.
     * @return string The name of the new file. An empty string in case of
     *                error.
    **/
    public function cut($start, $end)
    {
        if ($this->error) {
            return '';
        }
        // full time
        $duration = floor($this->info['playtime_seconds']);
        if ($start < 0 || $end > $duration) {
            // cannot be cut m8
            $this->error      =
            "There was an error while cutting your audio...";
            $this->error_code = 8;
            return '';
        }
        $difference = $end - $start;
        // trims...
        $new_name = $this->generateFileName($this->audio);
        $result   = $this->exec(
                        "sox $this->audio $new_name trim $start $difference"
                    );
            
        $result = trim($result);
        if (! in_array(
                    $result,
                    array("", "sox WARN mp3: MAD lost sync" )
                )
            ) {
            $this->error      = 
            "Oh snap! There was an error while cutting your audio...";
            $this->error_code = 6;
            return '';
        }
        unlink($this->audio);
        $this->audio = $new_name;
        $id3         = new \getID3();
        $this->info  = $id3->analyze($this->audio);
        return $this->audio;
    }

    /************************ static methods *****************/

    /**
     * Applies effects to $filename using $effects
     * 
     * @param  string $filename A path to the audio
     * @param  array  $effects  The list of effects to be applied
     * @return array  A multidimensional array with arrays of two keys:
     *                The PID of the process of the system applying the effect
     *                And the filename of the effect being applied.
     */
    public static function applyEffects($filename, array $effects)
    {
        if (! file_exists($filename)) {
            throw new ProgrammerException(
                    'File does not exist'
                );
        }

        $commands = array(
            /* effect => its command */
            'echo'          => 'sox %s %s echo 0.8 0.9 100 0.3',
            'faster'        => 'sox %s %s speed 1.5',
            'reverse'       => 'sox %s %s reverse',
            'slow'          => 'sox %s %s speed 0.7',
            'reverse_quick' => 'sox %s %s reverse speed 1.5',
            'hilbert'       => 'sox %s %s hilbert -n 11',
            'flanger'       => 'sox %s %s flanger',
            'delay'         => 'sox %s %s delay 2',
            'deep'          => 'sox %s %s deemph',
            'low'           => 'sox %s %s upsample 150',
            'fade'          => 'sox %s %s fade l 3',
            'tremolo'       => 'sox %s %s tremolo 1'
        );
        $result = array();
        //         ↓ don't delete that comma
        foreach ($effects as $effect) {
            $new_name = self::getFileName($filename) . '-' . $effect . '.mp3';
            $execute  = sprintf(
                $commands[$effect],
                $filename,
                $new_name
            );
            exec('nohup ' . $execute .
                " > /dev/null 2> /dev/null & echo $!", $output);
            $pid = end($output);
            $result[$effect] = array(
                'pid'       =>  $pid,
                'filename'  =>  $new_name
            );
        }
        return $result;
    }
    /**
     * Return the list of effect that were already applied
     * @param  string $info The $_SESSION[$id] array
     * @return array
     */
    public static function getFinishedEffects(array $info)
    {
        $result = array();
        foreach ($info as $effect_name => $effect_info) {
            // check if process alive
            exec('ps -p ' . $effect_info['pid'], $output);
            $output = implode("\n", $output);
            if (! strpos($output, 'sox'))
                $result[] = array(
                        'name' => $effect_name,
                        'file'     => $effect_info['filename']
                    );
        }
        return $result;
    }
    /**
     * After a posted audio, cleans all the effects files remaining in
     * the tmp/ dir.
     * 
     * @param  array $session_id The array of $_SESSION[$id]
     */
    public static function cleanTmpDir(array $session_id)
    {
        if (file_exists($session_id['tmp_url'])) {
            unlink($session_id['tmp_url']);
        }
        
        foreach ($session_id['effects'] as $effect_name => $effect_info) {
            unlink($effect_info['filename']);
        }
    }
    /**
     * Returns the list of effects available for everyone.
     * @return array
    **/
    public static function getEffects()
    {
        $names = array(
            'deep'          => 'Deep',
            'delay'         => 'Delay',
            'echo'          => 'Echo',
            'fade'          => 'Fade',
            'faster'        => 'Faster',
            'flanger'       => 'Flanger',
            'hilbert'       => 'Hilbert',
            'low'           => 'Low',
            'reverse'       => 'Reversed',
            'reverse_quick' => 'Reversed and faster',
            'slow'          => 'Slow',
            'tremolo'       => 'Tremolo',
        );
        return $names;
    }
}
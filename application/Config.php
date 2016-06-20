<?php
/**
 * This class has 2 static methods
 * To get and set config in runtime
 * And it loads the config from the config file
 *
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/
class Config
{
    /**
     * Stores the config
     * @var array
     */
    private static $config;
    /**
     * Loads the config if it was not loaded
     */
    public static function loadConfigFile()
    {
        if (null !== self::$config) {
            return;
        }
        $config_file = DOCUMENT_ROOT . '/config.ini';
        if (! is_readable($config_file)) {
            throw new \ProgrammerException(
                    "Can't read $config_file or it does not exist"
                );
        }
        self::$config = parse_ini_file($config_file);
    }
    /**
     * Returns $key from self::config
     * @param  string $key
     * @return mixed  The key of self::$config or NULL if it does not exist.
    **/
    public static function get($key)
    {
        if (null === self::$config) {
            self::loadConfigFile();
        }
        if (! array_key_exists($key, self::$config)) {
            return null;
        }
        return self::$config[$key];
    }
    /**
     * Sets $key and $value to self::$config
     * @param string $key
     * @param mixed  $value
    **/
    public static function set($key, $value)
    {
        if (null === self::$config) {
            self::loadConfigFile();
        }
        self::$config[$key] = $value;
    }
}
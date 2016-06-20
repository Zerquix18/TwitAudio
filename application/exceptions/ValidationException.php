<?php
/**
*
* Exception for validation errors
* This function must be throw and catched by the controllers
* When something is wrong.
* 
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/

class ValidationException extends \Exception
{
    /**
     * Saves the options
     * @var array
     */
    public $options;
    /**
     * @param string $message The exception message
     * @param array  $options  Options for behavior of the catch block
     */
    public function __construct($message = '', array $options = [])
    {
        $default_options = array('code' => 0);
        $this->options   = array_merge($default_options, $options);

        parent::__construct($message, $this->options['code'], null);
    }
}
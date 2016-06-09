<?php
/**
* Home File
* This file loads the main resources
*
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
define("DOCUMENT_ROOT", dirname(__FILE__) );

require DOCUMENT_ROOT . '/application/Config.php';
require DOCUMENT_ROOT . '/application/zerdb.php';
require DOCUMENT_ROOT . '/application/functions.php';
require DOCUMENT_ROOT . '/application/sessions.php';
require DOCUMENT_ROOT . '/vendor/autoload.php';

require DOCUMENT_ROOT . '/application/init.php';
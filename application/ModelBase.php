<?php
/**
* Model Base
* All the models that require the database connection
* Must extend to this class
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright (c) 2016 - Luis A. Martínez
*
**/
namespace application;

abstract class ModelBase {
	public $db;
	// in all clases that extend to this one
	// $this->user will be the logged user :)
	public $user;
	
	protected function __construct() {
		global $db, $_USER;
		$this->db   = $db;
		$this->user = $_USER;
	}
}
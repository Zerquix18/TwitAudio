<?php
/**
* An interface for the models that access to the database
* and are mainly used in the site.
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace application\interfaces;
interface ModelInterface {
	/**
	* Used to complete and clean something after
	* a SELECT. It will clean unuseful data,
	* force some types and add aditional data.
	*
	* @param $array_to_complete array
	* @return array
	**/
	public static function complete( array $array_to_complete );
	/**
	* Used to DELETE something in the database.
	*
	* @param $parameters array
	**/
	public static function delete(  array $parameters );
	/**
	* Used to INSERT something in the database
	* Returns an array with useful data.
	* In case of error, returns an empty array.
	*
	* @param $parameters array - An array with the pair key=>value to insert
	* in the database.
	* @return array
	**/
	public static function insert(   array $parameters );
	/**
	* Used to SELECT something from the database.
	*
	* @param $by string - An ID or username
	* @param $which_columns -An array with the columns
	*  of the database to extract.
	* @return array
	**/
	public static function get( $by, array $which_columns = array() );
	
}
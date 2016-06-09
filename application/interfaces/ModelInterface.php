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
	 * To complete things after a SELECT query. This will force types,
	 * add aditional keys and delete useless stuff after a query.
	 *
	 * @param  array $array_to_complete
	 * @return array
	 */
	public static function complete( array $array_to_complete );
	/**
	 * Deletes something, like an audio or a user.
	 * 
	 * @param  array $id
	 * @throws \Exception if there was a query error
	 * @return bool
	 */
	public static function delete( $id );
	/**
	 * Inserts something in the database.
	 * 
	 * @param  array $parameters
	 * @throws \Exception if there was an query error
	 * @return array Useful information to display after the insert
	 */
	public static function insert(   array $parameters );
	/**
	 * Get something from the database.
	 * 
	 * @param  string $by An ID or user
	 * @param  array  $which_columns A list of columns of the database
	 * to select the info.
	 * @throws  \Exception if there was a query error
	 * @return array  An empty array in case that nothing was found.
	 */
	public static function get( $by, array $which_columns = array() );
	
}
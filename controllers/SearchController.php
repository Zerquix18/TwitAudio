<?php
/**
*
* The Search Controller
* coming soon in cinemas
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace controllers;
use \application\Views, \application\HTTP, \models\Search;

class SearchController {

	public function __construct() {
		$query = HTTP::get('q') ?: '';
		$type  = HTTP::get('t');
		$order = HTTP::get('o');
		if( empty($query) )
			$content = array();
		else {
			$search = new Search;
			$content = $search->do_search( array(
					'query'		=> $query,
					'type'		=> $type,
					'order'		=> $order,
					'page'		=> 1
				)
			);
		}
		Views::load_full_template('search', array(
				'query'		=> $query,
				'type'		=> $type,
				'order' 	=> $order,
				'content' 	=> $content
			)
		);
	}
}
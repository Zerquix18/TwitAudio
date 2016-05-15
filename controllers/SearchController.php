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
use \application\View,
	\application\HTTP,
	\models\Search;
	
class SearchController {

	public function __construct() {
		$query = HTTP::get('q');
		$type  = HTTP::get('t');
		$order = HTTP::get('o');
		
		if( $query ) {
			$content = Search::do_search( array(
					'query'		=> $query,
					'type'		=> $type,
					'order'		=> $order,
					'page'		=> 1
				)
			);
		} else {
			$content = array();
		}
		View::load_full_template('search', array(
				'query'		=> $query,
				'type'		=> $query ? $content['type']  : '',
				'order' 	=> $query ? $content['order'] : '',
				'content' 	=> $content
			)
		);
	}
}
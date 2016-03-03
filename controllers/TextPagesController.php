<?php
/**
* Text pages controller
*
* These pages includes about, terms, policy, etc.
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright (c) 2016 - Luis A. Martínez
*
**/
namespace controllers;

use \application\Views;
/**
*
* There is a place in the distance
* A place that I've dreaming of
*
**/
class TextPagesController {
	public function __construct( $page ) {
		$file = $_SERVER['DOCUMENT_ROOT'] .
			'/application/html/' .$page . '.html';
			
		if( ! file_exists($file ) || ! is_readable($file) )
			Views::exit_404();

		$template = array(
				'text'		=>		file_get_contents( $file ),
				'page'		=> 		$page
			);

		Views::load_full_template('text', $template);
	}
}
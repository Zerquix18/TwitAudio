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

use \application\View;
/**
*
* There is a place in the distance
* A place that I've dreaming of
*
**/
class TextPagesController {
	public function __construct( $page ) {
		$file = $_SERVER['DOCUMENT_ROOT'] .
			'/templates/html/' . $page . '.html';
			
		if( ! is_readable($file) ) {
			View::exit_404();
		}

		$text = file_get_contents($file);
		$text = nl2br($text);

		$template = array(
				'text' => $text,
				'page' => $page
			);

		View::load_full_template('text', $template);
	}
}
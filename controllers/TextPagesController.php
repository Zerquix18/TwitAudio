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
		try {
			$file = $_SERVER['DOCUMENT_ROOT'] .
			'/application/html/text/' . $page . '.html';
			
			if( ! is_readable($file) ) {
				View::exit_404();
			}

			$text   = file_get_contents($file);
			$text   = nl2br($text);

			$bars   = array('text' => $text);

			$titles = array(
				'about' 	=> 'About',
				'terms' 	=> 'Terms of Service',
				'privacy' 	=> 'Privacy Policy',
				'faq' 		=> 'FAQ',
				'contact' 	=> 'Contact',
				'licensing' => 'Licensing',
			);
			$title = str_replace(
							array_keys($titles),
							array_values($titles),
							$page
						);

			View::set_page('text');
			View::set_title($title);
			echo View::get_group_template('main/text', $bars);

		} catch ( \Exception $e ) {
			// database error or template error :c
			if( \Config::get('is_production') ) {
				View::exit_500();
			} else {
				echo $e->getMessage(), PHP_EOL;
			}//if
		}//catch
	}//__construct
}//class
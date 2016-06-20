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
class TextPagesController
{
    
    public function __construct($page)
    {
        $file = DOCUMENT_ROOT .
        '/application/html/text/' . $page . '.html';
        
        if (! is_readable($file)) {
            View::exit404();
        }

        $text   = file_get_contents($file);
        $text   = nl2br($text);

        $bars   = array('text' => $text);

        $titles = array(
            'about'     => 'About',
            'terms'     => 'Terms of Service',
            'privacy'   => 'Privacy Policy',
            'faq'       => 'FAQ',
            'contact'   => 'Contact',
            'licensing' => 'Licensing',
        );
        $title = str_replace(
                        array_keys($titles),
                        array_values($titles),
                        $page
                    );

        View::setPage('text');
        View::setTitle($title);
        echo View::getGroupTemplate('main/text', $bars);
    }//__construct
}//class
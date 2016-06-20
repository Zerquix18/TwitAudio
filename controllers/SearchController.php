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

use \application\View;
use \application\HTTP;
use \models\Search;
    
class SearchController
{

    public function __construct()
    {
        $query = HTTP::get('q');
        $type  = HTTP::get('t');
        $order = HTTP::get('o');
            
        if ($query) {
            $results = Search::doSearch(array(
                    'query'     => $query,
                    'type'      => $type,
                    'order'     => $order,
                    'page'      => 1
                )
            );
        } else {
            $results = array();
        }
        $bars = array(
                'search' => array(
                    'query'            => $query,
                    'query_urlencoded' => rawurlencode($query),

                    'is_audios' => 'a' == $results['type'],
                    'is_users'  => 'u' == $results['type'],

                    'by_date'   => 'd' == $results['order'],
                    'by_plays'  => 'p' == $results['order'],

                    'results'   => $results
                )
            );
        $title = $query . ' Search';

        View::setPage('search');
        View::setTitle($title);
        echo View::getGroupTemplate('main/search', $bars);
    }//__construct
}//class
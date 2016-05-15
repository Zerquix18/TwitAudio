<?php
/**
* Search model
* Yea, this model was made because it supports
* both users and audios and I could not
* decide whether to put it on the Audios Model
* or the Users model. Anyway, being here
* will be better for a future with better searches.
*
* @author Zerquix18 <malbertoa_11@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace models;
use \models\Users,
	\models\Audios;
	
class Search {

	public static function do_search( array $options ) {
		/*
		* Sometimes you just hate the PHP inconsistency.
		* property_exists( $haystack, $needle )
		* but then array_key_exists( $needle, $haystack )
		* wtf they smooking
		*/
		if(    ! array_key_exists('query', $options)
			|| ! array_key_exists('page',  $options)
		) {
			trigger_error('Missing options query or page');
			return array();
		}

		$criteria = $options['query'];
		$criteria = trim( $criteria, "\x20\x2A\t\n\r\0\x0B" );
		$criteria = '*' . $criteria . '*'; // wildcards.
		$page     = $options['page'];

		/**
		* 2 types (a=audios, u=users)
		**/
		if(    array_key_exists('type', $options)
			&& in_array($options['type'], array('a','u') )
		) {
			$type = $options['type'];
		} else {
			$type = 'a';
		}
		/**
		* 2 orders: (d=date,p=plays)
		**/
		if(    array_key_exists('order', $options)
			&& in_array($options['order'], array('d','p') )
		) {
			$order = $options['order'];
		}else{
			$order = 'd';
		}

		if( 'a' == $type ) {
			$query = 'SELECT id,user,audio,reply_to,description,
							 time,plays,favorites,duration
								FROM audios
					  WHERE reply_to = \'0\'
					  AND status = \'1\'
					  AND MATCH(`description`)
						AGAINST (? IN BOOLEAN MODE)';
			$count = db()->query(
					'SELECT COUNT(*) AS size FROM audios
					 WHERE reply_to = \'0\'
					 AND status = \'1\'
					 AND MATCH(`description`)
					 AGAINST (? IN BOOLEAN MODE)',
					$criteria
				);
			} else {
				$query = 'SELECT user,name,avatar,bio,verified FROM users
						  WHERE MATCH(`user`, `name`, `bio`)
						  AGAINST (? IN BOOLEAN MODE)';
				$count = db()->query(
						'SELECT COUNT(*) AS size FROM users
					     WHERE MATCH(`user`, `name`, `bio`)
					     AGAINST (? IN BOOLEAN MODE)',
						$criteria
				);
		}
		$count = (int) $count->size;
		if( 0 == $count ) {
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count,
					'type'       => $type,
					'order'      => $order
				);
		}
		$total_pages = ceil( $count / 10 );
		if( $page > $total_pages )
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count,
					'type'       => $type,
					'order'      => $order
				);
		if( 'a' == $type ) {
			// if the type is audios then we can sort
			if( 'd' == $order ) {
				$query .= ' ORDER BY time DESC';
			} else {
				$query .= ' ORDER BY plays DESC';
			}
			// ..
		}

		$result = array(
				'audios'	=> array()
			);
		$query       .= ' LIMIT '. ($page-1) * 10 . ',10';
		$search       = db()->query($query, $criteria);
		$current_user = Users::get_current_user();
		while( $res = $search->r->fetch_assoc() ) {
			// now we have the result
			// we got to know which function to call
			if( 'a' === $type ) {
				if( $current_user->can_listen($res['user']) ) {
					$result['audios'][] = Audios::complete($res);
				}
			}else{ // if looking for users
				$result['audios'][] = Users::complete($res);
			}
		}
		$result['page']      = $page;
		$result['load_more'] = $page < $total_pages;
		$result['type']      = $type;
		$result['order']     = $order;
		$result['total']     = $count;
		return $result;
	} // end constructor
} // end class
// end your life
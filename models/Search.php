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
use \models\User, \models\Audio;

class Search extends \application\ModelBase {

	public function __construct() {
		parent::__construct();
	}

	public function do_search( array $options ) {
		/*
		* Sometimes you just hate the PHP inconsistency.
		* property_exists( $haystack, $needle )
		* but then array_key_exists( $needle, $haystack )
		* wtf they smooking
		*/
		if( ! array_key_exists('query', $options)
			|| ! array_key_exists('page', $options) )
			return false;

		$criteria = $options['query'];
		$criteria = trim( $criteria, "\x20\x2A\t\n\r\0\x0B" );
		$criteria = '*' . $criteria . '*'; // wildcards.
		$page  = $options['page'];

		/**
		* 2 types (a=audios, u=users)
		**/
		if( array_key_exists('type', $options)
			&& in_array($options['type'], array('a','u'), true ) )
			$type = $options['type'];
		else
			$type = 'a';
		/**
		* 2 orders: (d=date,p=plays)
		**/
		if( array_key_exists('sort', $options)
			&& in_array($options['sort'], array('d','p'), true ) )
			$sort = $options['sort'];
		else
			$sort = 'd';

		if( 'a' == $type ):
			$query = 'SELECT id,user,audio,reply_to,description,
							 time,plays,favorites,duration
								FROM audios
					  WHERE reply_to = \'0\'
					  AND status = \'1\'
					  AND MATCH(`description`)
						AGAINST (? IN BOOLEAN MODE)';
			$count = $this->db->query(
					'SELECT COUNT(*) AS size FROM audios
					 WHERE reply_to = \'0\'
					 AND status = \'1\'
					 AND MATCH(`description`)
					 AGAINST (? IN BOOLEAN MODE)',
					$criteria
				);
			else: // if the type is user
				$query = 'SELECT user,name,avatar,bio,verified FROM users
						  WHERE MATCH(`user`, `name`, `bio`)
						  AGAINST (? IN BOOLEAN MODE)';
				$count = $this->db->query(
						'SELECT COUNT(*) AS size FROM users
					     WHERE MATCH(`user`, `name`, `bio`)
					     AGAINST (? IN BOOLEAN MODE)',
						$criteria
				);
		endif;
		$count = (int) $count->size;
		if( 0 == $count )
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count,
					'type'       => $type,
				);
		$total_pages = ceil( $count / 10 );
		if( $page > $total_pages )
			return array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count,
					'type'       => $type,
				);
		if( 'a' == $type ):
			if( 'd' == $sort )
				$query .= ' ORDER BY time DESC';
			else
				$query .= ' ORDER BY plays DESC';
		endif;

		$result = array(
				'audios'	=> array()
			);
		$query .= ' LIMIT '. ($page-1) * 10 . ',10';
		$search = $this->db->query($query, $criteria);
		$users_model  = new User;
		$current_user = $users_model->get_current_user();
		$audios_model = new Audio;
		while( $res = $search->r->fetch_assoc() ) {
			if( 'a' === $type ):
				if( $current_user->can_listen($res['user']) ):
					$result['audios'][] =
							$audios_model->complete_audio( $res );
				endif;
			else: // if looking for users
				$result['audios'][] = $users_model->complete_user($res);
			endif;
		}
		$result['page']		 = $page;
		$result['load_more'] = $page < $total_pages;
		$result['type'] 	 = $type;
		$result['total']	 = $count;
		return $result;
	} // end constructor
} // end class
// end your life
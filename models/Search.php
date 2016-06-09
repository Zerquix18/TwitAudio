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
	/**
	 * Performs a search
	 * Options are:
	 * 'query'
	 * 'page'
	 * 'sort' (d=date,p=plays)
	 * 'type' (a=audios,u=user)
	 * 
	 * @param  array  $options
	 * @return array
	 */
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
			throw new \ProgrammerException('Missing options query or page');
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
		} else {
			$order = 'd';
		}

		if( 'a' == $type ) {
			$count = db()->query(
					"SELECT
						COUNT(*) AS size
					 FROM audios
					 WHERE reply_to IS NULL
					 AND status = '1'
					 AND MATCH(`description`)
					 AGAINST (? IN BOOLEAN MODE)",
					$criteria
				);
		} else {
			$count = db()->query("
					SELECT
						COUNT(*) AS size
					FROM users
					WHERE MATCH(`username`, `name`, `bio`)
					AGAINST (? IN BOOLEAN MODE)
					AND status = '1'
					",
					$criteria
				);
		}
		if( ! $count ) {
			throw new \DBException('SELECT COUNT search error');
		}
		$count  = (int) $count->size;
		$result = array(
					'audios'	 => array(),
					'load_more'  => false,
					'page' 		 => $page,
					'total'		 => $count,
					'type'       => $type,
					'order'      => $order
				);
		if( 0 == $count ) {
			return $result;
		}
		$total_pages = ceil( $count / 10 );

		if( $page > $total_pages ) {
			return $result;
		}

		if( 'a' == $type ) {
			$columns = Audios::$columns;
			$columns = implode(',', $columns);
			$query = "
					SELECT
						%s
					FROM audios
					WHERE reply_to IS NULL
					AND   status = '1'
					AND   MATCH(`description`)
					AGAINST (? IN BOOLEAN MODE)";
			// if the type is audios then we can sort
			if( 'd' == $order ) {
				$query .= ' ORDER BY date_added DESC';
			} else {
				$query .= ' ORDER BY plays DESC';
			}
			// ..
		} else {
			$columns = 'username, name, avatar, bio, is_verified';
			$query   = "
					SELECT
						%s
					FROM users
					WHERE status = '1'
					AND   MATCH(`username`, `name`, `bio`)
					AGAINST (? IN BOOLEAN MODE)";
		}
		$query      .= ' LIMIT %d, %d';
		$query       = sprintf(
								$query,
								$columns,
								($page-1) * Audios::$per_page,
								Audios::$per_page
							);
		$search       = db()->query($query, $criteria);

		if( ! $search ) {
			throw new \DBException('SELECT search error');
		}

		$current_user = Users::get_current_user();
		
		while( $res = $search->r->fetch_assoc() ) {
			// now we have the result
			// we got to know which function to call
			if( 'a' === $type ) {
				if( $current_user->can_listen($res['user_id']) ) {
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
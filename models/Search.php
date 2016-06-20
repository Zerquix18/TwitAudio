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

use \models\Users;
use \models\Audios;
use PDO;
    
class Search
{
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
    public static function doSearch(array $options)
    {
        /*
        * Sometimes you just hate the PHP inconsistency.
        * property_exists($haystack, $needle)
        * but then array_key_exists($needle, $haystack)
        * wtf they smooking
        */
        if (   ! array_key_exists('query', $options)
            || ! array_key_exists('page',  $options)
        ) {
            throw new \ProgrammerException('Missing options query or page');
        }

        $criteria = $options['query'];
        $criteria = trim($criteria, "\x20\x2A\t\n\r\0\x0B");
        $criteria = '*' . $criteria . '*'; // wildcards.
        $page     = $options['page'];

        /**
        * 2 types (a=audios, u=users)
        **/
        if (   array_key_exists('type', $options)
            && in_array($options['type'], array('a','u') )
        ) {
            $type = $options['type'];
        } else {
            $type = 'a';
        }
        /**
        * 2 orders: (d=date,p=plays)
        **/
        if (   array_key_exists('order', $options)
            && in_array($options['order'], array('d','p') )
        ) {
            $order = $options['order'];
        } else {
            $order = 'd';
        }

        if ('a' == $type) {
            $query = db()->prepare(
                    "SELECT
                        COUNT(*)
                     FROM audios
                     WHERE reply_to IS NULL
                     AND status = '1'
                     AND MATCH(`description`)
                     AGAINST (:criteria IN BOOLEAN MODE)"
                );
        } else {
            $query = db()->prepare("
                    SELECT
                        COUNT(*)
                    FROM users
                    WHERE MATCH(`username`, `name`, `bio`)
                    AGAINST (:criteria IN BOOLEAN MODE)
                    AND status = '1'"
                );
        }
        $query->bindParam('criteria', $criteria);
        $query->execute();

        $count  = (int) $query->fetchColumn();

        $result = array(
                    'audios'     => array(),
                    'load_more'  => false,
                    'page'       => $page,
                    'total'      => $count,
                    'type'       => $type,
                    'order'      => $order
                );
        if (0 == $count) {
            return $result;
        }
        $total_pages = ceil( $count / 10 );

        if ($page > $total_pages) {
            return $result;
        }

        if ('a' == $type) {
            $columns = Audios::$columns;
            $columns = implode(',', $columns);
            $query   = "SELECT
                            {$columns}
                        FROM audios
                        WHERE reply_to IS NULL
                        AND   status = '1'
                        AND   MATCH(`description`)
                        AGAINST (:criteria IN BOOLEAN MODE)";
            // if the type is audios then we can sort
            if ('d' == $order) {
                $query .= ' ORDER BY date_added DESC';
            } else {
                $query .= ' ORDER BY plays DESC';
            }
            // ..
        } else {
            $columns = 'username, name, avatar, bio, is_verified';
            $query   = "SELECT
                            {$columns}
                        FROM users
                        WHERE status = '1'
                        AND   MATCH(`username`, `name`, `bio`)
                        AGAINST (:criteria IN BOOLEAN MODE)";
        }
        $query .= ' LIMIT :skip, :max';
        $query  = db()->prepare($query);
        $query->bindValue('criteria', $criteria);
        $query->bindValue(
                            'skip',
                            ($page - 1) * Audios::$per_page,
                            PDO::PARAM_INT
                        );
        $query->bindValue('max', Audios::$per_page, PDO::PARAM_INT);
        $query->execute();

        $current_user = Users::getCurrentUser();
        
        while ($res = $query->fetch(PDO::FETCH_ASSOC)) {
            // now we have the result
            // we got to know which function to call
            if ('a' === $type) {
                if ($current_user->canListen($res['user_id'])) {
                    $result['audios'][] = Audios::complete($res);
                }
            } else { // if looking for users
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
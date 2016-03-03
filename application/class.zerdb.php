<?php
/**
*
* MySQLi Database Class (ZerDB)
*
* @author Zerquix18
* @version 2.0
* @link http://github.com/zerquix18/zerdb
* @copyright Copyright (c) 2014-2016, Zerquix18
*
* I ask for forgiveness to ayone reading this code
* I made it when I was 14-15 years old
* And it's the greatest and usefull bullshit
* that I've done.
**/

if( ! class_exists('mysqli') )
	exit("Class MySQLi doesn't exist");

class zerdb {

/**
*
* Database tables, for insert queries
*
* @since 1.0
* @access public
* @var array
*
**/
	public $tablas = array(
			"users" => array(
					'id', 'user', 'name', 'avatar', 'bio', 'verified', 'access_token', 'access_token_secret', 'favs_public', 'audios_public', 'time', 'lang'
				),
			"audios" => array(
					'id', 'user', 'audio', 'reply_to', 'description', 'tw_id', 'time', 'plays', 'favorites', 'duration', 'is_voice'
				),
			"favorites" => array(
					'user_id', 'audio_id', 'time'
				),
			"plays" => array(
					'user_ip', 'audio_id', 'time'
				),
			"blocks" => array(
					'user_id', 'blocked_id', 'time'
				),
			"sessions" => array(
					'user_id', 'sess_id', 'time', 'ip', 'is_mobile'
				),
			"following_cache" => array(
					'user_id', 'following', 'time', 'result'
				),
			"trends" => array(
					'user', 'trend', 'time'
				),
		);
	private $dbhost;

	private $dbuser;

	private $dbpass;

	private $dbname;

	private $dbport;
/**
*
* Affected rows of the last query OR the nums of rows selected in the last query.
*
* @since 1.0
* @access public
* @var int
*
**/
	public $nums = null;

/**
*
* Is the connection done and right?
*
* @since 1.0
* @access public
* @var bool
*
**/
	public $ready = false;
/**
*
* Access to MySQLi resource
*
* @since 1.0
* @access public
* @var object
*
**/
	public $mysqli;
/**
*
* Last query you made or the current query you're making.
*
* @since 1.0
* @access public
* @var string|null
*
**/
	public $query;
/**
*
* Last error
*
* @since 1.0
* @access public
* @var string
*
**/
	public $error;
/**
*
* Last error number
*
* @since 1.0
* @access public
* @var string
*
**/
	public $errno;

	public $dbcharset;
/**
*
* Last ID inserted (if there's one).
*
* @since 1.0
* @access public
* @var public
*
**/
	public $id;
/**
*
* Class constructor.
*
* @param string $dbhost
* @param string $dbuser
* @param string $dbpass
* @param string $dbname
* @param string $dbcharset
* @param integer $dbport
*
**/
	public function __construct(
			$dbhost, $dbuser, $dbpass,
			$dbname, $dbcharset = null, $dbport = null ) {
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpass = $dbpass;
		$this->dbname = $dbname;
		$this->dbcharset = ! is_null($dbcharset) ? $dbcharset : 'utf8';
		$this->dbport = is_null($dbport) ? ini_get('mysqli.default_port') : (int) $dbport;
		return $this->connect();
	}

/**
*
* It connects to the database.
*
* @access private
* @return bool true if everything is done and false if there was an error
*
**/
	private function connect() {
		$this->mysqli = @new mysqli( $this->dbhost, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport);
    		if( $this->mysqli->connect_error ) {
			$this->error = $this->mysqli->connect_error;
			$this->errno = $this->mysqli->connect_errno;
			return false;
		}
		$this->mysqli->set_charset( $this->dbcharset );
		$this->ready = true;
		return true;
  	}
/**
*
* It closes the connection
*
* @return bool
*
**/
	public function close() {
		if( $this->ready )
			return $this->mysqli->close();
		return false;
	}

/**
*
* It cleans some vars to use it again
*
* @return bool
*
**/
	public function flush() {
		$this->query =
		$this->error  =
		$this->errno =
		$this->nums = null;
	}
/**
*
* It scapes the special characters in a string
*
* @access public
* @param string $string
* @return string
*
**/
	public function real_escape( $string ) {
		if( ! is_string( $string ) )
			return $string;
		return $this->ready ? $this->mysqli->real_escape_string( $string ) : addslashes( $string );
	}
/**
*
* It makes a query.
*
* @param string $query
* @param mixed $args
* @return bool|object
*
**/
	public function query( $query = null, $args = '' ) {
		if( is_null($query) )
			return false;
		$args = func_get_args();
		array_shift($args);
		if( ! empty($args) && ! preg_match("/(\%)(s|d|f)|[\?]/", $query ) )
			return false;
		$this->flush();
		if( ! empty($args) ) {
			if( is_array($args[0]) )
				$args = $args[0];
			$args = array_map(
					array($this, 'real_escape'),
					$args
				);
			$rplc = array("%s", "%d", "%f", "'?'", "'%s'", "'%d'", "'%f'"); // deletes mistakes
			$query = str_replace($rplc, "?", $query); // replace all that by: ? :)
			if( preg_match_all("!\?!", $query) !== count($args) )
				return false;
			foreach($args as $a) {
				$a = is_string($a) ? "'$a'" : $a; // if it's string it will pass it like an string eh... be careful.
				$query = preg_replace("/\?/", $a, $query, 1); // Replaces all ? by args in order... that's why the count have to be the same
			}
		}			
		$this->query = $query;
		return $this->execute();
	}
/**
*
* It executes the query loaded in $this->query
*
* @return bool|object
*
**/
	public function execute() {
		if( empty($this->query ) )
			return false;
		$query = $this->mysqli->query( $this->query );
		if( !$query ) {
			$this->error = $this->mysqli->error;
			$this->errno = $this->mysqli->errno;
			return false;
		}
		$this->id = $this->mysqli->insert_id; // dw if it's null...
		$this->nums = (int) $this->mysqli->affected_rows;
		if( preg_match('/^(select)/i', $this->query) ) {
			$lol = new stdClass();
			$this->nums = $lol->nums = $query->num_rows;
			$lol->r = $query;
			if( $this->nums == 1):
				foreach($query->fetch_array() as $a => $b)
					$lol->$a = stripslashes($b);
				$query->data_seek(0);
			endif;
			return $lol;
		}
		return $query;
	}
/**
*
* Alias for $this->execute()
*
**/
	public function _() {
		return $this->execute();
	}
/**
*
* It selects something from the database
*
* @param string $table
* @param string $data
* @param array|null
* @return bool|object
*
**/
	public function select( $table, $data = "*", $where = null ) {
		if( ! $this->ready )
			return false;
		$this->flush();
		$this->query = "SELECT $data FROM $table";
		if( null != $where )
			$this->where( $where );
		return $this;
	}
/**
*
* It updates something in the database
*
* @param string $table
* @param string|array $arg1
* @param string $arg2
* @return bool|object
*
**/
	function update( $table, $arg1, $arg2 = '') {
		if( ! $this->ready )
			return false;
		if( ! is_array($arg1) && count( func_get_args() ) !== 3 )
			return false;
		$this->flush();
		$this->query = "UPDATE {$table}";
		$set = array();
		if( empty($arg2) )
			foreach($arg1 as $a => $b) {
				if( is_string($b) )
					$b = $this->real_escape($b);
				$set[] = "{$a} = '{$b}'";
			}
		else {
			if( is_string($arg2) )
				$arg2 = $this->real_escape($arg2);
			$set = array("{$arg1} = '{$arg2}'");
		}
		$this->query .= " SET " . implode(', ', $set);
		return $this;
	}
/**
*
* It deletes something in the database
*
* @param string $table
* @param null|array $arg2
* @return object
*
**/
	public function delete( $table, $where = null) {
		if( ! $this->ready )
			return false;
		$this->flush();
		$this->query = "DELETE FROM {$table}";
		if( is_array($where) )
			$this->where( $where );
		return $this;
	}

/**
*
* It inserts something in the database
*
* @param string $table
* @param array $t_data
* @param array $data
* @return bool
*
**/
	public function insert( $table, $data) {
		return $this->insert_replace("INSERT", $table, array_slice( func_get_args(), 1 ) );
	}
/**
*
* It replaces something in the database
*
* @param string $table
* @param array $t_data
* @param array $data
* @return bool
*
**/
	public function replace( $table, $data ) {
		return $this->insert_replace("REPLACE", $table, array_slice( func_get_args(), 1 ) );
	}
/**
*
* Helper for insert and replace
*
**/
	private function insert_replace($action, $table, $data) {
		if( ! $this->ready )
			return false;
		if( empty($data) )
			return false;
		if( ! in_array( strtoupper($action), array("INSERT", "REPLACE") ) ) 
			return false;
		//then
		$t_data = $this->tablas[$table];
		$this->query = "{$action} INTO {$table} (`" . implode('`,`', $t_data) . "`) VALUES ";
		$v = array();
		foreach($data as $a) {
			$a = array_map( array($this, 'real_escape'), $a);
			$v[] = "('" . implode("','", $a ) . "')";
		}
		$this->query .= implode(', ', $v);
		return $this->execute();
	}
/**
*
* It adds WHERE to the loaded query. 
*
* @param array|string $arg1
* @param string $arg2
* @param string $arg3
* @return bool|object
*
**/
	public function where( $column, $value = '', $operator = "AND") {
		$args = func_get_args();
		if( ! is_array($column) && empty($value) )
			return false;
		$ops = array(">", "<", "!=", "=");
		if( in_array($value, $ops) )
			$op = $value;
		else
			$op = "=";
		if( is_array($column) ) {
			$wh = array();
			foreach($column as $col => $val){
				if( is_string($val ) )
					$val = $this->real_escape($val);
				$val = !is_string($val) ? "{$val}" : "'{$val}'";
				$wh[] = "{$col} {$op} {$val}";
			}
			$this->query .= " WHERE " . implode(" {$operator} ", $wh);
		}else{
			if( is_string($args[1]) )
				$args[1] = $this->real_escape($args[1]);
			$this->query .= " WHERE {$args[0]} {$op} '{$args[1]}'";
		}
		return $this;
	}

/**
*
* This is the same that where() but this adds "WHRE something != 'somethin'"
*
* @param array $where
*
**/
	public function wherenot( $where ) {
		return $this->where($where, "!=");
	}

/**
*
* This is the same that where() but this adds "WHRE something > number"
*
* @param array $where
*
**/
	public function wheremt( $where ) {
		return $this->where($where, ">");
	}

/**
*
* This is the same that where() but this adds "WHRE something < number"
*
* @param array $where
*
**/
	public function wherelt( $where ) {
		return $this->where($where, "<");
	}
/** 
*
* LIKE statement
*
* @param string|array $arg1
* @param string|bool $arg2
* @param string $and
* @param bool $not
* @return bool|object
*
**/
	public function like($arg1, $arg2 = false, $and = "AND", $not = false) {
		if( ! is_array($arg1) && empty($arg2) )
			return false;
		$args = func_get_args();
		$like = array();
		$not = (!$not) ? '' : 'NOT ';
		if( is_array($arg1) ) {
			foreach($arg1 as $a => $b)
				$like[] = "$a {$not} LIKE %" . $b . "%";
		}else{
			$like[] = "{$args[0]} {$not}LIKE '%{$args[1]}%'";
		}
		$this->query .= " WHERE " . implode(' {$and} ', $like );
		return $this;
	}
/**
*
* LIKE statement, but just starting ("WHERE something LIKE test%")
*
* @param string|array $arg1
* @param string|bool $arg2
* @param string $and
* @param bool $not
* @return bool|object
*
**/
	public function slike( $arg1, $arg2 = false, $and = "AND", $not = false) {
		if( ! is_array($arg1) && empty($arg2) )
			return false;
		$args = func_get_args();
		$like = array();
		$not = (!$not) ? '' : 'NOT ';
		if( is_array($arg1) ) {
			foreach($arg1 as $a => $b)
				$like[] = "$a $not LIKE %" . $b;
		}else{
			$like[] = "{$args[0]} {$not}LIKE '%{$args[1]}'";
		}
		$this->query .= " WHERE " . implode(' {$and} ', $like );
		return $this;
	}
/**
*
* NOT like statement, it has the same use that $this->like()
*
**/
	public function notlike( $arg1, $arg2 = false, $and = "AND" ) {
		return $this->like($arg1, $arg2, $and, true );
	}
/**
*
* NOT LIKE statement, it has the same use that $this->slike()
*
*/
	public function notslike($arg1, $arg2 = false, $and = "AND") {
		return $this->slike($arg1, $arg2, $and, true);
	}
/**
*
* LIMIT statement
*
* @param string|int $l1
* @param string|int $l2
* @return object
*
**/
	public function limit($l1, $l2 = '') {
		if( ! is_numeric($l1) )
			return false;
		$this->query .= " LIMIT {$l1}";
		if( is_numeric($l2) )
			$this->query .= ",{$l2}";
		return $this->execute();
	}
/**
*
* It returns the Query
*
**/
	public function getQuery() {
		return $this->query;
	}
/**
*
* It adds something to the current query
*
**/
	public function add( $add ) {
		$this->query .= " " . $add;
		return $this;
	}
  
/** End class! **/
}
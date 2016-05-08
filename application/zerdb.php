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
* I ask for forgiveness to anyone reading this code
* I made it when I was 14-15 years old
* And it's the greatest and usefull bullshit
* that I've done.
**/

if( ! class_exists('mysqli') )
	return trigger_error('Class MySQLi does not exist.', E_USER_ERROR);

class zerdb {
	
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
		
		$this->connect();
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
		$this->mysqli = @new mysqli(
			$this->dbhost,
			$this->dbuser,
			$this->dbpass,
			$this->dbname,
			$this->dbport
		);
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
* It cleans some vars to use them again
*
* @return bool
*
**/
	public function flush() {
		$this->query =
		$this->error =
		$this->errno =
		$this->nums  = null;
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
			return $string; // duh!

		if( $this->ready )
			return $this->mysqli->real_escape_string( $string );
		else
			return addslashes( $string );

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
	public function query( $query, $args = '' ) {
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
				); // <- protects everything!
			$rplc = array("%s", "%d", "%f", "'?'", "'%s'", "'%d'", "'%f'");
			// ^ deletes mistakes
			$query = str_replace($rplc, "?", $query);
			// ^ replaces everything that by: ? :)
			if( preg_match_all("!\?!", $query) !== count($args) )
				return false;
			foreach($args as $a) {
				$a = is_string($a) ? "'$a'" : $a;
				// if it's string it will pass it like an string eh...
				// be careful.
				$query = preg_replace("/\?/", $a, $query, 1);
				// Replaces all the ? by args in order...
				// that's why the count have to be the same
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
		if( ! $query ) {
			$this->error = $this->mysqli->error;
			$this->errno = $this->mysqli->errno;
			return false;
		}
		$this->id = $this->mysqli->insert_id; // dw if it's null...
		$this->nums = (int) $this->mysqli->affected_rows;
		if( preg_match('/^(select)/i', $this->query) ) {
			$result = new stdClass();
			$this->nums = $result->nums = $query->num_rows;
			$result->r = $query;
			if( $this->nums == 1) {
				foreach($query->fetch_assoc() as $a => $b)
					$result->$a = stripslashes($b);
				$query->data_seek(0);
			}
			return $result;
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
		$this->query = "SELECT {$data} FROM {$table}";
		if( null !== $where )
			$this->where( $where );

		return $this;
	}
/**
*
* It updates something in the database
*
* Ex: ->update('users', 'password', '123')
* Becomes: UPDATE users SET password = '123'
* Ex 2: ->update('users', array('password' => '123', 'user' => 123) )
* Becomes: UPDATE users SET password = '123', 'user' = 123
* @param string $table
* @param string|array $arg1
* @param string $arg2
* @return bool|object
*
**/
	function update( $table, $sets, $set2 = '') {
		if( ! $this->ready )
			return false;
		if( ! is_array($sets) && count( func_get_args() ) !== 3 )
			return false;
		$this->flush();
		$this->query = "UPDATE {$table}";
		$set = array();
		if( empty($set2) ) {
			foreach($sets as $column => $value) {
				$value = $this->real_escape($value);
				/**
				* @todo make this more friendly with
				* other types
				**/
				$set[] = "{$column} = '{$value}'";
			}
		} else {
			if( is_string($set2) ) {
				$set2 = $this->real_escape($set2);
			}
			$set = array("{$sets} = '{$set2}'");
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
* Insert query
*
**/
	public function insert($table, $data) {
		if( ! $this->ready )
			return false;
		if( empty($data) )
			return false;
		$this->query  = "INSERT INTO {$table} SET ";
		$end = end($data);
		reset($data);
		while( list($column, $value) = each($data) ) {
			/**
			* @todo make this friendly with other types
			**/
			$value = $this->real_escape($value);
			$this->query .= "{$column} = '$value'";
			if( $end !== $value )
				$this->query .= ', ';
		}
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
		/** here be dragons... **/
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
/** End class! **/
}
<?php
/**
 * DB Exception
 * Thrown when a db query FAILS
 * In the catch block, the db() function may be called
 * to get the error
 * or the query itself
 */
class DBException extends \Exception {}
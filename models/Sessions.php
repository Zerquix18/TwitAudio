<?php
/**
 * Class or Session manipulations
 * It does not include the variable $_SESSION
 * It's only to initialize the sessions,
 * insert them and delete them.
 * Also to check if a user is logged
 */
use \application\TACrypt;
use \application\HTTP;
use \PDO as PDO;
use Abraham\TwitterOAuth\TwitterOAuth;

class Sessions
{
	/**
	 * It stores the user ID of the session
	 * @var integer
	 */
	private static $user_id = 0;
	public  static function init()
	{
		if (is_mobile()) {
			self::verifyMobileLogin();
		} else {
			self::verifyWebLogin();
		}
	}
	public  static function verifyWebLogin()
	{
	    $cookie_name     = 'ta_session';
	    //id of the logged user:
	    $user_id         = 0;
	    $cookie_exists   = isset($_COOKIE['ta_session']);
	    $is_cookie_valid = $cookie_exists && preg_match(
	                            "/^(ta-)[\w]{29}+$/",
	                            $_COOKIE[ $cookie_name ]
	                        );
	    session_name($cookie_name);

        if (! $cookie_exists) {
            /*
             * If the cookie does not exist,
             * generate a new ID.
             */
            session_id(generate_id('session'));
        }

        if (! $cookie_exists || $is_cookie_valid) {
            /*
             * if cookie isn't valid,
             * PHP will throw a unavoidable warning
             * when calling session_start();
             */
            session_start();
        }
        if ($is_cookie_valid) {
            $session = db()->prepare(
                    "SELECT user_id FROM sessions
                     WHERE id = :id AND is_mobile = '0'
                    ");
            $session->bindValue('id', session_id());
            $session->execute();
            // fetchColumn will return FALSE if it doesn't exist >.<
            // or string with the user ID >.< 
            $user_id = (int) $session->fetchColumn();
        }
	    self::$user_id = $user_id;
	}
	public  static function verifyMobileLogin()
	{
	    $TACrypt = new TACrypt();
	    $headers = apache_request_headers();
	    if (empty($headers['Authorization'])) {
	        HTTP::exitJson(array(
	                'success'  => false,
	                'response' => 'Authorization required',
	            )
	        );
	    }
	    $authorization = $TACrypt->decrypt64($headers['Authorization']);
	    if (! $authorization) {
	        HTTP::exitJson(array(
	                'success'  => false,
	                'response' => 'Invalid authorization',
	            )
	        );
	    }
	    $query = db()->prepare(
	                "SELECT
	                    user_id
	                 FROM sessions
	                 WHERE id        = :session_id
	                 AND   is_mobile = :is_mobile"
	            );
	    $query->bindValue('session_id', $authorization);
	    $query->bindValue('is_mobile', '1');
	    $query->execute();
	    $user_id = (int) $query->fetchColumn();
	    if (! $user_id) {
	        \application\HTTP::exitJson(array(
	                'success'  => false,
	                'response' => 'Invalid authorization'
	            )
	        );
	    }
	    self::$user_id = $user_id;
	    // now the user is logged
	    // mobile requires caching:
	    session_cache_limiter('public');
	    session_cache_expire(30);
	    session_id($authorization);
	    session_start();
	}
	public static function signin($user_id)
	{
        $session_id = is_mobile() ? generate_id('session') : session_id();
        $date_added = time();
        $user_ip    = get_ip();
        $is_mobile  = is_mobile() ? '1' : '0';
        $query      = db()->prepare(
                        'INSERT INTO sessions
                         SET
                            id         = :session_id,
                            user_id    = :user_id,
                            date_added = :date_added,
                            user_ip    = :user_ip,
                            is_mobile  = :is_mobile'
                    );
        $query->bindValue('session_id', $session_id);
        $query->bindValue('user_id',    $user_id,    PDO::PARAM_INT);
        $query->bindValue('date_added', $date_added, PDO::PARAM_INT);
        $query->bindValue('user_ip',    $user_ip,    PDO::PARAM_INT);
        $query->bindValue('is_mobile',  $is_mobile);
        $query->execute();
        return $session_id;
	}
	public static function signOut()
	{
	    $query = db()->prepare('DELETE FROM sessions WHERE id = :id');
	    $query->bindValue('id', session_id());
	    $query->execute();
	    
	    session_destroy();
	    setcookie('ta_session', '', time() - 3600);
	}
	public static function isUserLogged()
	{
		return !! self::$user_id;
	}
	public static function getUserId()
	{
		return self::$user_id;
	}
}
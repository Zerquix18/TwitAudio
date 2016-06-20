<?php
/**
* Class for Twitter manipulation
* 
* @author Zerquix18
*
**/
namespace application;

use \models\Users;

class Twitter
{
    private static $prod_consumer_key    = 'jM8rVR9XiDRTuiV0qh7ULmi9b';
    private static $prod_consumer_secret = 
    'QKX2iCmKeKM6pOXFWYDdyIkqCNFjs9Jbf6T8QjcgpMopfaCUEp';

    private static $dev_consumer_key     = 'chkQ6AvFyXup8WmaMmkd3AiWQ';
    private static $dev_consumer_secret  = 
    'nGnTknnae6Eh52dASCFYivAyidYsmAZVP0nmjHMdz2Lg086coM';
    /**
     * Returns the tokens for Twitter
     * @return array An array with 2-4 keys
     */
    public static function getTokens()
    {
        $result = array();
        if ('www.twitaudio.com' === $_SERVER['HTTP_HOST']) {
            /**
             * These tokens will only be used in this domain,
             * no matter if the config says we're in production
             */
            $result[] = self::$prod_consumer_key;
            $result[] = self::$prod_consumer_secret;
        } else {
            $result[] = self::$dev_consumer_key;
            $result[] = self::$dev_consumer_secret;
        }
        // if the user is already logged, then add the access tokens
        // but not if they're trying to re-login
        $is_signin_page   =
                'signin'   === substr($_SERVER['REQUEST_URI'], 1, 6);
        $is_callback_page =
                'callback' === substr($_SERVER['REQUEST_URI'], 1, 8);

        if (is_logged() && ! $is_signin_page && ! $is_callback_page) {
            $current_user = Users::getCurrentUser();
            $result[]     = $current_user->access_token;
            $result[]     = $current_user->access_token_secret;
        }
        return $result;
    }
}
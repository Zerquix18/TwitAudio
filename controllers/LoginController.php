<?php
/**
* Login controller
* Handles the requests in the web
* For login
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace controllers;

use \application\Twitter;
use \application\HTTP;
use \models\Users;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
    
class LoginController
{

    // the place to redirect after a success or error
    private $redirect_to = '';

    public function __construct($action)
    {
        $is_production = \Config::get('is_production');
        try {

            if (! isset($_COOKIE['ta_session'])) {
                throw new \ValidationException(
                        'Cookies are needed to sign in'
                    );
            }

            $this->$action();

        } catch (\ProgrammerException $e) {
            $message  =  $e->getMessage();
        } catch (\VendorException $e) {
            $message  = 'Error with ' . $e->getVendor() . ': ' . $e->getMessage();
        } catch (\PDOException $e) {
            $message  = $e->getMessage();
        } catch (\ValidationException $e) {
            $message  = $e->getMessage();
        } catch (\Exception $e) {
            $message  = $e->getMessage();
        } finally {
            if (isset($message)) {
                if ($is_production) {
                    $_SESSION['login_error'] =
                    'There was a problem while singing you in.' .
                    ' Please, try again.';
                } else {
                    $_SESSION['login_error'] = $message;
                }
            }
            HTTP::redirect($this->redirect_to ?: url());
        }
    }

    private function signin()
    {
        if (isset($_GET['back_to']) && is_string($_GET['back_to'])) {
            // this will replace every non alphabetic character
            // into :{character}
            // so ':' will be '\:'
            // and it's protected
            $url = preg_replace('/([^\w])/', '\\\$1', url() );
            // check it gets back to the site, no outside
            if (preg_match(
                    '/^(' . $url . ')/',
                    $back_to = urldecode($_GET['back_to'])
                    )
                ) {
                $_SESSION['back_to'] = $back_to;
            }
        }
        $twitteroauth = new TwitterOAuth(...Twitter::getTokens());
        $callback     = url('callback'); // twitaudio.com/callback
        try {
            $request_token = $twitteroauth->oauth(
                                'oauth/request_token',
                                array('oauth_callback' => $callback)
                            );
            $login_url     = $twitteroauth->url(
                                'oauth/authorize',
                                array('oauth_token' => $request_token['oauth_token'])
                            );
        } catch (TwitterOAuthException $e) {
            throw new \VendorException('TwitterOAuth', $e->getMessage());
        }

         if (isset($error)) {
            echo 'he';
            throw new \VendorException(...$error);
         }

        $_SESSION['oauth_token']        = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

        $this->redirect_to = $login_url;
    }
    /**
    * Back from Twitter
    *
    **/
    private function callback()
    {
        if (isset($_SESSION['back_to'])) { 
            $this->redirect_to = $_SESSION['back_to'];
            unset($_SESSION['back_to']);
        } else {
            $this->redirect_to = url();
        }

        if (! isset(
                    $_SESSION['oauth_token'],
                    $_SESSION['oauth_token_secret']
                )
            ) {
            throw new \ValidationException('No tokens were stored');
        }

        $denied = HTTP::get('denied');

        if ($denied && $denied == $_SESSION['oauth_token']) {
            throw new \ValidationException('Request was denied');
        }

        $oauth_token    = HTTP::get('oauth_token');
        $oauth_verifier = HTTP::get('oauth_verifier');

        if (   ! ($oauth_token && $oauth_verifier)
            || $_SESSION['oauth_token'] !== $oauth_token
        ) {
            throw new \ValidationException('Oauth tokens does not match');
        }

        $tokens       = Twitter::getTokens();
        $tokens[]     = $_SESSION['oauth_token'];
        $tokens[]     = $_SESSION['oauth_token_secret'];
        $twitteroauth = new TwitterOAuth(...$tokens);

        unset($_SESSION['oauth_token']);
        unset($_SESSION['oauth_token_secret']);

        try {
            $tokens = $twitteroauth->oauth(
                        "oauth/access_token",
                        array("oauth_verifier" => $oauth_verifier)
                    );
        } catch (TwitterOAuthException $e) {
            throw new \VendorException('TwitterOAuth', $e->getMessage());
        }

        $create_user = Users::insert(array(
                'access_token'        => $tokens['oauth_token'],
                'access_token_secret' => $tokens['oauth_token_secret']
            )
        );

        if ($create_user['first_time']) {
            $_SESSION['first_time'] = true;
        }
    }
}
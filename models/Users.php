<?php
/**
* User Model
* Manages all the user data
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace models;

use \application\interfaces\ModelInterface;
use \application\Twitter;
use \models\CurrentUser;
use PDO;
use Abraham\TwitterOAuth\TwitterOAuth;

class Users implements ModelInterface
{
    /**
     * Returns an pobject with all the data
     * of the current user.
     *
     * @param  array $user_content The data that the array will return
     * @return object
    *
    **/
    public static function getCurrentUser(array $user_context = [])
    {
        // if $user_context was passed, it's because it's completed
        // so it won't call complete_user again
        // cuz complete_user calls this function
        // and it causes a loop

        if (! empty($user_context)) {
            return new CurrentUser($user_context);
        }

        $user = is_logged() ?
            self::complete( (array) $GLOBALS['_USER'] )
        :
            array();

        return new CurrentUser( $user );
    }
    /**
     * Fills the array $user
     * With more info about the user,
     * forces the types and deletes
     * useless stuff
     *
     * @param  array $user - They array with the data to work with
     * @return array
    **/
    public static function complete(array $user)
    {

        /**
         * Check if $key is un $user
         * @var Clousure
         * @return bool
         */
        $has = function ($key) use ($user) {
            return array_key_exists($key, $user);
        };
        /** complete **/

        if ($has('avatar')) {
            $user['avatar_bigger'] = get_avatar($user['avatar'], 'bigger');
            $user['avatar_big']    = get_avatar($user['avatar']);
        }

        if ($has('id')) {
            $user['id']         = (int) $user['id'];
            $current_user       = self::getCurrentUser($user);
            $user['can_listen'] = $current_user->canListen($user['id']);
        }

        /** force types **/

        if ($has('is_verified')) {
            $user['is_verified']   = !! $user['is_verified'];
        }

        if ($has('date_added')) {
            $user['date_added']    = (int) $user['date_added'];
        }

        if (! is_mobile()) {
            $user = self::completeForWeb($user);
        }

        return $user;
    }
    /**
     * Completes the array $user, adding bars for the web
     * @param  array  $user The array to complete
     * @return array        Array with the bars
     */
    private static function completeForWeb(array $user)
    {
        $has = function ($key) use ($user) {
            return array_key_exists($key, $user);
        };

        if ($has('username')) {
            $user['profile_url']    = url('audios/' .    $user['username']);
            $user['favorites_url']  = url('favorites/' . $user['username']);
        }

        return $user;
    }
    /**
     * Gets the info about the given user
     * From the database.
     *
     * @param string $id_or_user    The ID or USER to extract the info
     * @param array  $which_columns The columns of the database
     * @return array
    **/
    public static function get($id_or_user, array $which_columns = [])
    {
        if (! $which_columns) {
            // default
            $which_columns = array(
                    'id',
                    'username',
                    'name',
                    'avatar',
                    'bio',
                    'is_verified',
                    'favs_privacy',
                    'audios_privacy'
            );
        }

        $id_or_user   = (string) $id_or_user;
        $column       = ctype_digit($id_or_user) ? 'id' : 'username';
        $columns      = implode(',', $which_columns);
        $query        = db()->prepare(
                            "SELECT
                                {$columns}
                            FROM users
                            WHERE {$column} = :id_or_user"
                        );
        $query->bindValue('id_or_user', $id_or_user);
        $query->execute();
        $user = $query->fetch(PDO::FETCH_ASSOC);
        if (! $user) {
            return array();
        }

        return self::complete( (array) $user );
    }
    /**
     * Registers an user in the database if it does not
     * exist. If it does exist, then it re-updates its info.
     * @param  array $options An array with the access tokens
     * @throws ProgrammerException
     * @return array The user data
    **/
    public static function insert( array $options = [])
    {
        $required_options = array('access_token', 'access_token_secret');
        if (0 !== count(
                    array_diff($required_options, array_keys($options))
                )
            ) {
            // ups
            throw new \ProgrammerException('Missing required options');
        }
        $tokens       = Twitter::getTokens();
        $tokens[]     = $options['access_token'];
        $tokens[]     = $options['access_token_secret'];
        $twitteroauth = new TwitterOAuth(...$tokens);
        $details = $twitteroauth->get('account/verify_credentials');

        if (! is_object($details) || ! property_exists($details, 'id')) {
            throw new \VendorException(
                'Twitter did not return anything: ' . print_r($details, true)
            );
        }

        $id                  = $details->id;
        $username            = $details->screen_name;
        $name                = $details->name;
        $bio                 = $details->description;
        $avatar              = $details->profile_image_url_https;
        $is_verified         = (string) (int) $details->verified;
        $access_token        = $options['access_token'];
        $access_token_secret = $options['access_token_secret'];

        $query = db()->prepare(
                    'SELECT COUNT(*) FROM users
                     WHERE id = :id'
                );
        $query->bindValue('id', $id, PDO::PARAM_INT);
        $query->execute();
        // if it's found, then it's not the first time
        // if it's not found, then it's the first time
        $first_time = ! $query->fetchColumn();

        if (! $first_time) {
            // re-update
            $query = db()->prepare(
                        'UPDATE users
                         SET
                            username            = :username,
                            name                = :name,
                            avatar              = :avatar,
                            bio                 = :bio,
                            is_verified         = :is_verified,
                            access_token        = :access_token,
                            access_token_secret = :access_token_secret
                         WHERE id = :id'
                    );
        } else {
            // insert
            $query  = db()->prepare(
                        'INSERT INTO users
                         SET
                            id                  = :id,
                            username            = :username,
                            name                = :name,
                            avatar              = :avatar,
                            bio                 = :bio,
                            is_verified         = :is_verified,
                            access_token        = :access_token,
                            access_token_secret = :access_token_secret,
                            favs_privacy        = :favs_privacy,
                            audios_privacy      = :audios_privacy,
                            date_added          = :date_added,
                            register_ip         = :register_ip'
                    );
            // params that will only be binded now
            $favs_privacy   = //↓
            $audios_privacy = $details->protected ? 'private' : 'public';
            $date_added     = time();
            $register_ip    = get_ip();

            $query->bindValue('audios_privacy', $audios_privacy);
            $query->bindValue('favs_privacy',   $favs_privacy);
            $query->bindValue('date_added',     $date_added,  PDO::PARAM_INT);
            $query->bindValue('register_ip',    $register_ip, PDO::PARAM_INT);
        }
        // params that will always be binded
        $query->bindValue('id',                  $id, PDO::PARAM_INT);
        $query->bindValue('username',            $username);
        $query->bindValue('name',                $name);
        $query->bindValue('avatar',              $avatar);
        $query->bindValue('bio',                 $bio);
        $query->bindValue('is_verified',         $is_verified);
        $query->bindValue('access_token',        $access_token);
        $query->bindValue('access_token_secret', $access_token_secret);  
        $query->execute();

        ///////////// this is a well comented line
        $session_id = \Sessions::signIn($id);

        return array(
                'id'          =>    $id,
                'username'    =>    $user,
                'name'        =>    $name,
                'avatar'      =>    $avatar,
                'is_verified' => !! $is_verified,
                'sess_id'     =>    $session_id,
                'first_time'  =>    $first_time
            );
    }
    /**
    * @todo
    **/
    public static function ban(array $user)
    {

    }
    /**
    * @todo
    **/
    public static function delete($id)
    {
        
    }
}
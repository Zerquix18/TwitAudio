<?php
/**
* This class has the functions and
* properties for the logged users.
* It should be called from the User model
**/
namespace models;

use PDO;

class CurrentUser
{

    /**
     * @param $user_info comes from the User model
     * @see \models\Users::get()
     *
    **/
    public function __construct(array $user_info = [])
    {
        foreach ($user_info as $key => $value) {
            $this->$key = $value;
        }
    }
    /**
     * Checks if the logged user
     * can listen to the audios of $id
     * (If $this->id is following $id)
     * @return bool
    **/

    public function canListen($id)
    {
        // if user is not logged, then $this will not have
        // the property 'id', which is added in the constructor

        $is_logged = property_exists($this, 'id');
        
        if ($is_logged && $this->id === $id) {
            // same user.
            return true;
        }

        $user = Users::get($id, array('audios_privacy'));

        if ('public' == $user['audios_privacy']) {
            return true;
        }

        if (! $is_logged) {
            return false; // not logged and audios aren't public.
        }

        // not public. check if cached ...
        // let's clean
        $query = db()->prepare(
                    "DELETE FROM following_cache
                    WHERE date_added < :date_added"
                );
        $query->bindValue('date_added', time() - 1800, PDO::PARAM_INT);
        $query->execute(); //              ^ (60*30) / half an hour

        $query = db()->prepare(
                    'SELECT result FROM following_cache
                     WHERE user_id = :user_id AND following = :following'
                );
        $query->bindValue('user_id',   $this->id);
        $query->bindValue('following', $id);
        $query->execute();
        $following_cache = $query->fetch(PDO::FETCH_ASSOC);

        if ($following_cache) {
            //there's something cached
            //the result will be 1 or 0
            //return it as bool c:
            return !! $following_cache['result'];
        }

        // not cached, make twitter requests
        // to see if $this->id is following $id
        $twitter = new \application\Twitter(
                $this->access_token,
                $this->access_token_secret
            );
        $friendship = $twitter->tw->get(
            'friendships/lookup',
            array('user_id' => $id)
        );
        if (! array_key_exists('errors', $friendship)) {
            // no errors
             $is_following = in_array(
                                'following',
                                $friendship[0]->connections
                            );
        } else {
            // API rate limit reached :( try another
            $profile = $twitter->tw->get(
                        'users/lookup',
                        array('user_id' => $id)
                    );
            if (   array_key_exists('error',  $profile)
                || array_key_exists('errors', $profile)
               ) {
                // both limits reached...! ):
                return false;
            }
            // success!
            $is_following =    array_key_exists('following', $profile[0])
                            && $profile[0]->following;
        }

        // store the result for half an hour
        $query = db()->prepare(
                    'INSERT INTO following_cache
                     SET
                        user_id    = :user_id,
                        following  = :following,
                        date_added = :date_added,
                        result     = :result'
                );
        $query->bindValue('user_id',    $this->id, PDO::PARAM_INT);
        $query->bindValue('following',  $id,       PDO::PARAM_INT);
        $query->bindValue('date_added', time(),    PDO::PARAM_INT);
        $query->bindValue('result', $is_following ? '1' : '0');
        $query->execute();

        return $is_following;
    }
    /**
     * Get a limit of the current user
     * Current limits are:
     * 'file_upload'    (will return it in mbs)
     * 'audio_duration' (will return it in seconds)
     *  @param  string $limit
    **/
    public function getLimit($limit)
    {
        if (! property_exists($this, 'id')) {
            return 0;
        }
        $duration = (int) $this->upload_limit;
        switch ($limit) {
            case 'file_upload':
                $duration = (string) ($duration / 60);
                return (int) $duration . '0';
                /**
                * example: duration = 120 then
                * 120/60 = 2
                * return 20(mb)
                * 50 for 5 minutes, 100 for 10 minutes
                * una hermosa simetría <3
                **/
                break;
            case "audio_duration":
                return $duration;
                break;
        }
    }
    /**
     * Returns the list of available effects for the logged user
     * 
     * @return array
    **/
    public function getAvailableEffects()
    {
        $all_effects = array(
                /** effects for all the users **/
                'echo',
                'faster',
                'reverse',
                /** effects for paid users */
                'slow',
                'reverse_quick',
                'hilbert',
                'flanger',
                'delay',
                'deep',
                'low',
                'fade',
                'tremolo'
            );

        if (! $this->isPremium()) {
            return array_splice($all_effects, 0, 3);
        }
        return $all_effects;
    }
    /**
     * Checks if user is premium
     * @return bool
    **/
    public function isPremium()
    {
        if (! property_exists($this, 'id')) {
            // not logged
            return false;
        }
        $duration      = (int) $this->upload_limit;
        $premium_until = (int) $this->premium_until;
        return ($duration > 120) && (time() < $premium_until);
    }
    /**
     * Updates the settings of the logged user
     * @param  array  $settings The settings, must be keys of the database
     * @return bool
     */
    public function updateSettings(array $settings) {
        $column_value = '';
        $params       = array();
        $last         = end($settings);
        reset($settings);
        foreach ($settings as $option => $value) {
            // this way the params are protected
            $column_value .= "{$option} = ?,";
            $params[]      = $value;
        }
        /**
        * Delete the last comma because there was no way
        * to check for the last value inside the loop
        **/
        $column_value = substr($column_value, 0, -1);
        //add the id
        $params[] = $this->id;
        //
        $query    = db()->prepare(
                        "UPDATE users
                         SET
                            {$column_value}
                         WHERE id = ?
                        "
                    );
        $result   = $query->execute($params);

        return $result;
    }
}
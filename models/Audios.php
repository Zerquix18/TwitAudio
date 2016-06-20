<?php
/**
* Audio Model
* Manages all the audios/replies data
*
* @author Zerquix18 <zerquix18@outlook.com>
*
**/
namespace models;

use \application\interfaces\ModelInterface;
use \application\Twitter;
use \application\HTTP;
use \models\Users;
use PDO;
use Abraham\TwitterOAuth\TwitterOAuth;

class Audios implements ModelInterface
{
    /**
     * The default columns to select from audios
     */
    public static $columns = array(
                                'id',
                                'user_id',
                                'audio_url',
                                'reply_to',
                                'description',
                                'date_added',
                                'plays',
                                'favorites',
                                'duration'
                            );
    public static $per_page = 10;
    /**
     * Loads the user info, how many favorites
     * and how many replies, etc. the audio has.
     * And adds it to the array.
     *
     * @param  array $audio The array to be completed
     * @return array        The audio completed
     *
    **/
    public static function complete(array $audio) {
        $has = function ($key) use ($audio) {
            return array_key_exists($key, $audio);
        };

        if ($has('user_id')) {
            $audio['user'] = Users::get($audio['user_id']);
            unset($audio['user_id']);
        }

        if ($has('id')) {

            if (is_logged()) {
                $current_user = Users::getCurrentUser();
                $query = db()->prepare(
                    'SELECT
                        COUNT(*)
                     FROM favorites
                     WHERE audio_id = :audio_id
                     AND   user_id  = :user_id'
                );
                $query->bindValue('audio_id', $audio['id']);
                $query->bindValue('user_id', $current_user->id);
                $query->execute();
                $audio['is_favorited'] = !! $query->fetchColumn();
            } else {
                $audio['is_favorited'] = false;
            }

            $audio['replies_count'] = self::getRepliesCount($audio['id']);

        }
        if ($has('favorites')) {
            $audio['favorites']  = (int) $audio['favorites'];
        }
        if ($has('plays')) {
            $audio['plays']      = (int) $audio['plays'];
        }
        if ($has('date_added')) {
            $audio['date_added'] = (int) $audio['date_added'];
        }
        if ($has('duration')) {
            $audio['duration']   = (int) $audio['duration'];
        }
        
        if (! empty($audio['audio_url'])) {
            $audio['original_name'] = $audio['audio_url'];
            $audio['audio']         = 
            url('assets/audios/' . $audio['audio_url']);
        }

        /** remove **/

        if ($has('r')) {
            // mysqli result
            unset($audio['r']);
        }
        if ($has('nums')) {
            // num rows
            unset($audio['nums']);
        }
        // if we are in web we need extra stuff for the template
        if (! is_mobile()) {
            $audio = self::completeForWeb($audio);
        }

        return $audio;
    }
    /**
     * Add bars for handlebars.
     * They're only needed in the web side.
     *
     * @param array $audio The array to complete
     */
    public static function completeForWeb($audio)
    {
        $has = function ($key) use ($audio) {
            return array_key_exists($key, $audio);
        };

        $current_user = Users::getCurrentUser();

        if ($has('user')) {
            $audio['user']['can_favorite'] = is_logged();
            // is_logged() returns the user ID
            $is_logged = is_logged();
            $audio['user']['can_delete']   =
            $is_logged && $audio['user']['id'] == $current_user->id;
        }
        if ($has('plays')) {
            $audio['plays_count'] = format_number($audio['plays']);
            if ($audio['plays'] == 1) {
                $plays_count_text = '%d person played this';
            } else {
                $plays_count_text = '%d people played this';
            }
            $audio['plays_count_text'] = //↓
            sprintf($plays_count_text, $audio['plays']);
        }
        if ($has('replies_count')) {
            $audio['replies_count'] = format_number($audio['replies_count']);
        }
        if ($has('favorites')) {
            $audio['favorites_count'] = //↓
            format_number($audio['favorites']);
        }
        if ($has('description')) {
            // add links, @mentions and avoid XSS
            $audio['description'] = HTTP::sanitize($audio['description']);
        }

        if ($has('id')) {
            if ($has('reply_to') && $audio['reply_to']) {
                /*
                    If it's a reply, then add a link to the original audio
                    But with this reply appearing first
                 */
                $audio['audio_url'] =
                url() . $audio['reply_to'] .'?reply_id=' . $audio['id'];
            } else {
                $audio['audio_url'] = url() . $audio['id'];
            }
        }

        if (   $has('id')
            && $has('audio')
            && $has('reply_to')
            && ! $audio['reply_to']
           ) {
            $audio['player'] = array(
                'id'        => $audio['id'],
                'audio'     => $audio['audio'],
                'autoload'  => true
            );
        }

        return $audio;
    }
    /**
     * Returns an array with the info of the audio $id
     *
     * @param  string $id       The ID of the audio to load
     * @param  array $whichinfo The database columns
     * @return array
    *
    **/
    public static function get($id, array $which_columns = [])
    {
        if (! preg_match("/^[A-Za-z0-9]{6}$/", $id)) {
            return array();
        }

        if (array() === $which_columns) {
            $which_columns = self::$columns;
        }

        $columns = implode(',', $which_columns);

        $query   = db()->prepare(
                "SELECT
                    {$columns}
                 FROM audios
                 WHERE status = '1'
                 AND   id     = :id"
            );
        $query->bindValue('id', $id);
        $query->execute();
        $audio = $query->fetch();
        if (! $audio) {
            return array();
        }

        return self::complete((array) $audio);
    }
    /**
     * Returns an array with the last 3 recent audios
     * of the logged user
     *
     * @return array
     *
    **/
    public static function getRecentAudios()
    {
        $current_user = Users::getCurrentUser();
        $columns      = implode(',', self::$columns);
        $query        = db()->prepare(
                            "SELECT
                                {$columns}
                             FROM audios
                             WHERE reply_to IS NULL
                             AND   status  = '1'
                             AND   user_id = :user_id
                             ORDER BY date_added DESC
                             LIMIT 3"
                        );
        $query->bindValue('user_id', $current_user->id, PDO::PARAM_INT);
        $query->execute();
        $result       = array();
        foreach ($query->fetchAll() as $array) {
            $result[] = self::complete($array);
        }
        return $result;
    }
    /**
     * Returns an array with the
     * 3 most listened audios of
     * the last 30 days
     * @return array
    **/
    public static function getPopularAudios()
    {
        $columns = self::$columns;
        /**
         * Here we do a JOIN, so before putting the columns,
         * place the 'A.' (for the Audios table) before every
         * column.
         * @var array
         */
        $columns = array_map(
                function ($value) {
                    return 'A.' . $value;
                },
                $columns
            );
        $columns = implode(',', $columns);
        $query   = db()->prepare(
                    "SELECT DISTINCT
                        {$columns}
                     FROM audios AS A
                     INNER JOIN users AS U
                     ON  A.user_id        = U.id
                     AND U.audios_privacy = 'public'
                     AND A.reply_to IS NULL
                     AND A.status         = '1'
                     AND A.date_added BETWEEN :oldtime AND :newtime
                     ORDER BY A.plays DESC
                     LIMIT 3"
                );
        $query->bindValue(
                        'oldtime',
                        time() - strtotime('-30 days'),
                        PDO::PARAM_INT
                    );
        $query->bindValue('newtime', time(), PDO::PARAM_INT);
        $query->execute();
        $result       = array();
        while ($audio = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = self::complete($audio);
        }
        return $result;
    }
    /**
     * Get the count of audios of the user $id
     *
     * @param  string $id
     * @return integer
    **/
    public static function getAudiosCount($user_id)
    {
        $query = db()->prepare(
                "SELECT
                    COUNT(*)
                 FROM audios
                 WHERE reply_to IS NULL
                 AND status  = '1'
                 AND user_id = :user_id"
            );
        $query->bindValue('user_id', $user_id);
        $query->execute();
        $count = (int) $query->fetchColumn();
        return $count;
    }
    /**
     * Returns an array with the last 10 audios of $user_id
     *
     * @param string  $user_id The ID of the user
     * @param integer $page    The page number
     * @return array
    *
    **/
    public static function getAudios($user_id, $page = 1)
    {
        $count  = self::getAudiosCount($user_id);
        // default result
        $result = array(
                    'audios'     => array(),
                    'load_more'  => false,
                    'page'       => $page,
                    'total'      => $count
                );

        if (0 === $count) {
            return $result;
        }

        $total_pages = ceil($count / self::$per_page);

        if ($page > $total_pages) {
            return $result;
        }

        $columns = self::$columns;
        $columns = implode(',', $columns);
        $query   = db()->prepare(
                        "SELECT
                            {$columns}
                        FROM audios
                        WHERE reply_to IS NULL
                        AND   status  = '1'
                        AND   user_id = :user_id
                        ORDER BY date_added DESC
                        LIMIT :skip, :max"
                    );
        $query->bindValue('user_id', $user_id);
        $query->bindValue(
                            'skip',
                            ($page - 1) * self::$per_page,
                            PDO::PARAM_INT
                        );
        $query->bindValue('max', self::$per_page, PDO::PARAM_INT);
        $query->execute();

        while ($audio = $query->fetch(PDO::FETCH_ASSOC)) {
            $result['audios'][] = self::complete($audio);
        }

        $result['load_more'] = $page < $total_pages;
        $result['page']      = $page + 1;
        $result['total']     = $count;
        return $result;
    }
    /**
     * Get the count of replies of the audio $id
     *
     * @param  string $id
     * @return integer
    **/
    public static function getRepliesCount($audio_id)
    {
        $query = db()->prepare(
                    "SELECT
                        COUNT(*)
                     FROM audios
                     WHERE status   = '1'
                     AND   reply_to = :reply_to"
                );
        $query->bindValue('reply_to', $audio_id);
        $query->execute();
        $count = (int) $query->fetchColumn();
        return $count;
    }
    /**
     * Returns an array with the last 10 replies of $audio_id
     *
     * @param string  $audio_id The ID of the audio
     * @param integer $page     The page number
     * @return array
     *
    **/
    public static function getReplies($audio_id, $page = 1)
    {
        $count  = self::getRepliesCount($audio_id);
        // default result
        $result = array(
                    'audios'     => array(),
                    'load_more'  => false,
                    'page'       => $page,
                    'total'      => $count
                );

        if (0 === $count) {
            return $result;
        }

        $total_pages = ceil($count / self::$per_page);

        if ($page > $total_pages) {
            return $result;
        }

        $columns = self::$columns;
        $columns = implode(',', $columns);
        $query   = db()->prepare(
                        "SELECT
                            {$columns}
                        FROM audios
                        WHERE reply_to = :reply_to
                        AND   status   = '1'
                        ORDER BY date_added DESC
                        LIMIT :skip, :max"
                    );
        $query->bindValue('reply_to', $audio_id);
        $query->bindValue(
                            'skip',
                            ($page - 1) * self::$per_page,
                            PDO::PARAM_INT
                        );
        $query->bindValue('max', self::$per_page, PDO::PARAM_INT);
        $query->execute();

        while ($audio = $query->fetch(PDO::FETCH_ASSOC)) {
            $result['audios'][] = self::complete($audio);
        }

        $result['load_more'] = ($page < $total_pages);
        $result['page']      = $page + 1;
        $result['total']     = $count;
        return $result;
    }
    /**
     * Get the count of favorites of the $audio_id
     * @param  string $user_id
     * @return integer
    **/
    public static function getFavoritesCount($user_id)
    {
        $query = db()->prepare(
                    "SELECT
                        COUNT(*)
                     FROM audios AS A
                     INNER JOIN favorites AS F
                     ON A.id       = F.audio_id
                     AND F.user_id = :user_id
                     AND A.status  = '1'"
                );
        $query->bindValue('user_id', $user_id);
        $query->execute();
        $count = (int) $query->fetchColumn();
        return $count;
    }
    /**
     * Returns an array with the last 10 favorites of $user_id
     *
     * @param $user_id - The ID of the user
     * @param $page    - The page number
     * @return array
     *
    **/
    public static function getFavorites($user_id, $page = 1)
    {
        $count  = self::getFavoritesCount($user_id);
        $result = array(
                    'audios'     => array(),
                    'load_more'  => false,
                    'page'       => $page,
                    'total'      => $count
                );

        if (0 == $count) {
            return $result;
        }

        $total_pages = ceil($count / 10);

        if ($page > $total_pages) {
            return $result;
        }

        $columns = self::$columns;
        $columns = array_map(
                function ($value) {
                    return 'A.' . $value;
                },
                $columns
            );
        $columns = implode(',', $columns);
        $query   = db()->prepare(
                        "SELECT DISTINCT
                            {$columns}
                         FROM audios AS A
                         INNER JOIN favorites AS F
                         ON  A.id      = F.audio_id
                         AND F.user_id = :user_id
                         AND A.status  = '1'
                         ORDER BY F.date_added DESC
                         LIMIT :skip, :max"
                    );
        $query->bindValue('user_id', $user_id);
        $query->bindValue(
                            'skip',
                            ($page - 1) * self::$per_page,
                            PDO::PARAM_INT
                        );
        $query->bindValue('max', self::$per_page, PDO::PARAM_INT);
        $query->execute();

        while ($audio = $query->fetch(PDO::FETCH_ASSOC)) {
            $result['audios'][] = self::complete($audio);
        }
    
        $result['load_more'] = $page < $total_pages;
        $result['page']      = $page + 1;
        $result['total']     = $count;
        return $result;
    }
    /**
     * Inserts an audio|reply in the database
     * @param  array  $options The keys to insert
     * @return array           An array with everything inserted so it can
     *                         be displayed in screen instantly.
     * @throws ProgrammerException
     */
    public static function insert(array $options)
    {
        if (empty($options['audio_url']) && empty($options['reply_to'])) {
            // come on! need one of both
            throw new \ProgrammerException(
                    'Missing option audios_url || reply'
                );
        }
        $is_reply = ! empty($options['reply_to']) && empty($options['audio']);
        if (! $is_reply) {
            // required keys for audios
            $required_options = array(
                    'description', 'duration',
                    'is_voice', 'send_to_twitter',
                    'audio_url',
            );
        } else {
            $required_options = array(
                    'reply', 'send_to_twitter',
                    'reply_to', 'twitter_id', 'user_id'
                );
        }
        if (0 !== count(
                    array_diff($required_options, array_keys($options))
                    )
           ) {
            // ups
            throw new \ProgrammerException('Missing required options');
        }
        $audio_id     = generate_id('audio');
        $current_user = Users::getCurrentUser();
        $query        = db()->prepare(
                        'INSERT INTO audios
                         SET
                            id          = :id,
                            user_id     = :user_id,
                            audio_url   = :audio_url,
                            reply_to    = :reply_to,
                            description = :description,
                            date_added  = :date_added,
                            duration    = :duration,
                            is_voice    = :is_voice'
                        );
        $query->bindValue('id', $audio_id);
        $query->bindValue('user_id', $current_user->id, PDO::PARAM_INT);
        $query->bindValue('date_added', time(), PDO::PARAM_INT);

        if (! $is_reply) {
            // it's an audio, so, set the audio URL:
            $query->bindValue('audio_url', $options['audio_url']);
            // reply_to must be NULL cause it's not a reply:
            $query->bindValue('reply_to', null, PDO::PARAM_NULL);
            // it's an audio, insert the description:
            $query->bindValue('description', $options['description']);
            // it's an audio, so it has a duration
            $query->bindValue(
                            'duration',
                            $options['duration'],
                            PDO::PARAM_INT
                        );
            // it's an audio, so it can be recorded
            $query->bindValue('is_voice', $options['is_voice']);
        } else {
            // it's a reply, so there's no player:
            $query->bindValue('audio_url', null, PDO::PARAM_NULL);
            // and it's replying to other audio:
            $query->bindValue('reply_to', $options['reply_to']);
            // it's a reply, use the reply key:
            $query->bindValue('description', $options['reply']);
            // it's a reply, it does not have a duration
            $query->bindValue('duration', 0, PDO::PARAM_INT);
            // it's a reply, it does not have player
            $query->bindValue('is_voice', '0');
        }
        $query->execute();

        $audio = self::get($audio_id);
        // now proceed to tweet
        if (! $options['send_to_twitter']) {
            // we got nothing else to do
            return $audio;
        }
        if (! $is_reply) {
            /**
            * Make the tweet for audios.
            * I'll explain this nightmare.
            **/
            // here's the link, forget about www here
            $link         = 'https://twitaudio.com/'. $audio_id;
            $link_length  = strlen($link);
            $description  = $options['description'];
            if (mb_strlen($description, 'utf-8') > (140-$link_length)) {
                // if the description is longer than (140 - the link length)
                // then make it shorter
                $description  = substr(
                        $description,
                        0,
                        140-$link_length-4 // 4= the 3 periods below + space
                    );
                $description .= '...';
            }
            // we're done :)
            $tweet = $description . ' ' . $link;
        } else {
            /**
            * Make the tweet for replies
            * This nightmare is bigger than
            * the one above.
            **/
            // we got the final part
            $link        = ' - https://twitaudio.com/'. $audio_id;
            $link_length = strlen($link);

            $query       = db()->prepare(
                            'SELECT username FROM users WHERE id = :id'
                        );
            $query->bindValue('id', $options['user_id']);
            $query->execute();

            $username    = $query->fetchColumn();

            $reply = sprintf('@%s %s', $username, $options['reply']);

            if (mb_strlen($reply, 'utf-8') > (140-$link_length)) {
                $reply  = substr($reply, 0, 140-$link_length-3);
                $reply .= '...';
            }
            $tweet = $reply . $link;
        }
        $twitteroauth = new TwitterOAuth(...Twitter::getTokens());
        $result       = $twitteroauth->post(
                            'statuses/update',
                            array(
                                'status'                => $tweet,
                                'in_reply_to_status_id' =>
                                $is_reply ? $options['twitter_id'] : 0
                            )
                        );
        if (array_key_exists('id', $result)) {
            $tweet_id = $result->id;
        } else {
            $tweet_id = 0;
        }

        if ($tweet_id) {
            // now re-update the ID in the db
            $query = db()->prepare(
                        'UPDATE audios
                         SET
                            twitter_id = :tweet_id
                         WHERE
                            id         = :audio_id
                        '
                    );
            // tweet id inserted as string instead of integer
            // because PHP can't handle a big integer like this
            // 744638714954002432
            // :(
            $query->bindValue('tweet_id', $tweet_id);
            $query->bindValue('audio_id', $audio_id, PDO::PARAM_INT);
            $query->execute();
        }
        return $audio;
    }

    /**
     * Will delete an audio or a reply
     *
     * This function is BLIND
     * It will delete the audio without any
     * comprobation.
    **/
    public static function delete($id)
    {
        $audio        = self::get($id, array('audio_url'));
        if (! $audio) {
            // does not exist.
            return;
        }
        $query = db()->prepare(
                        "DELETE FROM audios WHERE id = :id"
                    );
        $query->bindValue('id', $id);
        $query->execute();
        /*
            I tried making only one query but it generated a lot of
            warnings and did not delete the audio :(
         */
        $query = db()->prepare("DELETE FROM audios WHERE reply_to = :id");
        $query->bindValue('id', $id);
        $query->execute();

        if ($audio['audio_url']) {
            @unlink(
                DOCUMENT_ROOT .
                '/assets/audios/' . $audio['original_name']
            );
        }
    }
    /**
     * Favorites an audio
     * @param string $audio_id
    **/
    public static function registerFavorite($audio_id)
    {
        $query = db()->prepare(
                    'UPDATE audios
                     SET favorites = favorites + 1
                     WHERE id = :id'
                );
        $query->bindValue('id', $audio_id);
        $query->execute();

        $current_user = Users::getCurrentUser();

        $query = db()->prepare(
                'INSERT INTO favorites
                 SET
                    user_id    = :user_id,
                    audio_id   = :audio_id,
                    date_added = :date_added'
                );
        $query->bindValue('user_id', $current_user->id, PDO::PARAM_INT);
        $query->bindValue('audio_id', $audio_id);
        $query->bindValue('date_added', time(), PDO::PARAM_INT);
        $query->execute();
    }
    /**
     * Unfavorites an audio
     * @param  string $audio_id
    **/
    public static function unregisterFavorite($audio_id)
    {
        $query = db()->prepare(
                    'UPDATE audios
                     SET   favorites = favorites - 1
                     WHERE id        = :id'
                );
        $query->bindValue('id', $audio_id);
        $query->execute();

        $current_user = Users::getCurrentUser();

        $query = db()->prepare(
                    'DELETE FROM favorites
                     WHERE audio_id = :audio_id
                     AND    user_id = :user_id'
                );
        $query->bindValue('audio_id', $audio_id);
        $query->bindValue('user_id', $current_user->id, PDO::PARAM_INT);
        $query->execute();
    }
    /**
    * Registers a play for $audio_id
    *
    * @param $audio_id str
    * @return bool
    **/
    public static function registerPlay($audio_id)
    {
        $user_ip = get_ip(); // ← /application/functions.php
        $query   = db()->prepare(
                        'SELECT
                            COUNT(*)
                         FROM plays
                         WHERE user_ip  = :user_ip
                         AND   audio_id = :audio_id'
                    );
        $query->bindValue('user_ip', $user_ip, PDO::PARAM_INT);
        $query->bindValue('audio_id', $audio_id);
        $query->execute();

        $was_played = !! $query->fetchColumn();
        if ($was_played) {
            return false;
        }

        $query = db()->prepare(
                        'UPDATE audios
                        SET   plays = plays + 1
                        WHERE id    = :id'
                );
        $query->bindValue('id', $audio_id);
        $query->execute();

        $query = db()->prepare(
                    'INSERT INTO plays
                     SET
                        user_ip    = :user_ip,
                        audio_id   = :audio_id,
                        date_added = :date_added'
                );
        $query->bindValue('user_ip',    $user_ip, PDO::PARAM_INT);
        $query->bindValue('audio_id',   $audio_id);
        $query->bindValue('date_added', time(),   PDO::PARAM_INT);
        $query->execute();
        return true;
    }
}
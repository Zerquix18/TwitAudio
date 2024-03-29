<?php
/**
* Functions file
*
**/
/** init functions **/
function db_init()
{
    global $db;
    $db = new \PDO(
        sprintf(
            'mysql:host=%s;dbname=%s',
            \Config::get('host'),
            \Config::get('database')
        ),
        \Config::get('user'),
        \Config::get('password')
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}
function is_logged()
{
    return \Sessions::isUserLogged();
}
/**
 * Formats a number, making it smaller and easy to read.
 * @param  string|int $count
 * @return string
**/
function format_number($count)
{
    $count = (int) $count; // just in case
    if ($count >= 1000 &&  $count < 1000000) {
        return number_format($count / 1000, 1) . 'k';
    } elseif ($count >= 1000000) {
        return number_format($count / 1000000, 1) . "m";
    }

    return (string) $count;
}
/**
 * Generates an non-existent ID in the database
 * @param  string $for Must be audio|session
 * @return string the ID :)
**/
function generate_id($for)
{
    $chars = 
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890';
    if (! in_array($for, array('session', 'audio'))) {
        throw new \ProgrammerException(
            "generate_id only accepts 'session' and 'audio'"
        );
    }
    $table  = 'session' === $for ? 'sessions' : 'audios';
    $id     = '';
    while (true) {
        if ('session' === $for) {
            $id = 'ta-' . substr(str_shuffle($chars), 0, 29);
        } else {
            $id = substr(str_shuffle($chars), 0, 6);
        }
        $query = db()->prepare(
                    "SELECT COUNT(*) FROM {$table}
                    WHERE id = :id"
                );
        $query->bindValue('id', $id);
        $query->execute();
        $count = $query->fetchColumn();
        if (! $count) {
            // if it does not exist...
            break;
        }
    }
    return $id;
}
/**
* Returns the URL of the website, with HTTP and the final slash
*
* @param  $path string If it's passed, then it's appended to the URL
* @return string
**/
function url($path = '')
{
    return \Config::get('url') . $path;
}
/**
* Returns the avatar resized
* based on $link.
* @param $link string The Twitter URL of the avatar.
* @param $size string bigger or empty.
**/
function get_avatar($link, $size = '')
{
    $link_format = explode(".", $link);
    $format      = end($link_format);
    $link_size   = explode("_", $link);
    array_pop($link_format);
    $link        = implode("_", $link_format);
    if ($size == 'bigger') {
        return $link . '_bigger.'. $format;
    } elseif ($size == '') {
        return $link . '.' . $format;
    }
    return $link . '_normal.' . $format;
}
/**
 * Returns the current IP address of the user.
 * @return string
**/
function get_ip($long = true)
{
    if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }

    if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($long) {
        return ip2long($ip);
    }
    return $ip;
}
/**
 * PHP "strict standards" does not let me to do this
 * end( explode( '.', $something ) )
 * so I do last( explode( '.', $something) )
 * FUCK THE POLICE
 * @param  array $array
 * @return mixed
**/
function last(array $array)
{
    return end($array);
}
/**
* Checks if the request was done to the mobile API
* @return bool
**/
function is_mobile()
{
    return 'mob' === substr($_SERVER['REQUEST_URI'], 1, 3);
}
/**
* Returns the database global variable
* That variable has the zerdb class
* to perform queries.
* @return object
**/
function db()
{
    global $db;
    return $db;
}
/**
 * Minifies the HTML of $output
 * Adapted from http://stackoverflow.com/a/29363569/1932946
 * @param   string $output The HTML to minify
 * @return  string         The HTML minified
 */
function minify_html($output)
{
    if (! Config::get('is_production')) {
        return $output;
    }
    $replace = array(
        //remove tabs before and after HTML tags
        '/\>[^\S ]+/s'   => '>',
        '/[^\S ]+\</s'   => '<',
        //shorten multiple whitespace sequences;
        // keep new-line characters because they matter in JS!!!
        '/([\t ])+/s'  => ' ',
        //remove leading and trailing spaces
        '/^([\t ])+/m' => '',
        '/([\t ])+$/m' => '',
        // remove JS line comments (simple only);
        // do NOT remove lines containing URL
        // (e.g. 'src="http://server.com/"')!!!
        '~//[a-zA-Z0-9 ]+$~m' => '',
        //remove empty lines (sequence of line-end and white-space characters)
        '/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
        //remove empty lines (between HTML tags);
        //cannot remove just any line-end characters
        // because in inline JS they can matter!
        '/\>[\r\n\t ]+\</s'    => '><',
        //remove "empty" lines containing only JS's block end character;
        // join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
        '/}[\r\n\t ]+/s'  => '}',

        '/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
        //remove new-line after JS's function or condition start;
        //join with next line
        '/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
        '/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
        //remove new-line after JS's line end (only most obvious and safe cases)
        '/\),[\r\n\t ]+/s'  => '),',
        //remove quotes from HTML attributes that does not contain spaces;
        //keep quotes around URLs!
        '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' =>
                                '$1$2=$3$4',
        //$1 and $4 insert first white-space character found
        //before/after attribute
        
        // make resulting new lines spaces
        '/\n/' => ' ',
        // remove comments
        '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s' => '',
    );
    $output = preg_replace(
                array_keys($replace),
                array_values($replace),
                $output
            );
    //remove optional ending tags
    // (see http://www.w3.org/TR/html5/syntax.html#syntax-tag-omission )
    $remove = array(
        '</option>', '</li>', '</dt>', '</dd>', '</tr>', '</th>', '</td>'
    );
    $output = str_ireplace($remove, '', $output);
    return $output;
}
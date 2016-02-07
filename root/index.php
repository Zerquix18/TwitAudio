<?php
/**
* Index file of the administration
* The dashboard
* Shows stats :)
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/root/load.php';
$_ADM_BODY['page'] = 'dashboard';
adm_load_template('header');
// don't blame me, i didn't want to do this
?>
I did not want to do this, so here's your dirty dashboard :v<br><br>
<?php
//ty:http://unix.stackexchange.com/questions/119126/command-to-display-memory-usage-disk-usage-and-cpu-load
exec("./foo.sh 2>&1", $output);
echo nl2br( implode("\n", $output) );
echo '<hr>';
// counts
// I'm lazy...
$users = $db->query('SELECT COUNT(*) AS size FROM users');
$sessions = $db->query('SELECT COUNT(*) AS size FROM sessions');
$audios = $db->query('SELECT COUNT(*) AS size FROM audios WHERE reply_to = \'0\'');
$replies = $db->query('SELECT COUNT(*) AS size FROM audios WHERE reply_to != \'0\'');
$favorites = $db->query('SELECT COUNT(*) AS size FROM favorites');
$plays = $db->query('SELECT COUNT(*) AS size FROM plays');
echo 'Total users: ', $users->size, '<br>';
echo 'Total sessions: ', $sessions->size, '<br>';
echo 'Total audios: ', $audios->size, '<br>';
echo 'Total replies: ', $replies->size, '<br>';
echo 'Total favorites: ', $favorites->size, '<br>';
echo 'Total plays: ', $plays->size, '<br>';
adm_load_template('footer');
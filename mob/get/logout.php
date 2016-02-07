<?php
// this file will be called to delete the session
// of the database
require $_SERVER['DOCUMENT_ROOT'] . '/mob/load.php';
checkAuthorization();
// $sess_id is declared in checkAuthorization
$db->query('DELETE FROM sessions WHERE sess_id = ?', $sess_id);
result_success();
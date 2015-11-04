<?php
require dirname(__FILE__) . '/load.php';
$ch = curl_init('http://localhost/TwitAudio/mob/signin.php');
$data = array(
	'access_token' =>
	'142105347-85aZBx89HsjBsFVw3Ebx641IracBzWFdN3UyBng5',
	'access_token_secret' =>
	'5wnjeUjLuwT9suosGISOmvsqU2qpIV4TZqKrOLCQvs3hX'
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$result = curl_exec($ch);
curl_close($ch);
echo $result;
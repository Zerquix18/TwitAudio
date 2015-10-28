<?php
echo shell_exec('git checkout *');
echo shell_exec('git pull -f');
if( ! function_exists('curl_init') )
	return;
require_once('./load.php');
$css = PATH . INC . CS . 'default.css';
$js = PATH . INC . JS . 'default.js';
/*------------------------- css ---------------------*/
$url = 'http://cssminifier.com/raw';
$css = file_get_contents($css);
$data = array(
	'input' => $css,
);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$minified = curl_exec($ch);
if( false === $minified )
	return;
curl_close($ch);
file_put_contents($css, $minified);
/*------------------------- js ---------------------*/
$url = 'http://javascript-minifier.com/raw';
$js = file_get_contents($js);
$data = array(
	'input' => $js,
);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$minified = curl_exec($ch);
if( false === $minified )
	return;
file_put_contents($js, $minified);
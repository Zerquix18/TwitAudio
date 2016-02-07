<?php
// devuelve todo al estado del último commit local, sin borrar archivos nuevos.
shell_exec('git reset --hard HEAD');
// actualiza nuevamente todo, sin borrar archivos nuevos.
// sobreescribe todos los archivos ya existentes en el repo
shell_exec('git pull origin master');
// devuelve los permisos
shell_exec('chmod -R 777 .');
require_once('./load.php');
$css_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/css/default.css';
$js_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/js/default.js';
/*------------------------- css ---------------------*/
$url = 'http://cssminifier.com/raw';
$css = file_get_contents($css_path);
$postdata = array('http' => array(
	'method'  => 'POST',
	'header'  => 'Content-type: application/x-www-form-urlencoded',
	'content' => http_build_query( array('input' => $css) ) 
	)
);
$minified = file_get_contents($url, false, stream_context_create($postdata)) or die("here");
if( false === $minified || empty($minified) )
	exit;
file_put_contents($css_path, $minified);
/*------------------------- js ---------------------*/
$url = 'http://javascript-minifier.com/raw';
$js = file_get_contents($js_path);
$postdata = array('http' => array(
	'method'  => 'POST',
	'header'  => 'Content-type: application/x-www-form-urlencoded',
	'content' => http_build_query( array('input' => $js) ) 
	)
);
$minified = file_get_contents($url, false, stream_context_create($postdata));
if( false === $minified || empty($minified) )
	exit;
file_put_contents($js_path, $minified);
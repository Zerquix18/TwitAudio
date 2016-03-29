<?php
/**
* We cannot use the browser to load styles
* in this way: url.com/assets/css/app/style.css
* because the paths of the CSS
* will fail
* I mean, this: '../fonts' will point to css/fonts
* And in the web it will point to assets/fonts
* So, with this file, CSS styles will load
* In the right directory
* And we can work with separated files
*
**/
header("Content-Type: text/css");
$style = $_GET['style'];
// add a little protection
// just in case it's put in the web
// for any reason
if( preg_match('/([a-z]+)\/([a-z0-9-_]+\.css)/', $style) )
	echo @file_get_contents( $style );
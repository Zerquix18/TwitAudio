<?php
require_once('load.php');

!isset($_COOKIE['ta_session']) and exit( __("You must accept cookies to sign in :(") );

header("Location: ". $twitter->getURL() );
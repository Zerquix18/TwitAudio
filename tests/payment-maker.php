<?php
/**
* A payment maker for testing purposes
*
**/
chdir( dirname(__FILE__) );
chdir( '../' );
require_once('./index.php');
#if( 'cli' === php_sapi_name() )
#	system('clear');

use models\Payment;

$payment = new Payment('stripe');

$card = 'tok_184d8PJJU1SwMmrhr8NmSbBm';
// a new token must be generated every time
$result = $payment->charge( $card );
if( ! $result )
	echo $payment->error;

echo "\n";
<?php
/**
* Payments model
* Must be only called from the MobileAJAXController
*
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
* @todo support paypal
**/
namespace models;

class Payments {

	public $error = '';

	private $stripe_key = 'sk_test_BQokikJOvBiI2HlWgH4olfQ2';

	private $paypal_key = '';

	private $payment_method;

	private $charge_info;

	public function __construct( $payment_method ) {
		if( ! in_array($payment_method, array('paypal', 'stripe') ) ) {
			trigger_error('Payment method is wrong', E_USER_ERROR);
		}
		$this->payment_method = $payment_method;
	}

	public function charge( $key ) {
		ignore_user_abort(true);
		switch( $this->payment_method ) {
			/**
			* Unsupported yet
			* @todo
			* case "paypal":
			*	$charge_result = $this->charge_paypal( $key );
			*	break;
			**/
			case "stripe":
				$charge_result = $this->charge_stripe( $key );
				break;
			default:
			return false;
		}
		if( ! $charge_result ) {
			return false;
		}
		// successfull charge, now store some info
		$info = array('stripe_info' => $this->charge_info);
		$info['ip'] = get_ip();
		$info['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		/**
		* The info will be used just in cases of a dispute
		* Or to be reviewed after a payment
		* Will not be indexed or searched
		* So it's simply stored as JSON
		**/
		$info = json_encode($info);
		$this->db->insert('payments', array(
				
			)
		);
	}

	public function charge_stripe( $token ) {
		\Stripe\Stripe::setApiKey( $this->key );
		try {
			$this->charge_info = \Stripe\Charge::create(array(
					"amount"      => 1300, // <- CENTS
					"currency"    => "usd",
					"source"      => $token,
					"description" => "Example charge"
		    ));
		} catch(\Stripe\Error\Card $e) {
			// Since it's a decline, \Stripe\Error\Card will be caught
			$body        = $e->getJsonBody();
			$err         = $body['error'];
			$this->error = $err['message'];
		} catch (\Stripe\Error\RateLimit $e) {
			$this->error = 'Internal error';
		} catch (\Stripe\Error\InvalidRequest $e) {
			$this->error = 'Internal error';
		} catch (\Stripe\Error\Authentication $e) {
			$this->error = 'Internal error';
		} catch (\Stripe\Error\ApiConnection $e) {
			$this->error = 'Internal error';
		} catch (\Stripe\Error\Base $e) {
			$this->error = 'Internal error';
		} catch (Exception $e) {
			$this->error = 'Internal error';
		}
		return ! empty($this->error);
	}
	/**
	* Unsupported yet
	* @todo
	**/
	function charge_paypal( $token ) {
		
	}
	/**
	* Unsupported yet
	* @todo
	**/
	function get_paypal_url() {

	}
}
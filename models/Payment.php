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

class Payment {

	public $error       = '';

	private $stripe_key = 'sk_test_d4y4iNRanCY2Yj2pu0i59SMW';

	private $paypal_key = '';

	private $payment_method;

	private $charge_info;

	public function __construct( $payment_method, $user_id ) {
		if( ! in_array($payment_method, array('paypal', 'stripe') ) ) {
			trigger_error('Payment method is wrong', E_USER_ERROR);
		}
		$this->payment_method = $payment_method;
		$this->user_id        = $user_id;
	}

	public function charge( $token ) {
		ignore_user_abort(true);
		switch( $this->payment_method ) {
			/**
			* Unsupported yet
			* @todo
			* case "paypal":
			*	$charge_result = $this->charge_paypal($token);
			*	break;
			**/
			case "stripe":
				$charge_result = $this->charge_stripe($token);
				break;
			default:
			return false;
		}
		if( ! $charge_result ) {
			return false;
		}
		$ip         = get_ip();
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$info = json_encode($info);
		$this->db->insert('payments', array(
				'id'         => $this->user_id,
				'user_id'    => $this->payment_method,
				'user_agent' => $user_agent,
				'ip'         => $ip,
				'time'       => time()
			)
		);
		$this->db->update('users', array(
				'upload_seconds_limit' => '300',
				'premium_until'        => strtotime('+30 days')
			)
		)->where('id', $this->user_id)
		 ->_();
		return true;
	}
	public function charge_stripe( $token ) {
		\Stripe\Stripe::setApiKey($this->stripe_key);
		try {
			$this->charge_info = \Stripe\Charge::create(array(
					"amount"      => 130, // <- CENTS
					"currency"    => "usd",
					"source"      => $token,
					"description" => 'Payment for user ID: ' . $this->user_id
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
		// if it's empty, then there was no error, and it will return true
		// if it has something, there was an error, then return false.
		return empty($this->error);
	}
	/**
	* Unsupported yet
	* @todo
	**/
	function charge_paypal( $token ) {}
	/**
	* Unsupported yet
	* @todo
	**/
	function get_paypal_url() {}

	// what ya looking at
}
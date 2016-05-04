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

	/**
	* Charges the user and makes it premium
	* @return array
	**/
	public function charge( $token ) {
		global $db; // this is a bit dirty...
		ignore_user_abort(true);
		switch( $this->payment_method ) {
			/**
			* Unsupported yet
			* @todo
			* case 'paypal':
			*	$charge_result = $this->charge_paypal($token);
			*	break;
			**/
			case 'stripe':
				$charge_result = $this->charge_stripe($token);
				break;
			default:
				return array();
		}
		if( ! $charge_result ) {
			return array();
		}
		$ip         = get_ip();
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$this->db->insert('payments', array(
				'id'         => $this->user_id,
				'user_id'    => $this->payment_method,
				'user_agent' => $user_agent,
				'ip'         => $ip,
				'time'       => time()
			)
		);
		$premium_until = $this->get_next_month();
		$db->update('users', array(
				'upload_seconds_limit' => '300',
				'premium_until'        => $premium_until
			)
		)->where('id', $this->user_id)
		 ->_();
		$result = array(
				'premium_until' => $premium_until
			);
		return $result;
	}
	public function charge_stripe( $token ) {
		\Stripe\Stripe::setApiKey($this->stripe_key);
		try {
			$this->charge_info = \Stripe\Charge::create(array(
					'amount'      => 130, // <- CENTS
					'currency'    => 'usd',
					'source'      => $token,
					'description' => 'Payment for user ID: ' . $this->user_id
		    ));
		} catch(\Stripe\Error\Card $e) {
			// Since it's a decline, \Stripe\Error\Card will be caught
			$body        = $e->getJsonBody();
			$err         = $body['error'];
			$error_code  = $err['code'];
			switch( $error_code ) {
				case 'invalid_number':
				case 'incorrect_number':
					$this->error = 'Seems like the card number is invalid';
					break;
				case 'invalid_expiry_month':
					$this->error = 'The card exp. month is invalid';
					break;
				case 'invalid_expiry_year':
					$this->error = 'The card exp. year is invalid';
					break;
				case 'invalid_cvc':
				case 'incorrect_cvc':
					$this->error = 'The card security code is invalid';
					break;
				case 'expired_card':
					$this->error = 'The card has expired';
					break;
				case 'card_declined':
					$this->error = 'We are sorry but your card was declined.';
					break;
				case 'missing':
				case 'processing_error':
				default:
					$this->error = 'We apologize we had an internal error';
					break;
			}
		} catch (\Stripe\Error\RateLimit $e) {
			$this->error = 'We apologize we had an internal error';
		} catch (\Stripe\Error\InvalidRequest $e) {
			$this->error = 'We apologize we had an internal error';
		} catch (\Stripe\Error\Authentication $e) {
			$this->error = 'We apologize we had an internal error';
		} catch (\Stripe\Error\ApiConnection $e) {
			$this->error = 'We apologize we had an internal error';
		} catch (\Stripe\Error\Base $e) {
			$this->error = 'We apologize we had an internal error';
		} catch (\Exception $e) {
			$this->error = 'We apologize we had an internal error';
		}
		// if it's empty, then there was no error, and it will return true
		// if it has something, there was an error, then return false.
		return ! $this->error;
	}
	/**
	* Unsupported yet
	* @todo
	**/
	public function charge_paypal( $token ) {}
	/**
	* Unsupported yet
	* @todo
	**/
	public function get_paypal_url() {}

	/**
	* Returns the timestamp for the same day number
	* of today in the next month
	* thanks http://stackoverflow.com/a/5760371/1932946
	* for doing what PHP could not in its entiry history
	**/
	public function get_next_month() {
		$date      = new \DateTime('now');
		$start_day = $date->format('j');

		$date->modify('+1 month');

		$end_day   = $date->format('j');

		if( $start_day != $end_day ) {
			$date->modify('last day of last month');
		}
		
		return $date->getTimestamp();
	}

	// what ya looking at
}
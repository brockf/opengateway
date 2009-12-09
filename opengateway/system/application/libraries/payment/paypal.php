<?php
class Paypal
{
	public $post = array(
		//'creditcardtype'	=> '', 		// Visa, MasterCard, Discover, Amex
		//'acct'				=> '',		// Credit card number
		//'expdate'			=> '',		// MMYYYY
		'cvv2'				=> '123',		// 4 char max
		//'firstname'			=> '',		// 25 char max
		//'lastname'			=> '',		// 25 char max
		//'street'			=> '',		// 100 char max
		//'city'				=> '',		// 40 char max
		//'state'				=> '',		// 40 char max
		'countrycode'		=> 'US',	// 2 char max
		//'zip'				=> '',		// 20 char max
		'phonenum'			=> '',		// 20 char max
		//'amt'				=> 0, 		// <= 10,000 USD, no currency symbol, required 2 decimal places, comma optional
		'invnum'			=> '',		// Internal unique ID, helps prevent duplicate charges
		'version'			=> '56.0',
		'paymentaction'		=> 'sale'


	);


	 function Charge($client_id, $order_id, $gateway, $customer, $params, $credit_card)
	{
		$CI =& get_instance();
		
		switch($params['card_type'])
		{
			case 'visa';
				$card_type = 'Visa';
			break;
			case 'mc';
				$card_type = 'MasterCard';
			break;
			case 'discover';
				$card_type = 'Discover';
			break;
			case 'amex';
				$card_type = 'Amex';
			break;
		}
		
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		$post = array();
		$post['method'] = 'DoDirectPayment';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['amt'] = $params['amount']; 
		$post['acct'] = $credit_card['card_num'];
		$post['creditcardtype'] = $card_type;
		$post['expdate'] = $credit_card['exp_month'].$credit_card['exp_year'];
		$post['invnum'] = $order_id;
		
		if(isset($params['customer_id'])) {
			$post['firstname'] = $customer['first_name'];
			$post['lastname'] = $customer['last_name'];
			$post['street'] = $customer['address_1'].$customer['address_2'];
			$post['city'] = $customer['city'];
			$post['state'] = 'CO';
			$post['zip'] = $customer['postal_code'];
		}
		
		$post_data = array_merge($post, $this->post);
		
		$response = $this->Process($post_url, $post_data, $order_id);
		
		$response = $this->response_to_array($response);
		
		if($response['ACK'] == 'Success') {
			$CI->load->model('order_authorization_model');
			if($order_id) {
				$CI->order_authorization_model->SaveAuthorization($order_id, $response['TRANSACTIONID']);
			}
			$CI->order_model->SetStatus($order_id, 1);
			
			$response_array = array('order_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$CI->load->model('order_model');
			$CI->order_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
		
		
	}
	
	function Process($url, $post_data, $order_id = FALSE)
	{
		$CI =& get_instance();
		
		$data = '';

		// Build the data string for the request body
		foreach($post_data as $key => $value)
		{
			if(!empty($value))
			{
				$data .= strtoupper($key) . '=' . urlencode($value) . '&';
			}
		}

		// remove the extra ampersand
		$data = substr($data, 0, strlen($data) - 1);
		
		// setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
	
		// turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		// setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
		// getting response from server
		$response = curl_exec($ch);
		
		return $response;
		
	}
	
	function Recur($client_id, $gateway, $customer, $params, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences)
	{		
		$CI =& get_instance();
		
		// Paypal has a lot of required fields
		if(!isset($params['description'])) {
			die($CI->response->Error(5012));
		}
		
		// Create a new paypal profile
		$response = $this->CreateProfile($gateway, $customer, $params, $start_date, $subscription_id, $total_occurrences);
		
		if($response) {
			$profile_id = $response['profile_id'];	
		} else {
			die($CI->response->Error(5005));
		}
		
		// save the api_customer_reference
		$CI->load->model('subscription_model');
		$CI->subscription_model->SaveApiCustomerReference($subscription_id, $profile_id);
		
		if($response['success'] == TRUE){
				$response_array = array('subscription_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->subscription_model->MakeInactive($subscription_id);
				
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		
		return $response;
	}
	
	function CreateProfile($gateway, $customer, $params, $start_date, $subscription_id, $total_occurrences)
	{
		$CI =& get_instance();
		
		switch($params['card_type'])
		{
			case 'visa';
				$card_type = 'Visa';
			break;
			case 'mc';
				$card_type = 'MasterCard';
			break;
			case 'discover';
				$card_type = 'Discover';
			break;
			case 'amex';
				$card_type = 'Amex';
			break;
		}
		
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['arb_url_live'];
			break;
			case 'test':
				$post_url = $gateway['arb_url_test'];
			break;
			case 'dev':
				$post_url = $gateway['arb_url_dev'];
			break;
		}
		
		$post = array();
		$post['version'] = '60';
		$post['method'] = 'CreateRecurringPaymentsProfile';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['amt'] = $params['amount']; 
		$post['acct'] = $params['credit_card']['card_num'];
		$post['creditcardtype'] = $card_type;
		$post['expdate'] = $params['credit_card']['exp_month'].$params['credit_card']['exp_year'];
		$post['billingperiod'] = 'Day';
		$post['billingfrequency'] = $params['recur']['interval'];
		$post['profilestartdate'] = date('c', strtotime($start_date));
		$post['desc'] = $params['description'];
		$post['totalbillingcycles'] = $total_occurrences;
		//$post['invnum'] = $order_id;
		
		if(isset($params['customer_id'])) {
			$post['firstname'] = $customer['first_name'];
			$post['lastname'] = $customer['last_name'];
			$post['street'] = $customer['address_1'].$customer['address_2'];
			$post['city'] = $customer['city'];
			$post['state'] = $customer['state'];
			$post['zip'] = $customer['postal_code'];
			$post['email'] = $customer['email'];
		} else {
			$error = FALSE;
			if(!isset($params['first_name'])) {
				$error = TRUE;
			}
			if(!isset($params['last_name'])) {
				$error = TRUE;
			}
			if(!isset($params['address_1'])) {
				$error = TRUE;
			}
			if(!isset($params['city'])) {
				$error = TRUE;
			}
			if(!isset($params['state'])) {
				$error = TRUE;
			}
			if(!isset($params['postal_code'])) {
				$error = TRUE;
			}
			
			if($error) {
				die($CI->response->Error(5013));
			}
			
			
			$post['firstname'] = $params['first_name'];
			$post['lastname'] = $params['last_name'];
			$post['street'] = $params['address_1'];
			
			if(isset($params['address_1'])) {
				$post['street'] .= $params['address_2'];
			}
			
			$post['city'] = $params['city'];
			$post['state'] = $params['state'];
			$post['zip'] = $params['postal_code'];
			
			if(isset($params['email'])) {
				$post['email'] .= $params['email'];
			}
			
			
		}
		
		$post_response = $this->Process($post_url, $post);
		
		$post_response = $this->response_to_array($post_response);
		
		if($post_response['ACK'] == 'Success') {
			$response['success'] = TRUE;
			$response['profile_id'] = $post_response['PROFILEID'];
		} else {
			$response['success'] = FALSE;
			$response['profile_id'] = FALSE;
			$response['reason'] = $post_response['L_LONGMESSAGE0'];
		}
		
		return $response;
		
	}
	
	function CancelRecurring($client_id, $subscription, $gateway)
	{
		$CI =& get_instance();
		$CI->load->model('subscription_model');
		
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $subscription->arb_live_url;
			break;
			case 'test':
				$post_url = $subscription->arb_test_url;
			break;
			case 'dev':
				$post_url = $subscription->arb_dev_url;
			break;
		}
		
		$post = array();
		$post['version'] = '60';
		$post['method'] = 'ManageRecurringPaymentsProfileStatus';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['profileid'] = $subscription->api_customer_reference;
		$post['action'] = 'Cancel';
		
		$post_response = $this->Process($post_url, $post);
		
		$post_response = $this->response_to_array($post_response);
		
		if($post_response['ACK'] == 'Success') {
			$response = TRUE;
		} else {
			$response = FALSE;
		}
		
		return $response;
	}
	
	private function response_to_array($string)
	{
		$string = urldecode($string);
		$pairs = explode('&', $string);
		$values = array();

		foreach($pairs as $pair)
		{
			list($key, $value) = explode('=', $pair);
			$values[$key] = $value;
		}

		return $values;
	}

}

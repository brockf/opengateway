<?php

class paypal
{
	function Settings()
	{
		$settings['name'] = 'PayPal Pro';
		$settings['class_name'] = 'paypal';
		$settings['description'] = 'PayPal Pro is easy to setup and even easier to use.  Though not as powerful as other gateways (you cannot edit existing subscriptions, only cancel them), this gateway is very easy to setup.  Requires the Recurring Billing addon.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$0.00';
		$settings['monthly_fee'] = '$30.00';
		$settings['transaction_fee'] = '2.5% + $0.30';
		$settings['purchase_link'] = 'http://www.opengateway.net/gateways/paypal';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 1;
		$settings['required_fields'] = array('enabled',
											 'mode',
											 'user',
											 'pwd',
											 'signature',
											 'currency',
											 'accept_visa',
											 'accept_mc',
											 'accept_discover',
											 'accept_dc',
											 'accept_amex'
											);
		
		$settings['field_details'] = array(
										'enabled' => array(
														'text' => 'Enable this gateway?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'0' => 'Disabled')
														),
										'mode' => array(
														'text' => 'Mode',
														'type' => 'select',
														'options' => array(
																		'live' => 'Live Mode',
																		'test' => 'Sandbox'
																		)
														),
										'user' => array(
														'text' => 'API Username',
														'type' => 'text'
														),
										'pwd' => array(
														'text' => 'API Password',
														'type' => 'text'
														),
										'signature' => array(
														'text' => 'API Signature',
														'type' => 'text',
														),
										'currency' => array(
														'text' => 'Currency',
														'type' => 'select',
														'options' => array(
																		'USD' => 'US Dollar',
																		'CAD' => 'Canadian Dollar',
																		'EUR' => 'Euro',
																		'GBP' => 'UK Pound',
																		'AUD' => 'Australian Dollar',
																		'JPY' => 'Japanese Yen'
																	)
														),
										'accept_visa' => array(
														'text' => 'Accept VISA?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_mc' => array(
														'text' => 'Accept MasterCard?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_discover' => array(
														'text' => 'Accept Discover?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_dc' => array(
														'text' => 'Accept Diner\'s Club?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_amex' => array(
														'text' => 'Accept American Express?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														)
											);
		return $settings;
	}
	
	function TestConnection($client_id, $gateway)
	{
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
		}
		
		$post = array();
		$post['version'] = '56.0';
		$post['method'] = 'GetBalance';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		
		$response = $this->Process($post_url, $post);
		
		$response = $this->response_to_array($response);
		
		$CI =& get_instance();
		
		if($response['ACK'] == 'Success') {
			return TRUE;
		} else {			
			return FALSE;
		}


	}
	
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
		}
		
		$post = array();
		$post['version'] = '56.0';
		$post['paymentaction'] = 'sale';
		$post['method'] = 'DoDirectPayment';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['amt'] = $params['amount']; 
		$post['acct'] = $credit_card['card_num'];
		$post['creditcardtype'] = $card_type;
		$post['expdate'] = $credit_card['exp_month'].$credit_card['exp_year'];
		$post['invnum'] = $order_id;
		$post['currencycode'] = $gateway['currency'];
		
		if(isset($credit_card['cvv'])) {
			$post['cvv2'] = $credit_card['cvv'];
		}
		
		if(isset($params['customer_id'])) {
			$post['firstname'] = $customer['first_name'];
			$post['lastname'] = $customer['last_name'];
			$post['street'] = $customer['address_1'].$customer['address_2'];
			$post['city'] = $customer['city'];
			$post['state'] = $customer['state'];
			$post['zip'] = $customer['postal_code'];
			$post['countrycode'] = $customer['country'];
			$post['phonenum'] = $customer['phone'];
		}
		
		$response = $this->Process($post_url, $post, $order_id);
		
		$response = $this->response_to_array($response);
		
		if($response['ACK'] == 'Success') {
			$CI->load->model('order_authorization_model');
			if($order_id) {
				$CI->order_authorization_model->SaveAuthorization($order_id, $response['TRANSACTIONID']);
			}
			
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {

			
			$response_array = array('reason' => $response['L_LONGMESSAGE0']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
		
		
	}
	
	function Refund($client_id, $order_id, $gateway, $customer, $params, $credit_card)
	{
		$CI =& get_instance();
		
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
		}
		
		$post = array();
		$post['version'] = '56.0';
		$post['paymentaction'] = 'sale';
		$post['method'] = 'RefundTransaction';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['transactionid'] = $params['authorization']->tran_id;
		if($params['amount'] == $params['order']['amount']) {
			$post['refundtype'] = 'FULL'; 
		} else {
			$post['refundtype'] = 'PARTIAL';
			$post['amt'] = $params['amount'];
		}
	
		
		$response = $this->Process($post_url, $post, $order_id);
		
		$response = $this->response_to_array($response);
		
		if($response['ACK'] == 'Success') {
			$response = $CI->response->TransactionResponse(1, array());
		} else {
			$response_array = array('reason' => $response['L_LONGMESSAGE0']);
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
		
		// If the start date is today, we'll do the first one manually
		if(strtotime($start_date) == strtotime(date('Y-m-d'))) {
			// Create an order
			$CI->load->model('order_model');
			
			$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $subscription_id);
			$response = $this->Charge($client_id, $order_id, $gateway, $customer, $params, $credit_card);
			
			if($response['response_code'] == 1) {
				$response_array['charge_id'] = $response['charge_id'];
				$start_date = date('Y-m-d', strtotime($start_date) + ($interval * 86400));
			} else {
				$CI->load->model('subscription_model');
				$CI->subscription_model->MakeInactive($subscription_id);
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
				return $response;
				exit;
			}
		}
		
		// Create a new paypal profile
		$response = $this->CreateProfile($client_id, $gateway, $customer, $params, $start_date, $subscription_id, $total_occurrences, $interval);
		
		if($response) {
			$profile_id = $response['profile_id'];	
		} else {
			die($CI->response->Error(5005));
		}
		
		// save the api_customer_reference
		
		$CI->subscription_model->SaveApiCustomerReference($subscription_id, $profile_id);
		
		if($response['success'] == TRUE){
				$response_array['recurring_id'] = $subscription_id;
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->subscription_model->MakeInactive($subscription_id);
				
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		
		return $response;
	}
	
	function CreateProfile($client_id, $gateway, $customer, $params, $start_date, $subscription_id, $total_occurrences, $interval)
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
		$post['billingfrequency'] = $interval;
		$post['profilestartdate'] = date('c', strtotime($start_date));
		$post['totalbillingcycles'] = $total_occurrences;
		
		if(isset($params['credit_card']['cvv'])) {
			$post['cvv2'] = $params['credit_card']['cvv'];
		}
		
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
			if(!isset($params['customer']['first_name'])) {
				$error = TRUE;
			}
			if(!isset($params['customer']['last_name'])) {
				$error = TRUE;
			}
			if(!isset($params['customer']['address_1'])) {
				$error = TRUE;
			}
			if(!isset($params['customer']['city'])) {
				$error = TRUE;
			}
			if(!isset($params['customer']['state'])) {
				$error = TRUE;
			}
			if(!isset($params['customer']['postal_code'])) {
				$error = TRUE;
			}
			
			if($error) {
				die($CI->response->Error(5013));
			}
			
			
			$post['firstname'] = $params['customer']['first_name'];
			$post['lastname'] = $params['customer']['last_name'];
			$post['street'] = $params['customer']['address_1'];
			
			if(isset($params['customer']['address_2'])) {
				$post['street'] .= ' '.$params['customer']['address_2'];
			}
			
			$post['city'] = $params['customer']['city'];
			$post['state'] = $params['customer']['state'];
			$post['zip'] = $params['customer']['postal_code'];
			
			if(isset($params['customer']['email'])) {
				$post['email'] .= $params['customer']['email'];
			}
		}
		
		// Get the company name
		$CI->load->model('client_model');
		$company = $CI->client_model->GetClientDetails($client_id)->company;
		
		$post['desc'] = $company.' Subscription';
		
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
				$post_url = $subscription['arb_prod_url'];
			break;
			case 'test':
				$post_url = $subscription['arb_test_url'];
			break;
			case 'dev':
				$post_url = $subscription['arb_dev_url'];
			break;
		}
		
		$post = array();
		$post['version'] = '60';
		$post['method'] = 'ManageRecurringPaymentsProfileStatus';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['profileid'] = $subscription['api_customer_reference'];
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
	
	function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params)
	{
		$CI =& get_instance();
		$CI->load->model('subscription_model');
		
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $subscription['arb_prod_url'];
			break;
			case 'test':
				$post_url = $subscription['arb_test_url'];
			break;
			case 'dev':
				$post_url = $subscription['arb_dev_url'];
			break;
		}
		
		$post = array();
		$post['version'] = '58.0';
		$post['method'] = 'UpdateRecurringPaymentsProfile';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['profileid'] = $subscription['api_customer_reference'];
		
		if(isset($params['amount'])) {
			$post['amt'] = $params['amount'];
		}
		
		if(isset($params['customer_id'])){
			
			$post['firstname'] = $customer['first_name'];
			$post['lastname'] = $customer['last_name'];
			$post['street'] = $customer['address_1'];
			
			if($customer['address_1'] != '') {
				$post['street'] .= ' '.$customer['address_2'];
			}
			
			$post['city'] = $customer['city'];
			$post['state'] = $customer['state'];
			$post['zip'] = $customer['postal_code'];
		}
		
		if(isset($params['recur']['interval'])) {
			$post['totalbillingcycles'] = round((strtotime($subscription['end_date']) - strtotime($subscription['start_date'])) / ($params['recur']['interval'] * 86400), 0);
		}
		
		$post_response = $this->Process($post_url, $post);
		
		$post_response = $this->response_to_array($post_response);
		
		if($post_response['ACK'] == 'Success') {
			$response = TRUE;
		} else {
			$response = FALSE;
		}
		
		return $response;
	}
	
	function ChargeRecurring($client_id, $gateway, $params)
	{
		$details = $this->GetProfileDetails($client_id, $gateway, $params);
		if(!$details) {
			return FALSE;
		}
		$last_payment = date('Y-m-d',strtotime($details['LASTPAYMENTDATE']));
		$today = date('Y-m-d');
		$failed_payments = $details['FAILEDPAYMENTCOUNT'];
		
		if($last_payment == $today && $failed_payments < 1) {		
			$response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = "The charge has failed.";
		}
		
		return $response;
		
	}
	
	function GetProfileDetails($client_id, $gateway, $params)
	{
		$CI =& get_instance();
		$CI->load->model('subscription_model');
		
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $params['arb_live_url'];
			break;
			case 'test':
				$post_url = $params['arb_test_url'];
			break;
			case 'dev':
				$post_url = $params['arb_dev_url'];
			break;
			default:
				$post_url = $params['arb_dev_url'];
			break;
		}
		
		$post = array();
		$post['version'] = '60';
		$post['method'] = 'GetRecurringPaymentsProfileDetails';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['profileid'] = $params['api_customer_reference'];
		
		$post_response = $this->Process($post_url, $post);
		
		if($post_response['ACK'] == 'Success') {
			$response = $this->response_to_array($post_response);
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

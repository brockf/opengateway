<?php

class paypal
{
	public $settings;
	
	// if set to TRUE, any recurring interval of 30, 60, etc. will be converted to a monthly interval so that
	// the charges come on the same day each month
	public $same_day_every_month = FALSE;
		
	function paypal() {
		$this->settings = $this->Settings();
	}

	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'PayPal Pro';
		$settings['class_name'] = 'paypal';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'PayPal Pro is easy to setup and even easier to use.  Though not as powerful as other gateways (you cannot edit existing subscriptions, only cancel them), this gateway is very easy to setup.  Requires the Recurring Billing addon.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$0.00';
		$settings['monthly_fee'] = '$30.00';
		$settings['transaction_fee'] = '2.5% + $0.30';
		$settings['purchase_link'] = 'https://www.paypal.com/ca/mrb/pal=Q4XUN8HMLDQ2N';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 1;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 1;
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
																		'JPY' => 'Japanese Yen',
																		'MXN' => 'Mexican Pesos',
																		'CZK' => 'Czech Koruna',
																		'DKK' => 'Danish Krone',
																		'HKD' => 'Hong Kong Dollar',
																		'HUF' => 'Hungarian Forint',
																		'ILS' => 'Israeli New Shegel',
																		'NOK' => 'Norwegian Krone',
																		'NZD' => 'New Zealand Dollar',
																		'PHP' => 'Philippine Peso',
																		'PLN' => 'Polish Zloty',
																		'SGD' => 'Singapore Dollar',
																		'SEK' => 'Swedish Krona',
																		'CHF' => 'Swiss Franc',
																		'TWD' => 'Taiwan New Dollar',
																		'THB' => 'Thai Baht'
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
	
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{
		$CI =& get_instance();
		
		// get card type in proper format
		switch($credit_card['card_type']) {
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
		switch($gateway['mode']) {
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
		}
		
		// prep exp_date
		if (strlen($credit_card['exp_year']) == 2) {
			$credit_card['exp_year'] = '20' . $credit_card['exp_year'];
		}
		
		$post = array();
		$post['version'] = '56.0';
		$post['paymentaction'] = 'sale';
		$post['method'] = 'DoDirectPayment';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['amt'] = $amount; 
		$post['acct'] = $credit_card['card_num'];
		$post['creditcardtype'] = $card_type;
		$post['expdate'] = str_pad($credit_card['exp_month'], 2, "0", STR_PAD_LEFT) . $credit_card['exp_year'];
		$post['invnum'] = $order_id;
		$post['currencycode'] = $gateway['currency'];
		$post['ipaddress'] = $customer['ip_address'];
		
		if (isset($credit_card['cvv'])) {
			$post['cvv2'] = $credit_card['cvv'];
		}
		
		if (isset($customer['customer_id'])) {
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
		
		if ($response['ACK'] == 'Success') {
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['TRANSACTIONID']);
			
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		else {
			$response_array = array('reason' => $response['L_ERRORCODE0'] . ' - ' . $response['L_LONGMESSAGE0']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;	
	}
	
	function Recur($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences)
	{		
		$CI =& get_instance();
		
		// prep exp_date
		if (strlen($credit_card['exp_year']) == 2) {
			$credit_card['exp_year'] = '20' . $credit_card['exp_year'];
		}
		
		// If the start date is today, we'll do the first one manually
		if ($charge_today === TRUE) {
			// Create an order
			$CI->load->model('charge_model');
			
			$customer['customer_id'] = (isset($customer['customer_id'])) ? $customer['customer_id'] : FALSE;
			$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);
			$response = $this->Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card);
			
			if ($response['response_code'] == 1) {
				$response_array['charge_id'] = $response['charge_id'];
				$start_date = date('Y-m-d', strtotime($start_date) + ($interval * 86400));
				$CI->charge_model->SetStatus($order_id, 1);
			} else {
				$CI->load->model('recurring_model');
				$CI->recurring_model->MakeInactive($subscription_id);
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
				
				return $response;
			}
		}
		
		// get true recurring rate, first
		$subscription = $CI->recurring_model->GetRecurring($client_id, $subscription_id);
		
		// Create a new PayPal profile
		$response = $this->CreateProfile($client_id, $gateway, $customer, $subscription['amount'], $credit_card, $start_date, $subscription_id, $total_occurrences, $interval);
		
		if (is_array($response) and $response['success'] == TRUE) {
			$profile_id = $response['profile_id'];	
			
			$CI->recurring_model->SaveApiCustomerReference($subscription_id, $profile_id);
		}
		
		if ($response['success'] == TRUE){
				$response_array['recurring_id'] = $subscription_id;
				$response = $CI->response->TransactionResponse(100, $response_array);
		} else {
			// Make the subscription inactive
			$CI->recurring_model->MakeInactive($subscription_id);
			
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	function Refund ($client_id, $gateway, $charge, $authorization)
	{
		$CI =& get_instance();
		
		// Get the proper URL
		switch($gateway['mode']) {
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
		}
		
		$post = array();
		$post['version'] = '56.0';
		$post['method'] = 'RefundTransaction';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['transactionid'] = $authorization->tran_id;
		$post['refundtype'] = 'FULL';
	
		$response = $this->Process($post_url, $post);
		$response = $this->response_to_array($response);
		
		if ($response['ACK'] == 'Success') {
			return TRUE;
		} else {
			return FALSE;
		}
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

	function CreateProfile($client_id, $gateway, $customer, $amount, $credit_card, $start_date, $subscription_id, $total_occurrences, $interval)
	{
		$CI =& get_instance();
		
		switch($credit_card['card_type']) {
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
		$post['amt'] = $amount; 
		$post['acct'] = $credit_card['card_num'];
		$post['currencycode'] = $gateway['currency'];
		$post['creditcardtype'] = $card_type;
		$post['expdate'] = str_pad($credit_card['exp_month'], 2, "0", STR_PAD_LEFT) . $credit_card['exp_year'];
		
		if ($this->same_day_every_month == TRUE and $interval % 30 === 0) {
			$post['billingperiod'] = 'Month';
			$post['billingfrequency'] = ($interval / 30);
		} else {
			$post['billingperiod'] = 'Day';
			$post['billingfrequency'] = $interval;
		}
		
		$post['profilestartdate'] = date('c', strtotime($start_date));
		$post['ipaddress'] = $customer['ip_address'];
		
		if(isset($credit_card['cvv'])) {
			$post['cvv2'] = $credit_card['cvv'];
		}
		
		// build customer address
		$post['firstname'] = (isset($customer['first_name'])) ? $customer['first_name'] : '';
		$post['lastname'] = (isset($customer['last_name'])) ? $customer['last_name'] : '';
		$post['street'] = (isset($customer['address_1'])) ? $customer['address_1'] : '';
		if (isset($customer['address_2'])) {
			$post['street'] .= ' ' . $customer['address_2'];
		}
		$post['city'] = (isset($customer['city'])) ? $customer['city'] : '';
		$post['state'] = (isset($customer['state'])) ? $customer['state'] : '';
		$post['countrycode'] = (isset($customer['country'])) ? $customer['country'] : '';
		$post['zip'] = (isset($customer['postal_code'])) ? $customer['postal_code'] : '';
		$post['email'] = (isset($customer['email'])) ? $customer['email'] : '';
		
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
		$CI->load->model('recurring_model');
		
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
		$CI->load->model('recurring_model');
		
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
			$post['currencycode'] = $gateway['currency'];
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
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $params);
	}
	
	function ChargeRecurring($client_id, $gateway, $params)
	{
		$details = $this->GetProfileDetails($client_id, $gateway, $params);
		if(!$details) {
			return FALSE;
		}
		/*
		* We can check to see if today was the last payment date
		$last_payment = date('Y-m-d',strtotime($details['LASTPAYMENTDATE']));
		*/
		$today = date('Y-m-d');
		$failed_payments = $details['FAILEDPAYMENTCOUNT'];
		$status = $details['STATUS'];
		
		if ($failed_payments < 1 and $status != 'Cancelled') {		
			$response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = "The charge has failed.";
		}
		
		// we'll need to adjust the interval upon each charge so that the next_charge_date is a month from now
		if ($this->same_day_every_month === TRUE and $params['interval'] % 30 === 0) {
			$months = $params['interval'] / 30;
			$plural = ($months > 1) ? 's' : '';
			$next_charge = date('Y-m-d',strtotime('today + ' . $months . ' month' . $plural));
			
			// we'll return the $next_charge in this response
			$response['next_charge'] = $next_charge;			
		}
		
		return $response;	
	}
	
	function GetProfileDetails($client_id, $gateway, $params)
	{
		$CI =& get_instance();
		$CI->load->model('recurring_model');
		
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = isset($params['arb_prod_url']) ? $params['arb_prod_url'] : $params['arb_live_url'];
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
		$response = $this->response_to_array($post_response);
		
		if ($response['ACK'] == 'Success') {
			return $response;
		} else {
			return FALSE;
		}
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

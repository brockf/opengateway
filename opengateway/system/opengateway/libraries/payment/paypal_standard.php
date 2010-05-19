<?php

class paypal_standard
{
	var $settings;
	
	function paypal_standard() {
		$this->settings = $this->Settings();
	}

	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'PayPal Express Checkout';
		$settings['class_name'] = 'paypal_standard';
		$settings['external'] = TRUE;
		$settings['description'] = 'PayPal Express Checkout is the easiest, cheapest way to accept payments online.  Any Website Payments Standard account supports it.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$0';
		$settings['monthly_fee'] = '$0';
		$settings['transaction_fee'] = '2.9% + $0.30';
		$settings['purchase_link'] = 'https://www.paypal.com/ca/mrb/pal=Q4XUN8HMLDQ2N';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 0;
		$settings['requires_customer_information'] = 0;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array('enabled',
											 'mode',
											 'user',
											 'pwd',
											 'signature',
											 'currency'
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
														)
											);
		return $settings;
	}
	
	function TestConnection($client_id, $gateway)
	{
		return TRUE;
	}
	
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card, $return_url, $cancel_url)
	{
		$CI =& get_instance();
		$CI->load->model('charge_data_model');
		$CI->load->helper('url');
		$CI->load->model('client_model');
		
		$client = $CI->client_model->GetClient($client_id,$client_id);
		
		// save the return URL
		$CI->charge_data_model->Save($order_id, 'return_url', $return_url);
		
		$post_url = $this->GetAPIURL($gateway);
		
		$post = array();
		$post['version'] = '56.0';
		$post['method'] = 'SetExpressCheckout';
		$post['returnurl'] = site_url('callback/paypal/confirm/' . $order_id);
		$post['cancelurl'] = (!empty($cancel_url)) ? $cancel_url : 'http://www.paypal.com';
		$post['noshipping'] = '0';
		$post['allownote'] = '0';
		$post['localecode'] = $client['country'];
		$post['solutiontype'] = 'Mark';
		$post['landingpage'] = 'Billing';
		$post['channeltype'] = 'Merchant';
		
		if (isset($customer['email'])) {
			$post['email'] = $customer['email'];
		}
		
		if (isset($customer['first_name'])) {
			$post['name'] = $customer['first_name'] . ' ' . $customer['last_name'];
		}
		
		$post['paymentaction'] = 'sale';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['amt'] = $amount; 
		$post['invnum'] = $order_id;
		$post['currencycode'] = $gateway['currency'];
		
		$response = $this->Process($post_url, $post);
		
		if (!empty($response['TOKEN'])) {
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['TOKEN']);
			
			// generate express checkout URL
			$url = $this->GetExpressCheckoutURL($gateway);
			
			$url .= '&token=' . $response['TOKEN'];
			
			$response_array = array(
							'not_completed' => TRUE, // don't mark charge as complete
							'redirect' => $url, // redirect the user to this address
							'charge_id' => $order_id
						);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		else {
			$response_array = array('reason' => $response['L_ERRORCODE0'] . ' - ' . $response['L_LONGMESSAGE0']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;	
	}
	
	function Recur($client_id, $gateway, $customer, $amount, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences, $return_url, $cancel_url)
	{		
		$CI =& get_instance();
		
		$CI =& get_instance();
		$CI->load->model('charge_data_model');
		$CI->load->helper('url');
		$CI->load->model('client_model');
		
		$client = $CI->client_model->GetClient($client_id,$client_id);
		
		// save the return URL
		$CI->charge_data_model->Save('r' . $subscription_id, 'return_url', $return_url);
		
		$post_url = $this->GetAPIURL($gateway);
		
		$post = array();
		$post['version'] = '56.0';
		$post['method'] = 'SetExpressCheckout';
		$post['returnurl'] = site_url('callback/paypal/confirm_recur/' . $subscription_id);
		$post['cancelurl'] = (!empty($cancel_url)) ? $cancel_url : 'http://www.paypal.com';
		$post['noshipping'] = '0';
		$post['allownote'] = '0';
		$post['localecode'] = $client['country'];
		$post['solutiontype'] = 'Mark';
		$post['landingpage'] = 'Billing';
		$post['channeltype'] = 'Merchant';
		
		if (isset($customer['email'])) {
			$post['email'] = $customer['email'];
		}
		
		if (isset($customer['first_name'])) {
			$post['name'] = $customer['first_name'] . ' ' . $customer['last_name'];
		}
		
		$post['paymentaction'] = 'sale';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['amt'] = $amount; 
		$post['invnum'] = $subscription_id;
		$post['currencycode'] = $gateway['currency'];
		$post['L_BILLINGTYPE1'] = 'RecurringPayments';
		$description = $gateway['currency'] . $amount . ' every ' . $interval . ' days until ' . $end_date;
		if ($start_date != date('Y-m-d')) {
			$description .= ' (free trial ends ' . $start_date . ')';
		}
		$post['L_BILLINGAGREEMENTDESCRIPTION1'] = $description;
				
		$response = $this->Process($post_url, $post);
		
		if (!empty($response['TOKEN'])) {
			// generate express checkout URL
			$url = $this->GetExpressCheckoutURL($gateway);
			
			$url .= '&token=' . $response['TOKEN'];
			
			$response_array = array(
							'not_completed' => TRUE, // don't mark charge as complete
							'redirect' => $url, // redirect the user to this address
							'subscription_id' => $subscription_id
						);
			$response = $CI->response->TransactionResponse(100, $response_array);
		}
		else {
			$response_array = array('reason' => $response['L_ERRORCODE0'] . ' - ' . $response['L_LONGMESSAGE0']);
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
	
	function Process($url, $post_data)
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
		
		$response = $this->response_to_array($response);
		
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
		
		if($failed_payments < 1) {		
			$response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = "The charge has failed.";
		}
		
		return $response;	
	}
	
	function Callback_confirm ($client_id, $gateway, $charge, $params) {
		$CI =& get_instance();
		
		$url = $this->GetAPIUrl($gateway);
	
		$post = array();
		$post['method'] = 'GetExpressCheckoutDetails';
		$post['token'] = $params['token'];
		$post['version'] = '56.0';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
				
		$response = $this->Process($url, $post);
		
		if (isset($response['TOKEN']) and $response['TOKEN'] == $params['token']) {
			// we're good
						
			// complete the payment
			$post = $response; // most of the data is from here
			unset($post['NOTE']);
			
			$post['METHOD'] = 'DoExpressCheckoutPayment';
			$post['TOKEN'] = $response['TOKEN'];
			$post['PAYMENTACTION'] = 'Sale';
			$post['version'] = '56.0';
			$post['user'] = $gateway['user'];
			$post['pwd'] = $gateway['pwd'];
			$post['signature'] = $gateway['signature'];
			
			$response = $this->Process($url, $post);
			
			if ($response['PAYMENTSTATUS'] == 'Completed' or $response['PAYMENTSTATUS'] == 'Pending' or $response['PAYMENTSTATUS'] == 'Processed') {
				// we're good
				
				// save authorization (transaction id #)
				$CI->load->model('order_authorization_model');
				$CI->order_authorization_model->SaveAuthorization($charge['id'], $response['TRANSACTIONID']);
				
				$CI->charge_model->SetStatus($charge['id'], 1);
				TriggerTrip('charge', $client_id, $charge['id']);
				
				// get return URL from original OpenGateway request
				$CI->load->model('charge_data_model');
				$data = $CI->charge_data_model->Get($charge['id']);
				
				// redirect back to user's site
				header('Location: ' . $data['return_url']);
				die();
			}
		}
	}
	
	function Callback_recur ($client_id, $gateway, $subscription, $params) {
		$CI =& get_instance();
		
		$url = $this->GetAPIUrl($gateway);
	
		$post = array();
		$post['method'] = 'GetExpressCheckoutDetails';
		$post['token'] = $params['token'];
		$post['version'] = '56.0';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
				
		$response = $this->Process($url, $post);
		
		if (isset($response['TOKEN']) and $response['TOKEN'] == $params['token']) {
			// we're good			
			
			// continue with creating payment profile
			$post = $response; // most of the data is from here
			unset($post['NOTE']);
			
			$post['METHOD'] = 'CreateRecurringPaymentsProfile';
			$post['VERSION'] = '60';
			$post['user'] = $gateway['user'];
			$post['pwd'] = $gateway['pwd'];
			$post['signature'] = $gateway['signature'];
			$post['TOKEN'] = $response['TOKEN'];
			$description = $gateway['currency'] . $amount . ' every ' . $subscription['interval'] . ' days until ' . $subscription['end_date'];
			if ($subscription['start_date'] != date('Y-m-d')) {
				$description .= ' (free trial ends ' . $subscription['start_date'] . ')';
			}
			$post['DESC'] = $description;
			$post['PROFILESTARTDATE'] = date('c',time($subscription['start_date']));
			$post['BILLINGPERIOD'] = 'Day';
			$post['BILLINGFREQUENCY'] = $subscription['interval'];
			$post['AMT'] = $subscription['amount'];
			
			$response_sub = $this->Process($url, $post);
			
			if (isset($response_sub['PROFILEID'])) {
				// success!
				
				$CI->recurring_model->SaveApiCustomerReference($subscription['id'], $response_sub['PROFILEID']);
				
				if (date('Y-m-d',strtotime($subscription['start_date'])) == date('Y-m-d')) {
					// create today's order
					$CI->load->model('charge_model');
					
					$customer_id = (isset($subscription['customer']['id'])) ? $subscription['customer']['id'] : FALSE;
					$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $subscription['amount'], array(), $subscription['id'], $customer_id);
					
					// complete the payment
					$post = $response; // most of the data is from here
					unset($post['NOTE']);
					
					$post['METHOD'] = 'DoExpressCheckoutPayment';
					$post['TOKEN'] = $response['TOKEN'];
					$post['PAYMENTACTION'] = 'Sale';
					$post['version'] = '56.0';
					$post['user'] = $gateway['user'];
					$post['pwd'] = $gateway['pwd'];
					$post['signature'] = $gateway['signature'];
					
					$response_charge = $this->Process($url, $post);
					
					if ($response_charge['PAYMENTSTATUS'] == 'Completed' or $response_charge['PAYMENTSTATUS'] == 'Pending' or $response_charge['PAYMENTSTATUS'] == 'Processed') {
						$CI->load->model('order_authorization_model');
						$CI->order_authorization_model->SaveAuthorization($order_id, $response_charge['TRANSACTIONID']);
						
						$CI->charge_model->SetStatus($order_id, 1);
					}
					else {
						header('Failed to make initial charge.  Please contact support.');
						die();
					}
				}
				
				// we're all done now - finally!
				
				$order_id = (isset($order_id)) ? $order_id : FALSE;
			
				$CI->recurring_model->SetActive($client_id, $subscription['id']);
				
				// trip it - were golden!
				TriggerTrip('new_recurring', $client_id, $order_id, $subscription['id']);
				
				// trip a recurring charge?
				if ($order_id) {
					TriggerTrip('recurring_charge', $client_id, $order_id, $subscription['id']);
				}
				
				// get return URL from original OpenGateway request
				$CI->load->model('charge_data_model');
				$data = $CI->charge_data_model->Get('r' . $subscription['id']);
				
				// redirect back to user's site
				header('Location: ' . $data['return_url']);
				die();
			}
		}
	}
	
	private function GetAPIURL ($gateway) {
		if ($gateway['mode'] == 'test') {
			return $gateway['url_test'];
		}
		else {
			return $gateway['url_live'];
		}
	}
	
	private function GetExpressCheckoutURL ($gateway) {
		if ($gateway['mode'] == 'test') {
			return $gateway['arb_url_test'];
		}
		else {
			return $gateway['arb_url_live'];
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

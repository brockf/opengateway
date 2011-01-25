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
		$settings['no_credit_card'] = TRUE;
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
																		'JPY' => 'Japanese Yen',
																		'NOK' => 'Norwegian Krones'
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
		$post['noshipping'] = '1';
		$post['addroverride'] = '1';
		$post['allownote'] = '0';
		$post['localecode'] = $client['country'];
		$post['solutiontype'] = 'Sole';
		$post['landingpage'] = 'Billing';
		$post['channeltype'] = 'Merchant';
		
		if (isset($customer['email'])) {
			$post['email'] = $customer['email'];
		}
		
		if (isset($customer['first_name'])) {
			$post['name'] = $customer['first_name'] . ' ' . $customer['last_name'];
		}
		
		if (isset($customer['address_1']) and !empty($customer['address_1'])) {
			$post['SHIPTONAME'] = $customer['first_name'] . ' ' . $customer['last_name'];
			$post['SHIPTOSTREET'] = $customer['address_1'];
			$post['SHIPTOSTREET2'] = $customer['address_2'];
			$post['SHIPTOCITY'] = $customer['city'];
			$post['SHIPTOSTATE'] = $customer['state'];
			$post['SHIPTOZIP'] = $customer['postal_code'];
			$post['SHIPTOCOUNTRYCODE'] = $customer['country'];
			$post['SHIPTOPHONENUM'] = $customer['phone'];
		}
			
		$post['paymentaction'] = 'sale';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['AMT'] = $amount; 
		$post['L_DESC1'] = 'Invoice #' . $order_id;
		$post['L_AMT1'] = $amount;
		$post['L_QTY1'] = '1';
		$post['ITEMAMT'] = $amount;
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
	
	function Recur($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences, $return_url, $cancel_url)
	{		
		$CI =& get_instance();
		
		$CI =& get_instance();
		$CI->load->model('charge_data_model');
		$CI->load->helper('url');
		$CI->load->model('client_model');
		
		$amount = money_format("%i",$amount);
		
		$client = $CI->client_model->GetClient($client_id,$client_id);
		
		// save the return URL
		$CI->charge_data_model->Save('r' . $subscription_id, 'return_url', $return_url);
		
		// save the initial charge amount (it may be different, so we treat it as a separate first charge)
		$CI->charge_data_model->Save('r' . $subscription_id, 'first_charge', $amount);
		
		$post_url = $this->GetAPIURL($gateway);
		
		$post = array();
		$post['version'] = '56.0';
		$post['method'] = 'SetExpressCheckout';
		$post['returnurl'] = site_url('callback/paypal/confirm_recur/' . $subscription_id);
		$post['cancelurl'] = (!empty($cancel_url)) ? $cancel_url : 'http://www.paypal.com';
		$post['noshipping'] = '1';
		$post['addroverride'] = '1';
		$post['allownote'] = '0';
		$post['localecode'] = $client['country'];
		$post['solutiontype'] = 'Sole';
		$post['landingpage'] = 'Billing';
		$post['channeltype'] = 'Merchant';
		
		if (isset($customer['email'])) {
			$post['email'] = $customer['email'];
		}
		
		if (isset($customer['first_name'])) {
			$post['name'] = $customer['first_name'] . ' ' . $customer['last_name'];
		}
		
		$post['PAYMENTACTION'] = 'sale';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['AMT'] = $amount; 
		$post['invnum'] = $subscription_id;
		$post['currencycode'] = $gateway['currency'];
		$post['L_BILLINGTYPE0'] = 'RecurringPayments';
		$post['L_DESC0'] = 'Recurring payment';
		$post['L_AMT0'] = $amount;
		$post['L_QTY0'] = '1';
		$post['ITEMAMT'] = $amount;
		
		if (isset($customer['address_1']) and !empty($customer['address_1'])) {
			$post['SHIPTONAME'] = $customer['first_name'] . ' ' . $customer['last_name'];
			$post['SHIPTOSTREET'] = $customer['address_1'];
			$post['SHIPTOSTREET2'] = $customer['address_2'];
			$post['SHIPTOCITY'] = $customer['city'];
			$post['SHIPTOSTATE'] = $customer['state'];
			$post['SHIPTOZIP'] = $customer['postal_code'];
			$post['SHIPTOCOUNTRYCODE'] = $customer['country'];
			$post['SHIPTOPHONENUM'] = $customer['phone'];
		}
		
		// handle first charges unless there's a free trial
		if ($charge_today === TRUE) {
			// first recurring charge won't start until after the first interval
			// we'll run an instant payment first
			// old start date
			$adjusted_start_date = TRUE;
			$start_date = date('Y-m-d',strtotime($start_date)+(60*60*24*$interval));
		}
		
		// get true recurring rate, first
		$subscription = $CI->recurring_model->GetRecurring($client_id, $subscription_id);
		
		$description = ($subscription['amount'] != $amount) ? 'Initial charge: ' . $gateway['currency'] . $amount . ', then ' : '';
		$description .= $gateway['currency'] . money_format("%!i",$subscription['amount']) . ' every ' . $interval . ' days until ' . date('Y-m-d',strtotime($subscription['end_date']));
		if ($charge_today === FALSE) {
			$description .= ' (free trial ends ' . $start_date . ')';
		}
		$post['L_BILLINGAGREEMENTDESCRIPTION0'] = $description;
		
		$CI->charge_data_model->Save('r' . $subscription_id, 'profile_description', $description);
					
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
	
	function Process($url, $post_data)
	{
		$CI =& get_instance();
		
		$data = '';

		// Build the data string for the request body
		foreach($post_data as $key => $value)
		{
			if(!empty($value))
			{
				$data .= strtoupper($key) . '=' . urlencode(trim($value)) . '&';
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
		
		$post_url = $this->GetAPIURL($gateway);
		
		$post = array();
		$post['version'] = '60';
		$post['method'] = 'ManageRecurringPaymentsProfileStatus';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['profileid'] = $subscription['api_customer_reference'];
		$post['action'] = 'Cancel';
		
		$post_response = $this->Process($post_url, $post);
		
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
		
		$post_url = $this->GetAPIURL($gateway);
		
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
		$status = $details['STATUS'];
		$failed_payments = $details['FAILEDPAYMENTCOUNT'];
		
		if($failed_payments < 1 and $status != 'Cancelled') {		
			$response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = "The charge has failed.";
		}
		
		return $response;	
	}
	
	function Callback_confirm ($client_id, $gateway, $charge, $params) {
		$CI =& get_instance();
		
		$url = $this->GetAPIURL($gateway);
	
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
	
	function Callback_confirm_recur ($client_id, $gateway, $subscription, $params) {
		$CI =& get_instance();
		
		$CI->load->model('charge_data_model');
		$data = $CI->charge_data_model->Get('r' . $subscription['id']);
		
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
			
			// do we need a first charge?
			if (date('Y-m-d',strtotime($subscription['start_date'])) == date('Y-m-d', strtotime($subscription['date_created']))) {
				$CI->load->model('charge_model');
				
				// get the first charge amount (it may be different)
				$first_charge_amount = (isset($data['first_charge'])) ? $data['first_charge'] : $subscription['amount'];
				
				$customer_id = (isset($subscription['customer']['id'])) ? $subscription['customer']['id'] : FALSE;
				$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $first_charge_amount, array(), $subscription['id'], $customer_id);
				
				// yes, the first charge is today
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
				
				//die(print_r($response_charge));
				
				if (!isset($response_charge) or $response_charge['PAYMENTSTATUS'] != 'Completed' and $response_charge['PAYMENTSTATUS'] != 'Pending' and $response_charge['PAYMENTSTATUS'] != 'Processed') {
					die('Your initial PayPal payment failed.  <a href="' . $data['cancel_url'] . '">Go back to merchant</a>.');
				}
				else {
					// create today's order
					// we assume it's good because the profile is OK
					
					$CI->load->model('order_authorization_model');
					
					// we may not have the transaction ID if it's Pending
					$response_charge['TRANSACTIONID'] = (isset($response_charge['TRANSACTIONID'])) ? $response_charge['TRANSACTIONID'] : 'pending_payment';
					$CI->order_authorization_model->SaveAuthorization($order_id, $response_charge['TRANSACTIONID']);
					
					$CI->charge_model->SetStatus($order_id, 1);
				}
				
				// we'll also adjust the profile start date
				$adjusted_start_date = TRUE;
				$subscription['start_date'] = date('Y-m-d',strtotime($subscription['start_date'])+(60*60*24*$subscription['interval']));
			}		
			
			// continue with creating payment profile
			$post = $response; // most of the data is from here
			unset($post['NOTE']);
			
			$post['METHOD'] = 'CreateRecurringPaymentsProfile';
			$post['VERSION'] = '60';
			$post['user'] = $gateway['user'];
			$post['pwd'] = $gateway['pwd'];
			$post['signature'] = $gateway['signature'];
			$post['TOKEN'] = $response['TOKEN'];
			$post['DESC'] = $data['profile_description'];
			$post['PROFILESTARTDATE'] = date('c',strtotime($subscription['start_date']));
			$post['BILLINGPERIOD'] = 'Day';
			$post['BILLINGFREQUENCY'] = $subscription['interval'];
			$post['AMT'] = $subscription['amount'];
			
			$response_sub = $this->Process($url, $post);
			
			if (isset($response_sub['PROFILEID'])) {
				// success!
				
				$CI->recurring_model->SaveApiCustomerReference($subscription['id'], $response_sub['PROFILEID']);
				
				$order_id = (isset($order_id)) ? $order_id : FALSE;
			
				$CI->recurring_model->SetActive($client_id, $subscription['id']);
				
				// trip it - were golden!
				TriggerTrip('new_recurring', $client_id, $order_id, $subscription['id']);
				
				// trip a recurring charge?
				if ($order_id) {
					TriggerTrip('recurring_charge', $client_id, $order_id, $subscription['id']);
				}
				
				// redirect back to user's site
				header('Location: ' . $data['return_url']);
				die();
			}
			else {
				die('Error completing payment (subscription profile error).');
			}
		}
	}
	
	function GetProfileDetails($client_id, $gateway, $params)
	{
		$CI =& get_instance();
		$CI->load->model('recurring_model');
		
		$post_url = $this->GetAPIURL($gateway);
		
		$post = array();
		$post['version'] = '60';
		$post['method'] = 'GetRecurringPaymentsProfileDetails';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['profileid'] = $params['api_customer_reference'];
		
		$post_response = $this->Process($post_url, $post);
		
		if ($post_response['ACK'] == 'Success') {
			return $post_response;
		} else {
			return FALSE;
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

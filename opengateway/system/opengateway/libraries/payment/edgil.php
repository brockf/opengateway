<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');


class edgil
{
	var $settings;
	
	function edgil() {
		$this->settings = $this->Settings();
		
		$CI =& get_instance();
	}
	
	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'Edgil (Java) Gateway';
		$settings['class_name'] = 'edgil';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'Use Edgil\'s EdgCapture system to accept payments online.  This gateway requires that your server can execute the Java binaries and may take some manual tweaking and configuration due to its complexity.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$99.00';
		$settings['monthly_fee'] = '$40.00';
		$settings['transaction_fee'] = '$0.10';
		$settings['purchase_link'] = 'http://www.edgil.com';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 1;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'url',
										'port',
										'login_id',
										'password',
										'merchant_id',
										'oep_id',
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
																		'test' => 'Test Mode',
																		'dev' => 'Development Server'
																		)
														),
										'url' => array(
														'text' => 'Edgil URL',
														'type' => 'text'
														),
										'port' => array(
														'text' => 'Edgil Port',
														'type' => 'text'
														),
										'login_id' => array(
														'text' => 'Login ID',
														'type' => 'text'
														),
										'password' => array(
														'text' => 'Password',
														'type' => 'password'
														),
										'merchant_id' => array(
														'text' => 'Merchant ID',
														'type' => 'text'
														),
										'oep_id' => array(
														'text' => 'OEP ID',
														'type' => 'text'
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
	
	function loadObject($gateway)
	{
		$CI =& get_instance();
		
		$this->object_loaded = TRUE;

		define("JAVA_HOSTS", $gateway['url'].':'.$gateway['port']);
		if(!(@include_once($CI->config->item('java_include_path')."JavaBridge/java/Java.inc"))) {
			require_once($CI->config->item('java_include_path')."JavaBridge/java/Java.inc");
		}

		java_require($CI->config->item('java_include_path')."java/lib");
		
		$this->ECCOClient = new Java("com.edgil.ecco.eccoapi.ECCOClient", "PHPTestECCO",$CI->config->item('java_include_path')."java/ecco/data/ECCO.properties");
		// define ECCOStatusCodes object for ECCO Constants...
		$this->ECCOStatusCodes = new Java("com.edgil.ecco.eccoapi.ECCOStatusCodes");
		
		$this->pass = array("C","h","a","n","g","e","I","t");
		$this->alias = array("e","d","g","i","l","c","a");
	}
	
	function TestConnection($client_id, $gateway) 
	{
		$this->loadObject($gateway);
		
		$this->ECCOClient->certifyECCO($this->pass, $this->pass, $this->alias, "ECCO");
		
		$status = $this->ECCOClient->logon($gateway['login_id'],$gateway['password']);
		
		if($status == $this->ECCOStatusCodes->SUCCESS){
			return TRUE;
		} else {
			return FALSE;
		}
		
		return $response;
	}
	
	function GetEdgilToken ($customer_id, $order_id, $is_recurring = FALSE)
	{
		$CI->load->model('charge_data_model');
		
		$token_name = 'customer_' . $customer_id;
		if ($is_recurring == TRUE) {
			$token_name .= '_recur_' . $order_id;
		}
		else {
			$token_name .= '_charge_' . $order_id;
		}
		
		$data = $CI->charge_data_model->Get($token_name);
		
		if (empty($data)) {
			return FALSE;
		}
		else {
			return $data['token'];
		}
	}
	
	function SetEdgilToken($client_id, $customer, $credit_card, $gateway, $order_id, $is_recurring = FALSE)
	{
		$CI =& get_instance();

		$this->loadObject($gateway);
		$data = new Java("com.edgil.ecco.eccoapi.CardholderData");
		
		// build customer data
		$customer_id = $customer['customer_id'];
		
		// can we put an address on Edgil's records?
		if (!empty($customer['address_1']) and !empty($customer['city']) and !empty($customer['state']) and !empty($customer['postal_code'])) {
			$address = $customer['address_1'];
			if (isset($customer['address_2']) and !empty($customer['address_2'])) {
				$address .= ' '.$customer['address_2'];
			}
			
			$data->setAddress($address, $customer['city'], $customer['state'], $customer['postal_code']);
		}
		
		// set name
		$data->setName($customer['first_name'], $customer['last_name']);
		
		$data->setAccountNumber($credit_card['card_num']);
		$data->setExpirationDate($credit_card['exp_month'], $credit_card['exp_year']);
		
		$this->ECCOClient->certifyECCO($this->pass, $this->pass, $this->alias, "ECCO");
		$this->ECCOClient->logon($gateway['login_id'],$gateway['password']);
		
		$status = $this->ECCOClient->requestCreateToken($data, FALSE);
		
		if ($status == $this->ECCOStatusCodes->SUCCESS)
		{
			$token = $data->getToken();
			
			$CI->load->model('charge_data_model');
			
			$token_name = 'customer_' . $customer_id;
			if ($is_recurring == TRUE) {
				$token_name .= '_recur_' . $order_id;
			}
			else {
				$token_name .= '_charge_' . $order_id;
			}
			
			$CI->charge_data_model->Save($token_name, 'token', $token);
			
			$return = $token;
		}
		else
		{
			$return = FALSE;
		}
		
		$this->ECCOClient->logoff();
		
		return $return;
	}
	
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{	
		$CI =& get_instance();
	
		$token = $this->SetEdgilToken($client_id,$customer,$credit_card,$gateway,$order_id,FALSE);
		
		if (!isset($this->object_loaded)) {
			$this->loadObject($gateway);
		}

		$tran = new Java("com.edgil.ecco.eccoapi.MonetaryTransactionData");

		$tran->setMerchantId($gateway['merchant_id']);
		$tran->setOEPId($gateway['oep_id']);

		$this->ECCOClient->certifyECCO($this->pass, $this->pass, $this->alias, "ECCO");
		
		$status = $this->ECCOClient->logon($gateway['login_id'],$gateway['password']);
		
		if ($status != $this->ECCOStatusCodes->SUCCESS)
		{
			$response = $CI->response->TransactionResponse(2, array('reason' => 'Login Error'));
			return $response;
		}
		
		$tran->setToken($token);
		$tran->setTransactionId($order_id);
		$tran->setAmount($amount);
		
		$status = $this->ECCOClient->requestAuthorization($tran);

		if($status == $this->ECCOStatusCodes->SUCCESS)
		{
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id,'', "'".$tran->getAuthorizationCode()."'");
			$CI->charge_model->SetStatus($order_id, 1);
			
			$status = $this->ECCOClient->requestMarkForCapture($tran);
			
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		else
		{
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		$this->ECCOClient->logoff();
		
		return $response;
	}
	
	function Recur ($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{		
		$CI =& get_instance();
		
		$token = $this->SetEdgilToken($client_id, $customer, $credit_card, $gateway, $subscription_id, TRUE);
		
		// save the api_customer_reference
		$CI->load->model('recurring_model');
		$CI->recurring_model->SaveApiCustomerReference($subscription_id, $token);
		
		// If a payment is to be made today, process it.
		if ($charge_today === TRUE) {
			// Create an order for today's payment
			$CI->load->model('charge_model');
			$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);
			
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $token, $amount);
			
			if($response['success'] == TRUE){
				$CI->charge_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->recurring_model->MakeInactive($subscription_id);
				
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	function Refund ($client_id, $gateway, $charge, $authorization)
	{	
		$CI =& get_instance();
		
		$token = $this->GetEdgilToken($charge['customer']['id'], $charge['id'], FALSE);
	
		if (empty($token)) {
			$response_array = array('reason' => 'Could not get customer token.');
			$response = $CI->response->TransactionResponse(2, $response_array);
			return $response;
		}
		
		if (!isset($this->object_loaded)) {
			$this->loadObject($gateway);
		}

		$tran = new Java("com.edgil.ecco.eccoapi.MonetaryTransactionData");
		
		$tran->setToken($token);
		$tran->setAmount($charge['amount']);
		
		$status = $this->ECCOClient->requestCredit($tran);
		
		if($status == $this->ECCOStatusCodes->SUCCESS)
		{
			$response =  TRUE;
		}
		else
		{
			$response = FALSE;
		}
		
		$this->ECCOClient->logoff();
		
		return $response;	
	}
	
	function ChargeRecurring($client_id, $gateway, $order_id, $token, $amount)
	{	
		$CI =& get_instance();
	
		if (empty($token)) {
			$response['reason'] = 'Token not passed to ChargeRecurring.';
			$response['success'] = FALSE;
			return $response;
		}
		
		if (!isset($this->object_loaded)) {
			$this->loadObject($gateway);
		}

		$tran = new Java("com.edgil.ecco.eccoapi.MonetaryTransactionData");

		$tran->setMerchantId($gateway['merchant_id']);
		$tran->setOEPId($gateway['oep_id']);

		$this->ECCOClient->certifyECCO($this->pass, $this->pass, $this->alias, "ECCO");
		
		$status = $this->ECCOClient->logon($gateway['login_id'],$gateway['password']);
		
		if ($status != $this->ECCOStatusCodes->SUCCESS)
		{
			$response = $CI->response->TransactionResponse(2, array('reason' => 'Login Error'));
			return $response;
		}
		
		$tran->setToken($token);
		$tran->setTransactionId($order_id);
		$tran->setAmount($amount);
		
		$status = $this->ECCOClient->requestAuthorization($tran);

		if($status == $this->ECCOStatusCodes->SUCCESS)
		{
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id,'', "'".$tran->getAuthorizationCode()."'");
			$CI->charge_model->SetStatus($order_id, 1);
			
			$status = $this->ECCOClient->requestMarkForCapture($tran);
			
			$response['success'] = TRUE;
		}
		else
		{
			$response['success'] = FALSE;
		}
		
		$this->ECCOClient->logoff();
		
		return $response;
	}
	
	
	function CancelRecurring($client_id, $subscription)
	{	
		return TRUE;
	}
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		$token = $this->GetEdgilToken($params['customer_id'], $params['subscription_id'], TRUE);
		
		return $this->ChargeRecurring($client_id, $gateway, $order_id, $token, $params['amount']);
	}
	
	function UpdateRecurring()
	{
		return TRUE;
	}
	
	
}
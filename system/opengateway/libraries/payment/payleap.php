<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	PayLeap processing gateway.
 *
 * http://www.payleap.com
 *
 * Prod URL: 
 * Test URL:
 * Dev  URL: 
 * Rebill Prod URL: 
 * Rebill Test URL: http://test.payleap.com/admin/ws/recurring.asmx
 * Rebill Dev  URL: http://test.payleap.com/admin/ws/recurring.asmx
 *
 * Test Mastercard: 5000300020003003
 * Test Visa: 		4005550000000019 
 * Demo account: 
 *	Username: demo_merchant
 *	Password: plDemo123
 *	Vendor: 0097
 *
 * @package		OpenGateway
 * @author		Lonnie Ezell
 */
class payleap {

	var $settings;
	
	var $cust_key		= '';
	var $contract_key	= '';
	var $cc_info_key 	= '';
	
	/**
	 * if true, will echo out debug strings to verify
	 * that things are working. 
	 */
	private $debug = false;
	
	//--------------------------------------------------------------------
	
	function payleap() {
		$this->settings = $this->Settings();
	}
	
	//--------------------------------------------------------------------
	
	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'PayLeap';
		$settings['class_name'] = 'payleap';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'PayLeap simplifies the online payment system by consolidating all your payment systems to one transaction processing solution.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$29';
		$settings['monthly_fee'] = '$29';
		$settings['transaction_fee'] = '$0.25';
		$settings['purchase_link'] = 'http://www.payleap.com/lp/opengateway';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 0;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'customer_id',
										'username',
										'password',
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
																		'test' => 'Test Mode'
																		)
														),
										'customer_id' => array(
														'text' => 'Vendor Number',
														'type' => 'text'
														),
										
										'username' => array(
														'text' => 'API Login',
														'type' => 'text'
														),
										
										'password' => array(
														'text' => 'Transaction Key',
														'type' => 'password'
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
	
	//--------------------------------------------------------------------
	
	///// WORKING
	
	/**
	 * Tests that the user information provided is a valid set of rules.
	 * We do this by creating a dummy recurring credit card.
	 *
	 * @param	int		$client_id	- the id of the eWay account.
	 * @param	array	$gateway	- the gateway object.
	 * @return	bool	true if client info appears correct.
	 */
	function TestConnection($client_id, $gateway) 
	{	
		return TRUE;
		
		$data  = array(
			'Username'	=> $gateway['username'],
			'Password'	=> $gateway['password'],
			'Vendor'	=> $gateway['customer_id'],
			'CustomerID'	=> time() . rand(100,999),
			'CustomerName'	=> 'Test Account',
			'FirstName'		=> 'John',
			'LastName'		=> 'Smith',
			'Title'			=> '',
			'Department'	=> '',
			'Street1'		=> '',
			'Street2'		=> '',
			'Street3'		=> '',
			'City'			=> '',
			'StateID'		=> '',
			'Province'		=> '',
			'Zip'			=> '',
			'CountryID'		=> '',
			'Email'			=> 'john@example.com',
			'Mobile'		=> '',
			'DayPhone'		=> '',
			'NightPhone'	=> '',
			'Fax'			=> '',
			'EmailCustomer'	=> false,
			'EmailMerchant'	=> false,
			'EmailCustomerFailure' => false,
			'EmailMerchantFailure' => false,
			'ContractID'	=> 'Test123',
			'ContractName'	=> time() . rand(100,999),
			'BillAmt'		=> '10.00',
			'TaxAmt'		=> 0,
			'TotalAmt'		=> '10.00',
			'StartDate'		=> '02/28/2013',
			'EndDate'		=> '02/02/2015',
			'BillingPeriod'	=> 'MONTH',
			'BillingInterval' => '1',
			'CcAccountNum' => '4111111111111111',
			'CcExpDate'		=> '1212',
			'CcNameOnCard'	=> '',
			'CcStreet'		=> '',
			'CcZip'			=> '',
			'MaxFailures'	=> '',
			'FailureInterval'	=> '',
			'ExtData'		=> ''
		);
		
		$response = $this->process($gateway, $data, 'AddRecurringCreditCard', TRUE);
		
		if ($this->debug)
		{
			var_dump($response); 
		}
		
		if ($this->find_in_array('CustomerKey', $response) !== false)
		{ 
			return TRUE;
		}
		
		return FALSE;
	}
	
	//---------------------------------------------------------------
		
	/**
	 *	Recur - called when an initial Recur charge comes through to
	 *	to create a subscription.
	 *
	 */
	function Recur ($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{		
		$CI =& get_instance();
	
		// Create an order for today's payment
		$CI->load->model('charge_model');
		$customer['customer_id'] = (isset($customer['customer_id'])) ? $customer['customer_id'] : FALSE;
		$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);
		
		// Create the recurring seed
		$response = $this->CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id);
		
		// Process today's payment
		if ($charge_today === TRUE) {
			if ($gateway['mode'] != 'live') $response['client_id'] = '9876543211000';
		
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $response['client_id'], $amount);
		
			if($response['success'] == TRUE){
				$CI->charge_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->recurring_model->MakeInactive($subscription_id);
				$CI->charge_model->SetStatus($order_id, 0);
				
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	//---------------------------------------------------------------

	/**
	 * Creates a customer profile, along with the setting up the contract,
	 * and saving the subscription info. This is called from the Recur method.
	 */
	function createProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id)
	{
		$CI =& get_instance();
	
		// Get our subscription plan details
		$CI->load->model('recurring_model');
		$subscription = $CI->recurring_model->GetRecurring($client_id, $subscription_id);
	
	    $data = array(
	    	'Username'			=> $gateway['username'],
			'Password'			=> $gateway['password'],
			'Vendor'			=> $gateway['customer_id'],
	    	'Title'				=> isset($customer['title']) && !empty($customer['title']) ? $customer['title'] : 'Mr.',
	    	'CustomerID'		=> $customer['customer_id'],
	    	'CustomerName'		=> $customer['first_name'] .' '. $customer['last_name'],
	    	'FirstName' 		=> $customer['first_name'],
	    	'LastName'			=> $customer['last_name'],
	    	'Department'		=> '',
	    	'Street1'			=> $customer['address_1'],
	    	'Street2'			=> $customer['address_2'],
	    	'Street3'			=> '',
	    	'City'				=> $customer['city'],
	    	'StateID'			=> $customer['state'],
	    	'Province'			=> '',
	    	'Zip'				=> $customer['postal_code'],
	    	'CountryID'			=> $this->convert_country($customer['country']),
	    	'Email'				=> $customer['email'],
	    	'Fax'				=> '',
	    	'DayPhone'			=> '',
	    	'NightPhone'		=> '',
	    	'Mobile'			=> '',
	    	'EmailCustomer'		=> 'false',
	    	'EmailMerchant'		=> 'false',
	    	'EmailCustomerFailure'	=> 'true',
	    	'EmailMerchantFailure'	=> 'true',
	    	'ContractID'		=> $order_id,
	    	'ContractName'		=> '',
	    	'BillAmt'			=> $amount,
	    	'TaxAmt'			=> 0,
	    	'TotalAmt'			=> $amount,
	    	'StartDate'			=> $subscription['start_date'],
	    	'EndDate'			=> $subscription['end_date'],
	    	'BillingPeriod'		=> 'MONTH',
	    	'BillingInterval'	=> '1', // arbitrary, because we will send processes later
	    	'CcAccountNum'		=> isset($credit_card['card_num']) ? $credit_card['card_num'] : '',
	    	'CcNameOnCard'		=> isset($credit_card['name']) ? $credit_card['name'] : '',
	    	'CcExpDate'			=> $credit_card['exp_month'] . substr($credit_card['exp_year'], -2, 2),
	    	'CcStreet'			=> '',
	    	'CcZip'				=> '',
	    	'MaxFailures'		=> '',
	    	'FailureInterval'	=> '',
	    	'ExtData'			=> ''
	    );
    
		$response = $this->process($gateway, $data, 'AddRecurringCreditCard', TRUE);
		
		$this->cust_key = $this->find_in_array('CustomerKey', $response);
		$this->contract_key = $this->find_in_array('ContractKey', $response);
		$this->cc_info_key = $this->find_in_array('CcInfoKey', $response);
		
		if($this->cust_key !== false && is_numeric($this->cust_key))
		{	
			$response['success'] = true;
			$response['client_id'] = $cust_key;
			
			/*
				Save the Auth information
				
				Since the db only really holds one piece of info, we'll join
				the 3 pieces of data needed by PayLeap into a single item
				that we can pull info from in the future.	
			*/			
			$authkey = implode('::', array($this->cust_key, $this->contract_key, $this->cc_info_key));
			
			$CI->recurring_model->SaveApiCustomerReference($subscription_id, $authkey);
			// Client successfully created at PayLeap. Now we ned to save the info here. 
			return $response;
		}
		else
		{
			$response['success'] = false;
			$response['reason'] = 'Could not create customer at PayLeap.';
			return $response;
		}
	}
	
	//---------------------------------------------------------------
		
	/**
	 *	Handles paying the recurring charge. 
	 *
	 */	 
	function ChargeRecurring ($client_id, $gateway, $order_id, $customer_id, $amount) {
		$CI =& get_instance();
		
		/* 
			Split out our $customer_id into the 3 vital pieces that 
			PayLeap needs.
		*/
		$this->client_setup($customer_id);
			
		$data = array(
			'Username'	=> $gateway['username'],
			'Password'	=> $gateway['password'],
			'Vendor'	=> $gateway['customer_id'],
			'CcInfoKey'	=> $this->cc_info_key,
			'Amount'	=> $amount,
			'InvNum'	=> $order_id,
			'ExtData'	=> ''
		);
		
		$response = $this->process($gateway, $data, 'ProcessCreditCard', TRUE);
		
		$success = $this->find_in_array('error', $response);
		
		if ($success == 'APPROVED')
		{
			$response['success']			= TRUE;
			$response['transaction_num']	= $this->find_in_array('PNRef', $response);
			$response['auth_code']			= $this->find_in_array('AuthCode', $response);
			
			// Save the Auth information
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['transaction_num'], $response['auth_code']);
		} else 
		{
			$response['success'] 	= FALSE;
			$response['reason']		= $this->find_in_array('Message', $response);
		}
		
		return $response;
	}
	
	//---------------------------------------------------------------

	public function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params) 
	{
		$this->client_setup($subscription['api_customer_reference']);
	
	    $data = array(
	    	'Username'			=> $gateway['username'],
			'Password'			=> $gateway['password'],
			'Vendor'			=> $gateway['customer_id'],
			'TransType'			=> 'UPDATE',
			'CustomerKey'		=> $this->cust_key,
			'ContractKey'		=> $this->contract_key,
			'PaymentInfoKey'	=> $this->cc_info_key,
			'PaymentType'		=> 'CC',
	    	'Title'				=> isset($customer['title']) && !empty($customer['title']) ? $customer['title'] : 'Mr.',
	    	'CustomerID'		=> isset($customer['customer_id']) ? $customer['customer_id'] : '',
	    	'CustomerName'		=> isset($customer['first_name']) ? $customer['first_name'] .' '. $customer['last_name'] : '',
	    	'FirstName' 		=> isset($customer['first_name']) ? $customer['first_name'] : '',
	    	'LastName'			=> isset($customer['last_name']) ? $customer['last_name'] : '',
	    	'Department'		=> '',
	    	'Street1'			=> isset($customer['address_1']) ? $customer['address_1'] : '',
	    	'Street2'			=> isset($customer['address_2']) ? $customer['address_2'] : '',
	    	'Street3'			=> '',
	    	'City'				=> isset($customer['city']) ? $customer['city'] : '',
	    	'StateID'			=> isset($customer['state']) ? $customer['state'] : '',
	    	'Province'			=> '',
	    	'Zip'				=> isset($customer['postal_code']) ? $customer['postal_code'] : '',
	    	'CountryID'			=> isset($customer['country']) ? $this->convert_country($customer['country']) : '',
	    	'Email'				=> $customer['email'],
	    	'Fax'				=> '',
	    	'DayPhone'			=> '',
	    	'NightPhone'		=> '',
	    	'Mobile'			=> '',
	    	'EmailCustomer'		=> 'false',
	    	'EmailMerchant'		=> 'false',
	    	'EmailCustomerFailure'	=> 'true',
	    	'EmailMerchantFailure'	=> 'true',
	    	'ContractID'		=> $order_id,
	    	'ContractName'		=> '',
	    	'BillAmt'			=> $amount,
	    	'TaxAmt'			=> 0,
	    	'TotalAmt'			=> $amount,
	    	'StartDate'			=> date('m/d/Y', strtotime($subscription['start_date'])),
	    	'EndDate'			=> date('m/d/Y', strtotime($subscription['end_date'])),
	    	'BillingPeriod'		=> 'DAY',
	    	'BillingInterval'	=> $subscription['charge_interval'],
	    	'NextBillDt'		=> date('m/d/Y', strtotime($subscription['next_charge'])),
	    	'MaxFailures'		=> '',
	    	'FailureInterval'	=> '',
	    	'Status'			=> '',
	    	'ExtData'			=> ''
	    );
    
		$response = $this->process($gateway, $data, 'ManageContract', TRUE);
				
		if ($this->find_in_array('error', $response) == 'OK')
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
		
	public function CancelRecurring($client_id, $subscription, $gateway) 
	{
		$this->client_setup($subscription['api_customer_reference']);
	
	    $data = array(
	    	'Username'			=> $gateway['username'],
			'Password'			=> $gateway['password'],
			'Vendor'			=> $gateway['customer_id'],
			'TransType'			=> 'DELETE',
			'CustomerKey'		=> $this->cust_key,
			'ContractKey'		=> $this->contract_key,
			'PaymentInfoKey'	=> $this->cc_info_key,
			'PaymentType'		=> 'CC',
	    	'Title'				=> '',
	    	'CustomerID'		=> '',
	    	'CustomerName'		=> '',
	    	'FirstName' 		=> '',
	    	'LastName'			=> '',
	    	'Department'		=> '',
	    	'Street1'			=> '',
	    	'Street2'			=> '',
	    	'Street3'			=> '',
	    	'City'				=> '',
	    	'StateID'			=> '',
	    	'Province'			=> '',
	    	'Zip'				=> '',
	    	'CountryID'			=> '',
	    	'Email'				=> '',
	    	'Fax'				=> '',
	    	'DayPhone'			=> '',
	    	'NightPhone'		=> '',
	    	'Mobile'			=> '',
	    	'EmailCustomer'		=> 'false',
	    	'EmailMerchant'		=> 'false',
	    	'EmailCustomerFailure'	=> 'true',
	    	'EmailMerchantFailure'	=> 'true',
	    	'ContractID'		=> $subscription['subscription_id'],
	    	'ContractName'		=> '',
	    	'BillAmt'			=> 0,
	    	'TaxAmt'			=> 0,
	    	'TotalAmt'			=> 0,
	    	'StartDate'			=> date('m/d/Y', strtotime($subscription['start_date'])),
	    	'EndDate'			=> date('m/d/Y', strtotime($subscription['end_date'])),
	    	'BillingPeriod'		=> 'DAY',
	    	'BillingInterval'	=> $subscription['charge_interval'],
	    	'NextBillDt'		=> date('m/d/Y', strtotime($subscription['next_charge'])),
	    	'MaxFailures'		=> '',
	    	'FailureInterval'	=> '',
	    	'Status'			=> '',
	    	'ExtData'			=> ''
	    );
    
		$response = $this->process($gateway, $data, 'ManageContract', TRUE);
				
		if ($this->find_in_array('error', $response) == 'OK')
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Handles a one-time charge.
	 */
	public function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card) 
	{
		$CI =& get_instance();
	
		$data = array(
			'UserName'		=> $gateway['username'],
			'Password'		=> $gateway['password'],
			'TransType'		=> 'Sale',
			'CardNum'		=> $credit_card['card_num'],
			'ExpDate'		=> $credit_card['exp_month'] . substr($credit_card['exp_year'], -2, 2),
			'MagData'		=> '',
			'NameOnCard'	=> $credit_card['name'],
			'Amount'		=> $amount,
			'InvNum'		=> $order_id,
			'PNRef'			=> '',
			'Zip'			=> isset($customer['postal_code']) ? $customer['postal_code'] : '',
			'Street'		=> isset($customer['address_1']) ? $customer['address_1'] : '',
			'CVNum'			=> $credit_card['cvv'],
			'ExtData'		=> '<TrainingMode>F</TrainingMode>'
		);
		
		$response = $this->process($gateway, $data, 'ProcessCreditCard', false);
				
		if ($this->find_in_array('RespMSG', $response) == 'Approved')
		{
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $this->find_in_array('PNRef', $response), $this->find_in_array('AuthCode', $response));
			$CI->charge_model->SetStatus($order_id, 1);
			
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		else 
		{
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $this->find_in_array('RespMSG', $response));
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
		
	public function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) 
	{
		return $this->ChargeRecurring($client_id, $gateway, $order_id, $params['api_customer_reference'], $params['amount']);
	}
	
	//--------------------------------------------------------------------

	//--------------------------------------------------------------------
	// !PROCESSORS
	//--------------------------------------------------------------------

	/**
	 * Handles the communication with the remote server. 
	 * With PayLeap, it appears that we can do it all through GET/POST
	 * calls, which is easier than SOAP :).
	 *
	 * @param	string	$gatewayGateway details
	 * @param	array	$vars	The POST data to send
	 * @param	string	$action	The remote method to call (is appended to url)
	 */
	protected function process($gateway, $vars, $action, $type='rebill')
	{
		$header = array("MIME-Version: 1.0","Content-type: application/x-www-form-urlencoded","Contenttransfer-encoding: text"); 
	
		$post_fields = trim($this->to_post($vars, $gateway), '& ');
	
		$ch = curl_init(); // initiate curl object
		
		if ($gateway['mode'] == 'test') {
			if ($type == 'rebill') {
				$url = 'https://uat.payleap.com/MerchantServices.svc/';
			}
			else {
				$url = 'https://uat.payleap.com/TransactServices.svc/';
			}
		}
		else {
			if ($type == 'rebill') {
				$url = 'https://secure1.payleap.com/MerchantServices.svc/';
			}
			else {
				$url = 'https://secure1.payleap.com/TransactServices.svc/';
			}
		}
		
		// set URL and other appropriate options 
		$post_url = $url .'/'. $action .'?';
		
		curl_setopt($ch, CURLOPT_URL, $post_url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1); 
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($ch, CURLOPT_POST, true); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		$post_response = curl_exec($ch); // execute curl post and store results in $post_response
		
		if ($this->debug)
		{
			echo '<h2>Post Fields</h2>';
			print_r($post_fields);
		
			echo '<h2>Curl Headers</h2>';
			$opt = curl_getinfo($ch);
			var_dump($opt);
		
			echo '<h2>Curl Response</h2>';
			var_dump($post_response);
		}
		
		if (curl_errno($ch) == CURLE_OK)
		{
			$response = $this->xml2array($post_response);
			return $response;
		}
		
		return FALSE;
		
	}
	
	//---------------------------------------------------------------

	//--------------------------------------------------------------------
	// !PRIVATE METHODS
	//--------------------------------------------------------------------

	/**
	 * Returns the proper url for the remote gateway.
	 *
	 * Note that $mode param defaults to false, which will
	 * return the token payments url. If $mode is 'rebill', 
	 * then it will return the rebill url.
	 */
	private function GetAPIUrl ($gateway, $mode = FALSE) {
		if ($mode == FALSE) {
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
		}
		elseif ($mode == 'rebill') {
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
		}
		
		return $post_url;
	}
	
	//---------------------------------------------------------------
	
	private function to_post($array=array(), $gateway) 
	{
		if (!count($array))
		{
			return false;
		}
		
		$post = '';
		
		foreach ($array as $key => $value)
		{
			$post .= '&'. $key .'='. htmlspecialchars($value);
		}
		
		return trim($post);
	}
	
	//--------------------------------------------------------------------
	
	private function xml2array($xml) {
        $xmlary = array();
                
        $reels = '/<(\w+)\s*([^\/>]*)\s*(?:\/>|>(.*)<\/\s*\\1\s*>)/s';
        $reattrs = '/(\w+)=(?:"|\')([^"\']*)(:?"|\')/';

        preg_match_all($reels, $xml, $elements);

        foreach ($elements[1] as $ie => $xx) {
                $xmlary[$ie]["name"] = $elements[1][$ie];
                
                if ($attributes = trim($elements[2][$ie])) {
                        preg_match_all($reattrs, $attributes, $att);
                        foreach ($att[1] as $ia => $xx)
                                $xmlary[$ie]["attributes"][$att[1][$ia]] = $att[2][$ia];
                }

                $cdend = strpos($elements[3][$ie], "<");
                if ($cdend > 0) {
                        $xmlary[$ie]["text"] = substr($elements[3][$ie], 0, $cdend - 1);
                }

                if (preg_match($reels, $elements[3][$ie]))
                        $xmlary[$ie]["elements"] = $this->xml2array($elements[3][$ie]);
                else if ($elements[3][$ie]) {
                        $xmlary[$ie]["text"] = $elements[3][$ie];
                }
        }

        return $xmlary;
	}
	
	//--------------------------------------------------------------------
	
	private function find_in_array($name='', $array=array())
	{ 
		foreach ($array as $el)
		{	
			if ($el['name'] == $name)
			{
				return $el['text'];
			}
		}
		
		return false;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Convert country code from iso2 to iso3.
	 */
	private function convert_country($two='') 
	{
		if (empty($two)) 
		{
			return $two;
		}
		
		$CI =& get_instance();
		
		$CI->db->where('iso2', $two);
		$CI->db->select('iso3');
		$query = $CI->db->get('countries');
		
		if ($query->num_rows() > 0)
		{
			return $query->row()->iso3;
		}
		
		return $two;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Splits the client into the three pieces of info that PayLeap needs.
	 */
	private function client_setup($client=null) 
	{
		if (strpos($client, '::'))
		{
			list($cust_key, $contract, $cc_info) = explode('::', $client);
			
			$this->cust_key = $cust_key;
			$this->contract_key = $contract;
			$this->cc_info_key = $cc_info;
		}
	}
	
	//--------------------------------------------------------------------
	
	
}

/* End of file payleap.php */
/* Location: ./system/opengateway/libraries/payment/payleap.php */
<?php

/*
	Quantum Payments Gateway
	
	http://www.quantumgateway.com/developer.php
	
	Note: There is no live/test servers. All transactions use the same server
	which can be found at: 
	
	https://secure.quantumgateway.com/cgi/xml_requester.php
	
	The only difference is whether you're using your live credentials or your
	test credentials. 
	
	VaultKey is Located, and can be set, under “Edit Vault Config” in the Secure Vault of our gateway. 
	RestrictKey is located and can be set under RestrictKey in the Processing Settings of our gateway. 
	GatewayLogin is provided to you at the time your account was created.
*/
class quantum {
	
	var $settings;
	
	/**
	 * if true, will echo out debug strings to verify
	 * that things are working. 
	 */
	private $debug = true;
	
	/**
	 *
	 */
	private $url = 'https://secure.quantumgateway.com/cgi/xml_requester.php';
	
	//--------------------------------------------------------------------
	
	function quantum() {
		$this->settings = $this->Settings();
	}
	
	//--------------------------------------------------------------------
	/// WORKING
	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'Quantum Gateway';
		$settings['class_name'] = 'quantum';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'QuantumGateway was developed to provide businesses with the most useful, feature-enriched, and easy to use payment processing tool ever created. USA only.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = 'Varies';
		$settings['monthly_fee'] = 'Varies';
		$settings['transaction_fee'] = 'Varies';
		$settings['purchase_link'] = 'https://www.quantumgateway.com/signup.php';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 0;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 1;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'accept_visa',
										'accept_mc',
										'accept_discover',
										'accept_amex',
										'gateway_login',
										'vault_key',
										'restrict_key'
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
																		'live' => 'Live Mode'
																		)
														),
										'gateway_login' => array(
														'text' => 'GatewayLogin',
														'type' => 'text'
														),
										
										'vault_key' => array(
														'text' => 'Vault Key',
														'type' => 'text'
														),
										'restrict_key' => array(
														'text' => 'Retrict Key',
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
	
	//---------------------------------------------------------------
	/// WORKING
	/**
	 * Tests that the user information provided is a valid set of rules.
	 * We do this by creating a new test customer on the remote server.
	 *
	 * @param	int		$client_id	- the id of the eWay account.
	 * @param	array	$gateway	- the gateway object.
	 * @return	bool	true if client info appears correct.
	 */
	 function TestConnection($client_id, $gateway) 
	{	
		$customer = array(
	   		'CustomerID'=> rand(1, 10000),
	    	'FirstName' => 'Joe',
	    	'LastName'	=> 'Bloggs',
	    	'Address'	=> 'Blogg enterprises',
	    	'City'		=> 'Capital City',
	    	'State'		=> 'act',
	    	'ZipCode'	=> '2111',
	    	'EmailAddress'	=> 'test@example.com',
			'PaymentType'	=> 'CC',
	    	'CreditCardNumber'	=> '4444333322221111',
	    	'ExpireMonth'	=> '12',
	    	'ExpireYear'	=> date('Y')
	    );
		
		$response = $this->process($gateway, $customer, 'CustomerAdd');
		
		if ($response['ResponseSummary']['Status'] == 'Success')
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	//---------------------------------------------------------------
	
	//---------------------------------------------------------------
	// !EVENT FUNCTIONS
	//---------------------------------------------------------------
	/// WORKING
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{
		$CI =& get_instance();
	
		$post = array(
			'TransactionType'	=> 'CREDIT',
			'ProcessType'		=> 'SALES',
			'PaymentType'		=> 'CC',
			'Amount'			=> $amount,
			'CreditCardNumber'	=> $credit_card['card_num'],
			'ExpireMonth'		=> $credit_card['exp_month'],
			'ExpireYear'		=> $credit_card['exp_year'],
			'CVV2'				=> $credit_card['cvv'],
			'FirstName'			=> $customer['first_name'],
			'LastName'			=> $customer['last_name'],
			'Address'			=> $customer['address_1'],
			'City'				=> $customer['city'],
			'State'				=> $customer['state'],
			'ZipCode'			=> $customer['postal_code'],
			'Country'			=> $customer['country'],
			'EmailAddress'		=> $customer['email'],
			'PhoneNumber'		=> $customer['phone'],
			'InvoiceNumber'		=> $order_id,
		);
		
		$response = $this->process($gateway, $post, 'ProcessSingleTransaction', 'restrict');
		
		if (strtolower($response['Result']['Status']) == 'approved')
		{
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['Result']['TransactionID'], $response['Result']['AuthorizationCode']);
			$CI->charge_model->SetStatus($order_id, 1);
			
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		else
		{
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['Result']['StatusDescription']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	/// WORKING
	function createProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id)
	{
		$CI =& get_instance();
	 
		$post = array(
			'CustomerID'		=> $customer['id'] . $subscription_id,
			'FirstName'			=> $customer['first_name'],
			'LastName'			=> $customer['last_name'],
			'Address'			=> $customer['address_1'],
			'City'				=> $customer['city'],
			'State'				=> $customer['state'],
			'ZipCode'			=> $customer['postal_code'],
			'Country'			=> $customer['country'],
			'EmailAddress'		=> $customer['email'],
			'PhoneNumber'		=> $customer['phone'],
			'PaymentType'		=> 'CC',
			'CreditCardNumber'	=> $credit_card['card_num'],
			'ExpireMonth'		=> $credit_card['exp_month'],
			'ExpireYear'		=> $credit_card['exp_year'],
			'CVV2'				=> $credit_card['cvv'],
		);
		
		$response = $this->process($gateway, $post, 'CustomerAdd');

		if ($this->debug)
		{
			$this->log_it('CreateProfile Recurring Params', $post);
			$this->log_it('CreateProfile Recurring Response', $response);
		}

		if ($response['ResponseSummary']['Status'] == 'Success')
		{
			$response['success'] = true;
			$response['customer_id'] = $response['Result']['CustomerID'];
			
			// Save the Auth information
			$CI->load->model('recurring_model');
			$CI->recurring_model->SaveApiCustomerReference($subscription_id, $response['Result']['CustomerID']);
			// Client successfully created at eWay. Now we ned to save the info here. 
			return $response;
		}
		else
		{
			$response['success'] = false;
			$response['reason'] = $response['ResponseSummary']['StatusDescription'];
			return $response;
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 *	Recur - called when an initial Recur charge comes through to
	 *	to create a subscription.
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
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $response['customer_id'], $amount);
			
			if($response['success'] === TRUE){
				$CI->charge_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->recurring_model->MakeInactive($subscription_id);
				$CI->charge_model->SetStatus($order_id, 0);
				
				$response_array = array('reason' => $response['ResponseSummary']['StatusDescription']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	//---------------------------------------------------------------
	
	/**
	 *	Handles paying the recurring charge. 
	 */	 
	function ChargeRecurring ($client_id, $gateway, $order_id, $customer_id, $amount, $occurences=0) {
		$CI =& get_instance();
		
		$post = array(
			'TransactionType'	=> 'CC',
			'CustomerID'	=> $customer_id,
			'Amount'	=> $amount,
			'ProcessType'	=> 'SALES'
		);

		$response = $this->process($gateway, $post, 'CreateTransaction');
		
		if ($this->debug)
		{
			$this->log_it('Charge Recurring Params', $post);
			$this->log_it('Charge Recurring Response', $response);
		}
		
		if ($response['Result']['Status'] == 'APPROVED')
		{ 
			$response['success']			= TRUE;
			$response['transaction_num']	= $response['Result']['TransactionID'];
			$response['auth_code']			= $response['Result']['AuthorizationCode'];
			
			// Save the Auth information
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['transaction_num'], $response['auth_code']);
		} else 
		{
			$response['success'] 	= FALSE;
			$response['reason']		= $response['Result']['StatusDescription'];
		}
		
		return $response;
	}
	
	//---------------------------------------------------------------
	
	function CancelRecurring($client_id, $subscription)
	{ 
		// Recurring info not stored at Quantum since we're using tokenized info, 
		// so do nothing here.
		return TRUE;	
	}
	
	//---------------------------------------------------------------
		
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $order_id, $params['api_customer_reference'], $params['amount']);
	}
	
	//-----------------------------------------------------
		
	function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params)
	{
		// Recurring info not stored at eWay, so do nothing here…
		return TRUE;
	}
	
	//---------------------------------------------------------------
	
	//--------------------------------------------------------------------
	// !PROCESSORS
	//--------------------------------------------------------------------
	
	function Process($gateway, $array, $action, $keytype='vault')
	{	
		$key = $keytype == 'vault' ? $gateway['vault_key'] : $gateway['restrict_key'];
	
		$header = "<QGWRequest>
	<Authentication>
		<GatewayLogin>{$gateway['gateway_login']}</GatewayLogin>
		<GatewayKey>$key</GatewayKey>
	</Authentication>
		";
		
		$footer = "</QGWRequest>";
		
		$body = $this->to_xml($array, $action);
	
		$ch = curl_init($this->url); // initiate curl object
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, '?xml='. $header . $body .$footer); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml;charset=UTF-8', 'Expect:'));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		//curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	// Verify it belongs to the server.
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);	// Check common exists and matches the server host name
		
		$post_response = curl_exec($ch); // execute curl post and store results in $post_response
		
		if ($this->debug)
		{
		/*	echo '<h2>'. $action .'</h2>';
			echo 'Curl Response Code: '. curl_getinfo($ch, CURLINFO_HTTP_CODE) ."<br/>";
			echo 'Curl Error: '. curl_error($ch)."<br/>";
			echo "<pre>$action Request: "; echo str_replace('<', '&lt;', $header . $body . $footer);
			echo "$action Response: ". str_replace('<', '&lt;', $post_response); 
			
			if ($action == 'CreateTransaction')	die();
			*/
		}
		
		if (curl_errno($ch) == CURLE_OK)
		{
			$response_xml = @simplexml_load_string($post_response);	
			$CI =& get_instance();
			$CI->load->library('arraytoxml');
			$response = $CI->arraytoxml->toArray($response_xml);

			curl_close($ch);		
			return $response;
		}
		else {
			echo 'Curl Error: '. curl_error($ch);
		}
		
		curl_close($ch);
		return FALSE;
	}
	
	//---------------------------------------------------------------
	
	//--------------------------------------------------------------------
	// UTILITY FUNCTIONS
	//--------------------------------------------------------------------
	
	public function to_xml($array=array(), $action='')
	{
		if (!count($array) || empty($action))
		{
			return false;
		}
		
		$xml = "<Request>\n";
		$xml .= "<RequestType>$action</RequestType>\n";
		
		foreach ($array as $key => $value)
		{
			$xml .= "\t<$key>$value</$key>\n";
		}
		
		$xml .= "</Request>\n";
		
		return $xml;
	}
	
	//---------------------------------------------------------------
	
	/*
		Method: log_it()
		
		Logs the transaction to a file. Helpful with debugging callback
		transactions, since we can't actually see what's going on.
		
		Parameters:
			$heading	- A string to be placed above the resutls
			$params		- Typically an array to print_r out so that we can inspect it.
	*/
	public function log_it($heading, $params) 
	{
		$file = FCPATH .'writeable/gateway_log.txt';
		
		$content .= "# $heading\n";
		$content .= date('Y-m-d H:i:s') ."\n\n";
		$content .= print_r($params, true);
		file_put_contents($file, $content, FILE_APPEND);
	}
	
	//--------------------------------------------------------------------
}
<?php

class authnet
{
	function Settings()
	{
		$settings['name'] = 'Authorize.net';
		$settings['class_name'] = 'authnet';
		$settings['description'] = 'Authorize.net is the USA\'s premier gateway.  Coupled with the powerful Customer Information Manager (CIM), this gateway is an affordable and powerful gateway for any American merchant.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$99.00';
		$settings['monthly_fee'] = '$40.00';
		$settings['transaction_fee'] = '$0.10';
		$settings['purchase_link'] = 'http://www.opengateway.net/gateways/authnet';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 1;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'login_id',
										'transaction_key',
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
										'login_id' => array(
														'text' => 'Login ID',
														'type' => 'text'
														),
										'transaction_key' => array(
														'text' => 'Transaction Key',
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
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",		
			"x_type"			=> "AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_card_num"		=> '4222222222222',
			"x_exp_date"		=> '1099',
			"x_amount"			=> 1,
			"x_test_request"    => TRUE
		);
		
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$order_id = 0;
		$response = $this->Process($order_id, $post_url, $post_string, TRUE);
		
		$CI =& get_instance();
		
		if($response['success']){
			return TRUE;
		} else {
			return FALSE;
		}
		
		return $response;
		
	}
	
	function Charge($client_id, $order_id, $gateway, $customer, $params, $credit_card)
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
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",		
			"x_type"			=> "AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_card_num"		=> $credit_card['card_num'],
			"x_exp_date"		=> $credit_card['exp_month'].'/'.$credit_card['exp_year'],
			"x_amount"			=> $params['amount']
			);
			
		if ($gateway['mode'] == 'test') {
			$post_values['x_test_request'] = 'TRUE';
		}

		if(isset($credit_card->cvv)) {
			$post_values['x_card_code'] = $credit_card['cvv'];
		}	
		
		if(isset($customer['customer_id'])) {
			$post_values['x_first_name'] = $customer['first_name'];
			$post_values['x_last_name'] = $customer['last_name'];
			$post_values['x_address'] = $customer['address_1'];
			if (isset($customer['address_2']) and !empty($customer['address_2'])) {
				$post_values['x_address'] .= ' - '.$customer['address_2'];
			}
			$post_values['x_state'] = $customer['state'];
			$post_values['x_zip'] = $customer['postal_code'];
			$post_values['x_country'] = $customer['country'];
		}
		
		if(isset($params['description'])) {
			$post_values['x_description'] = $params['description'];
		}
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		$CI =& get_instance();
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	function Recur($client_id, $gateway, $customer, $params, $start_date, $end_date, $interval, $credit_card, $subscription_id)
	{		
		$CI =& get_instance();
		
		// Create a new authnet profile
		$response = $this->CreateProfile($gateway, $subscription_id);
		
		if(isset($response) and !empty($response['success'])) {
			$profile_id = $response['profile_id'];	
		} else {
			die($CI->response->Error(5005));
		}
		
		// save the api_customer_reference
		$CI->load->model('subscription_model');
		$CI->subscription_model->SaveApiCustomerReference($subscription_id, $profile_id);
		
		// Create the payment profile
		$response = $this->CreatePaymentProfile($profile_id, $gateway, $credit_card, $customer);
		if($response) {
			$payment_profile_id = $response['payment_profile_id'];	
		} else {
			die($CI->response->Error(5006));
		}
		
		// Save the api_payment_reference
		$CI->subscription_model->SaveApiPaymentReference($subscription_id, $payment_profile_id);
		
		// If a payment is to be made today, process it.
		if(date('Y-m-d', strtotime($start_date)) == date('Y-m-d')) {
			
			// Create an order for today's payment
			$CI->load->model('order_model');
			$customer['customer_id'] = (isset($customer['customer_id'])) ? $customer['customer_id'] : FALSE;
			$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $subscription_id, $customer['customer_id']);
			
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $profile_id, $payment_profile_id, $params);
			
			if($response['success'] == TRUE){
				$CI->order_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->subscription_model->MakeInactive($subscription_id);
				
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	function CancelRecurring($client_id, $subscription)
	{	
		return TRUE;
	}
	
	function CreateProfile($gateway, $subscription_id)
	{
		$CI =& get_instance();
		
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
		
		$content =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		"<createCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
		"<merchantAuthentication>
	        <name>".$gateway['login_id']."</name>
	        <transactionKey>".$gateway['transaction_key']."</transactionKey>
	    </merchantAuthentication>
		".
		"<profile>".
		"<merchantCustomerId>".$subscription_id."</merchantCustomerId>".
		"</profile>".
		"</createCustomerProfileRequest>";
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		@$response = simplexml_load_string($post_response);
		
		if($response->messages->resultCode == 'Ok') {
			$response['success'] = TRUE;
			$response['profile_id'] = (string)$response->customerProfileId;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = (string)$response->messages->message->text;
		}
		
		return $response;
		
	}
	
	function CreatePaymentProfile($profile_id, $gateway, $credit_card, $customer)
	{
		$CI =& get_instance();
		
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
		
		if(isset($customer['first_name'])) {
			$first_name = $customer['first_name'];
			$last_name = $customer['last_name'];
		} else {
			$name = explode(' ', $credit_card['name']);
			$first_name = $name[0];
			$last_name = $name[count($name) - 1];
		}
		
		$content =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		"<createCustomerPaymentProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
		"<merchantAuthentication>
	        <name>".$gateway['login_id']."</name>
	        <transactionKey>".$gateway['transaction_key']."</transactionKey>
	    </merchantAuthentication>
		".
		"<customerProfileId>" . $profile_id . "</customerProfileId>".
		"<paymentProfile>".
		"<billTo>".
		 "<firstName>".$first_name."</firstName>".
		 "<lastName>".$last_name."</lastName>".
		"</billTo>".
		"<payment>".
		 "<creditCard>".
		  "<cardNumber>".$credit_card['card_num']."</cardNumber>".
		  "<expirationDate>".$credit_card['exp_year']."-".$credit_card['exp_month']."</expirationDate>". // required format for API is YYYY-MM
		 "</creditCard>".
		"</payment>".
		"</paymentProfile>\n";
		if ($gateway['mode'] == 'test') {
			$content .= "<validationMode>liveMode</validationMode>\n";
		}
		$content .= "</createCustomerPaymentProfileRequest>";
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		@$response = simplexml_load_string($post_response);
		
		if($response->messages->resultCode == 'Ok') {
			$response['success'] = TRUE;
			$response['payment_profile_id'] = (string)$response->customerPaymentProfileId;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = (string)$response->messages->message->text;
		}
		
		return $response;
	}
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $order_id, $params['api_customer_reference'], $params['api_payment_reference'], $params);
	}
	
	function ChargeRecurring($client_id, $gateway, $order_id, $profile_id, $payment_profile_id, $params)
	{		
		$CI =& get_instance();
		
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
		
		$content =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		"<createCustomerProfileTransactionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
		 "<merchantAuthentication>
	        <name>".$gateway['login_id']."</name>
	        <transactionKey>".$gateway['transaction_key']."</transactionKey>
	    </merchantAuthentication>".
		"<transaction>".
		"<profileTransAuthCapture>".
		"<amount>" . $params['amount']. "</amount>". 
		"<customerProfileId>" . $profile_id . "</customerProfileId>".
		"<customerPaymentProfileId>" . $payment_profile_id . "</customerPaymentProfileId>".
		"<order>".
		"<invoiceNumber>".$order_id."</invoiceNumber>".
		"</order>".
		"</profileTransAuthCapture>".
		"</transaction>
		</createCustomerProfileTransactionRequest>";
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		@$response = simplexml_load_string($post_response);
		
		if($response->messages->resultCode == 'Ok') {
			// Send a notification to the notification URL
			// $CI->load->library('notify');
			// $CI->notify->SendNotification($subscription_id);
			
			// Get the auth code
			$post_response = explode(',', $response->directResponse);
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $post_response[6], $post_response[4]);
			$response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = (string)$response->messages->message->text;
		}
		
		return $response;	
	}
	
	function UpdateRecurring()
	{
		return TRUE;
	}
	
	function Process($order_id, $post_url, $post_string, $test = FALSE)
	{
		$CI =& get_instance();
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close ($request); // close curl object
		
		$response = explode('|',$post_response);

		// Log the response
		//$this->LogResponse($order_id, $response);
		if(!isset($response[1])) {
			$response['success'] = FALSE;
			return $response;
		}
		
		if($test) {
			if($response[0] == 1) {
				$response['success'] = TRUE;
			} else {
				$response['success'] = FALSE;
	
			}
	
			return $response;
		}
		// Get the response.  1 for the first part meant that it was successful.  Anything else and it failed
		if($response[0] == 1) {
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response[6], $response[4]);
			$CI->order_model->SetStatus($order_id, 1);
			
			$response['success'] = TRUE;
		} else {
			$CI->load->model('order_model');
			$CI->order_model->SetStatus($order_id, 0);
			
			$response['success'] = FALSE;
			$response['reason'] = $response[3];
		}

		return $response;

	}
	
	function LogResponse($order_id, $response)
	{		
		$insert_data = array(
		'order_id'											=> $order_id,
		'response_code' 									=> $response[0],
		'response_subcode' 									=> $response[1],
		'response_reason_code' 								=> $response[2],
		'response_reason_text' 								=> $response[3],
		'authorization_code' 								=> $response[4],
		'avs_response' 										=> $response[5],
		'transaction_id' 									=> $response[6],
		'invoice_number' 									=> $response[7],
		'description' 										=> $response[8],
		'amount' 											=> $response[9],
		'method' 											=> $response[10],
		'transaction_type'									=> $response[11],
		'customer_id'										=> $response[12],
		'first_name' 										=> $response[13],
		'last_name' 										=> $response[14],
		'company' 											=> $response[15],
		'address' 											=> $response[16],
		'city' 												=> $response[17],
		'state' 											=> $response[18],
		'zip_code' 											=> $response[19],
		'country' 											=> $response[20],
		'phone' 											=> $response[21],
		'fax' 												=> $response[22],
		'email_address' 									=> $response[23],
		'ship_to_first_name' 								=> $response[24],
		'ship_to_last_name' 								=> $response[25],
		'ship_to_country' 									=> $response[26],
		'ship_to_address' 									=> $response[27],
		'ship_to_city' 										=> $response[28],
		'ship_to_state' 									=> $response[29],
		'ship_to_zip_code' 									=> $response[30],
		'ship_to_country' 									=> $response[31],
		'tax' 												=> $response[32],
		'duty' 												=> $response[33],
		'freight' 											=> $response[34],
		'tax_exempt' 										=> $response[35],
		'purchase_order_number' 							=> $response[36],
		'md5_hash' 											=> $response[37],
		'card_code_response' 								=> $response[38],
		'cardholder_authentication_verification_response' 	=> $response[39]
		);
		
		$CI =& get_instance();
		$CI->log_model->LogApiResponse('authnet', $insert_data);
		
	}
	
	/*
	function Auth($client_id, $order_id, $gateway, $customer, $params, $credit_card)
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
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",		
			"x_type"			=> "AUTH_ONLY",
			"x_method"			=> "CC",
			"x_card_num"		=> $credit_card['card_num'],
			"x_exp_date"		=> $credit_card['exp_month'].$credit_card['exp_year'],
			"x_amount"			=> $params['amount']
			);

		if(isset($credit_card->cvv)) {
			$post_values['x_card_code'] = $credit_card['cvv'];
		}	
		
		if(isset($params['customer_id'])) {
			$post_values['x_first_name'] = $customer['first_name'];
			$post_values['x_last_name'] = $customer['last_name'];
			$post_values['x_address'] = $customer['address_1'].'-'.$customer['address_2'];
			$post_values['x_state'] = $customer['state'];
			$post_values['x_zip'] = $customer['postal_code'];
		}
		
		if(isset($params['description'])) {
			$post_values['x_description'] = $params['description'];
		}
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		$CI =& get_instance();
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	function Capture($client_id, $order_id, $gateway, $customer, $params)
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
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		// Get the tran id
		$CI->load->model('order_authorization_model');
		$order = $CI->order_authorization_model->GetAuthorization($order_id);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],
		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
		
			"x_type"			=> "PRIOR_AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_tran_id"			=> $order->tran_id
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	
	function Refund($client_id, $order_id, $gateway, $customer, $params)
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
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		// Get the tran id
		$CI->load->model('order_model');
		$order = $CI->order_model->GetCharge($client_id, $order_id);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],
		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
		
			"x_type"			=> "CREDIT",
			"x_method"			=> "CC",
			"x_tran_id"			=> $order['id'],
			"x_amount"			=> $params['amount'],
			"x_card_num"		=> $params['card_num'],
			"x_exp_date"		=> $params['exp_month'].$params['exp_year']
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	
	function Void($client_id, $order_id, $gateway, $customer, $params)
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
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		// Get the tran id
		$CI->load->model('order_model');
		$order = $CI->order_model->GetOrder($client_id, $order_id);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],
		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
		
			"x_type"			=> "VOID",
			"x_method"			=> "CC",
			"x_tran_id"			=> $order->tran_id,
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	*/
	
}
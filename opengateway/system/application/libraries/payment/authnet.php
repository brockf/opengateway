<?php
class authnet
{
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
			"x_card_num"		=> $credit_card->card_num,
			"x_exp_date"		=> $credit_card->exp_month.$credit_card->exp_year,
			"x_amount"			=> $params['amount']
			);

		if(isset($credit_card->cvv)) {
			$post_values['x_card_code'] = $credit_card->cvv;
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
			$response_array = array('order_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}

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
			"x_card_num"		=> $credit_card->card_num,
			"x_exp_date"		=> $credit_card->exp_month.$credit_card->exp_year,
			"x_amount"			=> $params['amount']
			);

		if(isset($credit_card->cvv)) {
			$post_values['x_card_code'] = $credit_card->cvv;
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
			$response_array = array('order_id' => $order_id);
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
			$response_array = array('order_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	
	function Credit($client_id, $order_id, $gateway, $customer, $params)
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
		
			"x_type"			=> "CREDIT",
			"x_method"			=> "CC",
			"x_tran_id"			=> $order->tran_id,
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
			$response_array = array('order_id' => $order_id);
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
			$response_array = array('order_id' => $order_id);
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
		if($response) {
			$profile_id = $response['profile_id'];	
		} else {
			die($CI->response->Error(5005));
		}
		
		// save the api_customer_reference
		$CI->load->model('subscription_model');
		$CI->subscription_model->SaveApiCustomerReference($subscription_id, $profile_id);
		
		// Create the payment profile
		$response = $this->CreatePaymentProfile($profile_id, $gateway, $credit_card);
		if($response) {
			$payment_profile_id = $response['payment_profile_id'];	
		} else {
			die($CI->response->Error(5006));
		}
		
		// Save the api_payment_reference
		$CI->subscription_model->SaveApiPaymentReference($subscription_id, $payment_profile_id);
		
		// Create an order for today's payment
		$CI->load->model('order_model');
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $credit_card, $subscription_id);
		
		// Process today's payment
		$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $profile_id, $payment_profile_id, $params);
		
		if($response['success'] == TRUE){
			$response_array = array('order_id' => $order_id, 'subscription_id' => $subscription_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			// Make the subscription inactive
			$CI->subscription_model->MakeInactive($subscription_id);
			
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
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
	
	function CreatePaymentProfile($profile_id, $gateway, $credit_card)
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
			$name = explode(' ', (string)$credit_card->name);
			$first_name = $name[0];
			$last_name = $name[1];
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
		  "<cardNumber>".(string)$credit_card->card_num."</cardNumber>".
		  "<expirationDate>".(string)$credit_card->exp_year."-".(string)$credit_card->exp_month."</expirationDate>". // required format for API is YYYY-MM
		 "</creditCard>".
		"</payment>".
		"</paymentProfile>".
		"<validationMode>testMode</validationMode>". // or testMode
		"</createCustomerPaymentProfileRequest>";
		
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
		"</transaction>".
		"</createCustomerProfileTransactionRequest>";
		
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
			$post_response = explode(',', $response->DirectResponse);
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $post_response[6], $post_response[4]);
			$response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = (string)$response->messages->message->text;
		}
		
		return $response;
		
	}
	
	
	
	function Process($order_id, $post_url, $post_string)
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
		$this->LogResponse($order_id, $response);
		
		// Get the response.  1 for the first part meant that it was successful.  Anything else and it failed
		if($response[0] == 1) {
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response[6], $response[4]);
			
			$response['success'] = TRUE;
		} else {
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
	
}
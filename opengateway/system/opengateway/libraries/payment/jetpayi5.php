<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
	Gateway Name
	
	Developer Portal: http://...
	
	Describe the gateway, list the API's used, as well as any testing information
	that will help in the future.
*/

class jetpayi5 {
	
	protected $settings;
	
	/*
		Can be used to display debugging information.
		To be used by the developer during the creation
		and testing process.
	*/
	private $debug = false;
	
	private $url	= 'http://jetgate.jetpayi5.com/';
	
	//--------------------------------------------------------------------
	
	public function __construct() 
	{
		$this->settings = $this->Settings();
	}
	
	//--------------------------------------------------------------------
	
	/*
		
	*/
	public function Settings() 
	{
		$settings = array();
		
		$settings['name'] = 'JetPayi5';
		$settings['class_name'] = 'jetpayi5';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = TRUE;
		$settings['description'] = 'JetPayi5 provides a full-featured interface to the JetPay payment gateway.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = 'Varies';
		$settings['monthly_fee'] = 'Varies';
		$settings['transaction_fee'] = 'Varies';
		$settings['purchase_link'] = 'http://jetpayi5.com/approval.htm';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 1;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 1;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'accept_visa',
										'accept_mc',
										'accept_discover',
										'accept_amex',
										'merchcomp',
										'accessid',
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
																		'live'	=> 'Live Mode',
																		'test'	=> 'Testing',
																		'dev'	=> 'Developer'
																		)
														),
										'merchcomp' => array(
														'text' => 'Merchant Company #',
														'type' => 'text'
														),
										
										'accessid' => array(
														'text' => 'Access ID',
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
	
	//--------------------------------------------------------------------
	
	/*
		Method: TestConnection()
		
		Tests that the user provided gateway information is correct. 
		The method used for this is different for every gateway. PLEASE EDIT THIS LINE.
		
		Parameters:
			$client_id	- the ID of the client in OG.
			$gateway	- An array of gateway information.
			
		Returns:
			True/False
	*/
	public function TestConnection($client_id, $gateway) 
	{
		// All we we need to do here is a Ping to the server
		$data = array();

		$response = $this->Process($gateway, $data, 'PI', 'dotran');
		
		if ($response['ActionCode'] == '000')
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: Charge()
		
		Performs a one-time charge. To be able to do refunds, we must 
		tokenize the customer so that we have credit card info to refund.
		
		Parameters: 
			$client_id		- The ID of the OpenGateway client
			$order_id		- The internal OpenGateway order id.
			$gateway		- An array of gateway information
			$customer		- An array with customer information
			$amount			- The amount of the charge
			$credit_card	- An array of credit card information
			
		Returns:
			$response	- A TransactionResponse object.
	*/
	public function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{ 
		$CI =& get_instance();

		//--------------------------------------------------------------------
		// Step 1: Create the customer profile
		//--------------------------------------------------------------------

		$response = $this->CreateProfile($client_id, $gateway, $customer, $credit_card, null, $amount, $order_id);
		
		if ($response['success'] == false)
		{
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['RVERRMTX']);
			$response = $CI->response->TransactionResponse(2, $response_array);
			
			return $response;
		}
		
		$customer_id = $response['customer_id'];
		
		//--------------------------------------------------------------------
		// Step 2: Add the credit card to their profile
		//--------------------------------------------------------------------
		
		$response = $this->AddCard($customer_id, $credit_card, $gateway);
		
		if ($response['ActionCode'] != '000')
		{
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['RVERRMTX']);
			$response = $CI->response->TransactionResponse(2, $response_array);
			
			return $response;
		}
		
		//--------------------------------------------------------------------
		// Step 3: Charge the card
		//--------------------------------------------------------------------	
		
		$response = $this->DoTran($customer_id, $amount, 'SA', $gateway);
			
		/*
			If the transaction was approved, we save the transaction ID
			and authorization code in the database, update the status so 
			that the user knows it was successful.
			
			At this point the customer id has been saved to the charge_data_model
			as 'CustomerID'.
		*/
		if ($response['ActionCode'] == '000')
		{
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['TransactionID'], $response['Approval']);
			$CI->charge_model->SetStatus($order_id, 1);
			
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		/*
			Otherwise, set the status as failed, and provide a reason why.
		*/
		else
		{
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['RVERRMTX']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	public function DoTran($customer_id, $amount, $transType='SA', $gateway) 
	{
		$data = array(
			'cardName'		=> '*FROMCUST',
			'customer'		=> $customer_id,
			'totalAmount'	=> $amount * 100	// Convert to cents
		);
		
		$response = $this->Process($gateway, $data, $transType, 'dotran');
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	
	/*
		Method: AddCard()
		
		Adds a new card to a JetPayi5 Customer. If the customer already
		has a card, this will overwrite it.
		
		Parameters:
			$customer_id	- An INT(6) with the customer id provided by JetPayi5
			$credit_card	- An array of credit card information
			$gateway		- The gateway array to pass to the Process method.
	*/
	public function AddCard($customer_id, $credit_card, $gateway) 
	{	
		$data = array(
			'customer'		=> $customer_id,
			'cardSeq'		=> 1,
			'cardNum'		=> $credit_card['card_num'],
			'cardExp'		=> $credit_card['exp_month'] . substr($credit_card['exp_year'], -2)
		);
		
		$response = $this->Process($gateway, $data, null, 'addcard');

		return $response;
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: Refund()
		
		Applies a credit to the customer's card.
	*/
	public function Refund ($client_id, $gateway, $charge, $authorization)
	{	
		// We need our customer id
		$CI =& get_instance();
		$CI->load->model('charge_data_model');
		$charge_data = $CI->charge_data_model->Get($charge['id']);
		
		$response = $this->DoTran($charge_data['CustomerID'], $charge['amount'], 'CR', $gateway);
		
		if ($response['ActionCode'] == '000')
		{
			return true;
		}
		
		return false;
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: Recur()
		
		Called when an initial Recur charge comes through to create a subscription.
		 
		Parameters:
			$client_id		- An in with internal client ID.
			$gateway		- An array of gateway information
			$customer		- An array of customer information
			$amount			- A float with the amount to charge
			$charge_today	- Boolean
			$start_date		- The day the subscription should start
			$end_date		- The day the subscription should end
			$interval		- The number of days between subscription charges
			$credit_card	- An array with the credit card information
			$subscription_id	- An int with the internal subscription id
			$total_occurences	- The total number of charges, or FALSE.
	*/
	public function Recur($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE) 
	{
		$CI =& get_instance();
	
		// Create an order for today's payment
		$CI->load->model('charge_model');
		$customer['customer_id'] = (isset($customer['customer_id'])) ? $customer['customer_id'] : FALSE;
		$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);

		//--------------------------------------------------------------------
		// Step 1: Create the Customer Profile
		//--------------------------------------------------------------------
		$response = $this->CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id);
	
		if ($response['success'] === false)
		{
			// Make the subscription inactive
			$CI->recurring_model->MakeInactive($subscription_id);
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['RVERRMTX']);
			$response = $CI->response->TransactionResponse(2, $response_array);
			return $response;
		}
		
		//--------------------------------------------------------------------
		// Step 2: Add their Credit Card
		//--------------------------------------------------------------------
		
		$customer_id = $response['customer_id'];

		$response = $this->AddCard($customer_id, $credit_card, $gateway);
		
		if ($response['ActionCode'] != '000')
		{	
			// Make the subscription inactive
			$CI->recurring_model->MakeInactive($subscription_id);
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['RVERRMTX']);
			$response = $CI->response->TransactionResponse(2, $response_array);
			return $response;
		}
		
		//--------------------------------------------------------------------
		// Step 3: Process Today's Payment
		//--------------------------------------------------------------------
		
		// Process today's payment
		if ($charge_today === TRUE) {
		
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $customer_id, $amount);
	
			if($response['success'] === TRUE){
				$CI->charge_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->recurring_model->MakeInactive($subscription_id);
				$CI->charge_model->SetStatus($order_id, 0);
				
				$response_array = array('reason' => $response['RVERRMTX']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: CreateProfile()
		
		This optional method is used for tokenized API's. This method 
		handles creating a customer profile and returning the new customer id.
		
		Not all of the provided information will be used with every gateway
		but they are provided in case you need them.
		
		NOTE: Our internal customer ID is passed along to their UDFIELD1 for 
		later retrieval
		
		Parameters:
			$client_id		- An int with the internal client id.
			$gateway		- An array of gateway information.
			$customer		- An array of customer information.
			$credit_card	- An array of credit card information.
			$subscription_id	- An INT with the internal ID of the subscription.
			$amount			- A float with the amount of the charge.
			$order_id		- An INT with the internal id of the order.
	*/
	public function CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id) 
	{
		$CI =& get_instance();
	
		/*
			An array of information to send to the gateway
			to create the customer profile.
		*/
		$data = array(
			'cardName'		=> $credit_card['name'],
			'firstName'		=> $customer['first_name'],
			'lastName'		=> $customer['last_name'],
			'udField1'		=> $customer['id'],
			'email'			=> $customer['email'],
			'billingStreet1'	=> $customer['address1'],
			'billingStreet2'	=> $customer['address2'],
			'billingCity'		=> $customer['city'],
			'billingStateProv'	=> $customer['state'],
			'billingPostalCode'	=> $customer['postal_code'],
			'billingCountry'	=> $customer['country'],
			'billingPhone'		=> $customer['phone']
		);
		
		$response = $this->process($gateway, $data, null, 'crtcust');

		if (!isset($response['RVERRMID']) || empty($response['RVERRMID']))
		{	
			$response['success'] = true;
			$response['customer_id'] = $response['CUSTOMER'];
			
			// Save the CustomerID
			
			// Single Charge
			if ($subscription_id == 0)
			{
				$CI->load->model('charge_data_model');
				$CI->charge_data_model->Save($order_id, 'CustomerID', $response['customer_id']);
			}
			// Subscription
			else
			{
				$CI->load->model('recurring_model');
				$CI->recurring_model->SaveApiCustomerReference($subscription_id, $response['customer_id']);
				$CI->load->model('charge_data_model');
				$CI->charge_data_model->Save($order_id, 'CustomerID', $response['customer_id']);
			}

			return $response;
		}
		else
		{
			$response['success'] = false;
			$response['reason'] = $response['RVERRMTX'];
			return $response;
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: ChargeRecurring
		
		This method handles the actual charging of a recurring payment, 
		both for the first-time time (from Recur) and remaining payments
		(from AutoRecurringCharge).
		
		Parameters:
			$client_id		- An INT with the internal client ID.
			$gateway		- An array of gateway information.
			$order_id		- An INT with the internal order_id.
			$customer_id	- An INT with the internal customer id.
			$amount			- A float with the amount of the charge.
			$occurrences	- The total number of payments.
	*/
	public function ChargeRecurring($client_id, $gateway, $order_id, $customer_id, $amount, $occurences=0) 
	{	
		$CI =& get_instance();
		
		// Let's be safe and pull up our 
				
		$response = $this->DoTran($customer_id, $amount, $transType='SA', $gateway);

		if ($response['ActionCode'] == '000')
		{
			$response['success']			= TRUE;
			$response['transaction_num']	= $response['TransactionID'];
			$response['auth_code']			= $response['Approval'];
			
			// Save the Auth information
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['transaction_num'], $response['auth_code']);
		} else 
		{
			$response['success'] 	= FALSE;
			$response['reason']		= $response['ResponseSummary']['StatusDescription'];
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: AutoRecurringCharge()
		
		Handles the normal recurring charge, after the first one.
		
		Parameters:
			$client_id	- An INT of the internal client ID.
			$order_id	- An INT with the internal order id.
			$gateway	- An array of the gateway information.
			$params		
	*/
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $order_id, $params['api_customer_reference'], $params['amount']);
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: UpdateRecurring()
		
		Updates the customer information for a subscription.
		Not all gateways support this.
		
		Parameters:
			$client_id	- An INT with the internal client id.
			$gateway	- An array of the the gateway's information.
			$subscription	- An array of the subscription information.
			$customer		- An array with the customer's information.
			$params			- Extra information.
	*/
	public function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params) 
	{
		// We only need to update customer information at the gateway.
		if (!is_array($customer))
		{
			// Just let OG know the update went fine.
			return TRUE;
		}
	
		// AT this point - update the customer info
		$data = array(
			'customer'			=> $subscription['api_customer_reference'],
			'cardName'			=> $customer['first_name'] .' '. $customer['last_name'],
			'firstName'			=> $customer['first_name'],
			'lastName'			=> $customer['last_name'],
			'billingStreet1'	=> isset($customer['address_1']) ? $customer['address_1'] : '',
			'billingStreet2'	=> isset($customer['address_2']) ? $customer['address_2'] : '',
			'billingCity'		=> isset($customer['city']) ? $customer['city'] : '',
			'billingStateProv'	=> isset($customer['state']) ? $customer['state'] : '',
			'billingPostalCode'	=> isset($customer['postal_code']) ? $customer['postal_code'] : '',
			'billingCountry'	=>isset($customer['country']) ? $customer['country'] : '',
			'billingPhone'		=> isset($customer['phone']) ? $customer['phone'] : '',
			'email'				=> isset($customer['email']) ? $customer['email'] : '',
		);
		
		$response = $this->process($gateway, $data, null, 'chgcust');

		if ($response['ActionCode'] == '000')
		{
			return true;
		}
		
		return false;
	}
	
	//--------------------------------------------------------------------
	
	/*
		Method: CancelRecurring()
		
		Cancels a recurring subscription at the gateway. Many gateways
		don't support this ability.
		
		Parameters:
			$client_id		- An INT with the internal client id.
			$subscription	- An array of subscription information.
	*/
	public function CancelRecurring($client_id, $subscription) 
	{
		return TRUE;	
	}
	
	//--------------------------------------------------------------------
	
	//--------------------------------------------------------------------
	// !PROCESSORS
	//--------------------------------------------------------------------
	
	/*
		Method: Process()
	*/
	private function Process($gateway, $post_vars, $trans_type, $url_suffix, $debug=false) 
	{
		$CI =& get_instance();

		// URL
		$post_url = $this->url . $url_suffix;
		
		// Authorization Values
		if ($gateway['mode'] == 'dev')
		{
			$post_vars['merchcomp']	= 1;
			$post_vars['accessid']	= 'TESTCOMP';
		}
		else
		{
			$post_vars['merchomp'] = $gateway['merchcomp'];
			$post_vars['accessid'] = $gateway['accessid'];
		}
		
		// Test mode?
		if ($gateway['mode'] != 'live')
		{
			$post_vars['transmode'] = 'T';
		} else
		{
			$post_vars['transmode'] = 'P';
		}
		
		// Transaction Type
		$post_vars['transType'] = $trans_type;
		
		$post_string = $this->compile_post($post_vars, $trans_type);

		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_VERBOSE, 1);
		curl_setopt($request, CURLOPT_POST, TRUE);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);   // Verify it belongs to the server.
	    curl_setopt($request, CURLOPT_SSL_VERIFYHOST, FALSE);   // Check common exists and matches the server host name
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		$post_response = curl_exec($request); // execute curl post and store results in $post_response

		curl_close ($request); // close curl object
		
		if ($debug)
		{
			echo "PostURL =  $post_url. Post String= $post_string <br/>";
			echo '<pre>'; die(str_replace('<', '&lt;', print_r($post_response)));	
		}
			
		$result = explode('&',$post_response);
		
		$response = array();
		
		foreach ($result as $pair)
		{
			$list = explode('=', $pair);
			$response[$list[0]] = $list[1];
		}

		return $response;
	}
	
	//--------------------------------------------------------------------
	
	
	//--------------------------------------------------------------------
	// !UTILITY METHODS
	//--------------------------------------------------------------------
	
	/*
		Method: compile_post()
		
		Compiles an array of values getting it ready to send to the 
		gateway. 
		
		Parameters:
			$vals	- An array of key/value pairs.
			$trans_type	- The type of transaction (ping, etc)
						  This is automatically added to the post array.
	*/
	private function compile_post($vals, $trans_type) 
	{	
		foreach ($vals as $key => &$value)
		{
			$vals[$key] = $value;
		}
		
		return http_build_query($vals);
	}
	
	//--------------------------------------------------------------------
	
}
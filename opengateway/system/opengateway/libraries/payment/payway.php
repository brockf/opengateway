<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * PayWay processing gateway.
 *
 * http://www.westpac.com.au/business-banking/merchant-accounts/online-phone/payway/
 * 
 * Prod URL: 
 * Test URL:
 * Dev  URL: 
 * Rebill Prod URL: 
 * Rebill Test URL: 
 * Rebill Dev  URL: 
 *
 * Card Type        Number          	Expiry	CVN		Response
 *----------------------------------------------------------------
 * Visa Approved:	4564710000000004 	02/19	847		08
 * MC Approved:		5163200000000008	08/20	070		08
 * Visa Expird		4564710000000012	02/05	963		54
 * Visa Low Funds	4564710000000020	05/20	234		51
 * Amex				376000000000006		06/20	2349	08
 * Diners Card:		36430000000007		06/22	348		08
 *
 * Demo account: 
 *	All testing is done through the production URL, but the customer
 *	merchant specified is "TEST".
 *
 * @package		OpenGateway
 * @author		Lonnie Ezell
 */

// Include the PayWay API Class
include APPPATH .'libraries/payway/Qvalent_PayWayAPI.php';

class payway {

	var $settings;
	
	var $pw;
	
	var $ci;
	
	/**
	 * if true, will echo out debug strings to verify
	 * that things are working. 
	 */
	private $debug = false;
	
	//--------------------------------------------------------------------
	
	function payway() {
		// Init our settings
		$this->settings = $this->Settings();
		
		$this->ci =& get_instance();
		
		// Grab an instance of the payway class.
		$this->pw = new Qvalent_PayWayAPI();
	}
	
	//--------------------------------------------------------------------
	
	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'PayWay';
		$settings['class_name'] = 'payway';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'An all-in-one package allowing you to collect direct debit and credit card payments, online or by phone. <br/><b>Your PayWay certificate must be placed uploaded to writeable/payway/ccapi.pem.</b>	';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = 'n/a';
		$settings['monthly_fee'] = 'n/a';
		$settings['transaction_fee'] = 'n/a';
		$settings['purchase_link'] = 'https://forms.westpac.com.au/forms/forms.nsf/merchAppl?OpenForm&referrer=http://www.westpac.com.au/business-banking/merchant-accounts/online-phone/payway/';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 0;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'username',
										'password',
										'merchant_id',
										'accept_visa',
										'accept_mc',
										'accept_discover',
										'accept_dc',
										'accept_amex',
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
										'username' => array(
														'text' => 'Username',
														'type' => 'text'
														),
										
										'password' => array(
														'text' => 'Password',
														'type' => 'password'
														),
										'merchant_id' => array(
														'text'	=> 'Merchant ID',
														'type'	=> 'text'
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

	function TestConnection($client_id, $gateway) 
	{
		// Is the certificate in place?
		if (!is_file(FCPATH .'writeable/payway/ccapi.pem'))
		{	
			return false;
		}
	
		$this->init_payway();
		
		// Create A fake charge to test...
	    $requestParameters = array();
	    $requestParameters[ "order.type" ] = 'capture';
	    $requestParameters[ "customer.username" ] = $gateway['username'];
	    $requestParameters[ "customer.password" ] = $gateway['password'];
	    $requestParameters[ "customer.merchant" ] = 'TEST';		
	    $requestParameters[ "customer.orderNumber" ] = rand(1,10000);
	    $requestParameters[ "customer.captureOrderNumber" ] = '0010';
	    $requestParameters[ "card.PAN" ] = '4564710000000004';
	    $requestParameters[ "card.CVN" ] = '';
	    $requestParameters[ "card.expiryYear" ] = '19';
	    $requestParameters[ "card.expiryMonth" ] = '02';
	    $requestParameters[ "card.currency" ] = 'AUD';
	    $requestParameters[ "order.amount" ] = number_format( (float)rand(1,50) * 100, 0, '.', '' );;
	    $requestParameters[ "order.ECI" ] = 'SSL';
	    
	    $requestText = $this->pw->formatRequestParameters( $requestParameters );

	    $responseText = $this->pw->processCreditCard( $requestText );
	
	    // Parse the response string into an array
	    $responseParameters = $this->pw->parseResponseParameters( $responseText );
	    
	    if ($this->debug)
	    {
	    	echo '<h2>CreateProfile Response</h2>';
	    	print_r($responseParameters);
	    	die();
	    }
	    
	    if($responseParameters['response.summaryCode'] == 0)
	    {
	    	return TRUE;
	    }
	    else
	    {
	    	return FALSE;
	    }
		
	}
	
	//--------------------------------------------------------------------
	
	/**
	 *	Recur - called when an initial Recur charge comes through to
	 *	to create a subscription.
	 *
	 */
	function Recur ($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{
		$this->init_payway();
		
		// Create an order for today's payment
		$this->ci->load->model('charge_model');
		$customer['customer_id'] = (isset($customer['customer_id'])) ? $customer['customer_id'] : FALSE;
		$order_id = $this->ci->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);
		
		// Create the recurring seed
		$response = $this->CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id);
		
		// Process today's payment
		if ($charge_today === TRUE) {
			if ($gateway['mode'] != 'live') $response['client_id'] = '9876543211000';
		
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $customer['id'], $amount);
		
			if ($this->debug)
			{
				echo '<h2>Recur: After ChargeRecurring</h2><br/>';
				echo 'Customer ID = '. $customer['id'];
		    	print_r($response);
			}
		
			if($response['success'] == TRUE){
				$this->ci->charge_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $this->ci->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$this->ci->recurring_model->MakeInactive($subscription_id);
				$this->ci->charge_model->SetStatus($order_id, 0);
				
				$response_array = array('reason' => $response['reason']);
				$response = $this->ci->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $this->ci->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Creates a customer profile, along with the setting up the contract,
	 * and saving the subscription info. This is called from the Recur method.
	 */
	function createProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id)
	{
		$this->init_payway();
		
		$requestParameters = array();
	    $requestParameters[ "order.type" ] = 'registerAccount';
	    $requestParameters[ "customer.username" ] = $gateway['username'];
	    $requestParameters[ "customer.password" ] = $gateway['password'];
	    $requestParameters[ "customer.merchant" ] = $gateway['merchant_id'];
	    $requestParameters[ "customer.customerReferenceNumber" ] = $customer['id'];
	    $requestParameters[ "card.PAN" ] = $credit_card['card_num'];
	    $requestParameters[ "card.expiryYear" ] = substr($credit_card['exp_year'], -2);
	    $requestParameters[ "card.expiryMonth" ] = $credit_card['exp_month'];
	    $requestParameters[ "card.cardHolderName" ] = $credit_card['name'];
	    
	    $requestText = $this->pw->formatRequestParameters( $requestParameters );

	    $responseText = $this->pw->processCreditCard( $requestText );
	
	    // Parse the response string into an array
	    $responseParameters = $this->pw->parseResponseParameters( $responseText );
	    
	    if ($this->debug)
	    {
	    	echo '<h2>CreateProfile Request</h2>';
	    	print_r($requestText);
	    	echo '<h2>CreateProfile Response</h2>';
	    	print_r($responseParameters);
	    }
	    
	    if($responseParameters['response.summaryCode'] == 0)
		{	
			$response['success'] = true;
			$response['client_id'] = $client_id;
			
			/*
				Save the Auth information
				
				Since the PayWay doesn't return an auth number, we use the customer id,
				since that's what we'll use to talk back to the system later.
			*/			
			$authkey = $customer['id'];
			
			$CI =& get_instance();
			$CI->recurring_model->SaveApiCustomerReference($subscription_id, $authkey);
			// Client successfully created at PayLeap. Now we ned to save the info here. 
			return $response;
		}
		else
		{
			$response['success'] = false;
			$response['reason'] = $responseParameters['response.text'];
			return $response;
		}
	}
	
	//--------------------------------------------------------------------
	
	/**
	 *	Handles paying the recurring charge. 
	 *
	 */	 
	function ChargeRecurring ($client_id, $gateway, $order_id, $customer_id, $amount) 
	{
		$this->init_payway();
	    
	    $requestParameters = array();
	    $requestParameters[ "order.type" ] = 'capture';
	    $requestParameters[ "customer.username" ] = $gateway['username'];
	    $requestParameters[ "customer.password" ] = $gateway['password'];
	    $requestParameters[ "customer.merchant" ] = ($gateway['mode'] == 'live') ? $gateway['merchant_id'] : 'TEST';
	    $requestParameters[ 'customer.customerReferenceNumber' ] = $customer_id;
	    $requestParameters[ "customer.orderNumber" ] = $order_id;
	    $requestParameters[ "card.currency" ] = 'AUD';
	    $requestParameters[ "order.amount" ] = number_format( (float)$amount * 100, 0, '.', '' );
	    $requestParameters[ "order.ECI" ] = 'SSL';
	    
	    $requestText = $this->pw->formatRequestParameters( $requestParameters );

	    $responseText = $this->pw->processCreditCard( $requestText );
	
	    // Parse the response string into an array
	    $responseParameters = $this->pw->parseResponseParameters( $responseText );
	    
	    
	    if ($this->debug)
	    {
	    	echo '<h2>ChargeRecurring Request</h2><br/>';
	    	echo 'Customer ID = '. $customer_id;
	    	print_r($requestParameters);
	    	print_r($requestText);
	    	echo '<h2>ChargeRecurring Response</h2><br/>';
	    	print_r($responseParameters);
	    }
	    
	    if ($responseParameters['response.summaryCode'] == '0')
		{
			$response['success']			= TRUE;
			$response['transaction_num']	= $responseParameters['response.receiptNo'];
			$response['auth_code']			= $responseParameters['response.receiptNo'];
			
			// Save the Auth information
			$CI =& get_instance();
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['transaction_num'], $response['auth_code']);
		} else 
		{
			$response['success'] 	= FALSE;
			$response['reason']		= $responseParameters['response.text'];
		}
		
		return $response;
	}
	
	//--------------------------------------------------------------------
	
	public function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params) 
	{
		/*
			PayWay only allows for updating of the customer's credit card number on their file.
		*/
		return TRUE;
	}
	
	//--------------------------------------------------------------------
	
	public function CancelRecurring($client_id, $subscription, $gateway) 
	{
		// This removes the client from PayWay's system so rebills cannot be done.
	
		$this->init_payway();
	    
	    $requestParameters = array();
	    $requestParameters[ "order.type" ] = 'deregisterAccount';
	    $requestParameters[ "customer.username" ] = $gateway['username'];
	    $requestParameters[ "customer.password" ] = $gateway['password'];
	    $requestParameters[ "customer.merchant" ] = ($gateway['mode'] == 'live') ? $gateway['merchant_id'] : 'TEST';
	    $requestParameters[ "customer.customerReferenceNumber" ] = $subscription['customer_id'];
	    
	    $requestText = $this->pw->formatRequestParameters( $requestParameters );

	    $responseText = $this->pw->processCreditCard( $requestText );
	
	    // Parse the response string into an array
	    $responseParameters = $this->pw->parseResponseParameters( $responseText );
	    
	    if ($responseParameters['response.statusCode'] == 0)
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
		$this->init_payway();
	    
	    $requestParameters = array();
	    $requestParameters[ "order.type" ] = 'capture';
	    $requestParameters[ "customer.username" ] = $gateway['username'];
	    $requestParameters[ "customer.password" ] = $gateway['password'];
	    $requestParameters[ "customer.merchant" ] = ($gateway['mode'] == 'live') ? $gateway['merchant_id'] : 'TEST';
	    $requestParameters[ "customer.orderNumber" ] = $order_id;
	    $requestParameters[ "customer.captureOrderNumber" ] = $order_id;
	    $requestParameters[ "card.PAN" ] = $credit_card['card_num'];
	    $requestParameters[ "card.CVN" ] = isset($credit_card['cvv']) ? $credit_card['cvv'] : '';
	    $requestParameters[ "card.expiryYear" ] = substr($credit_card['exp_year'], -2);
	    $requestParameters[ "card.expiryMonth" ] = $credit_card['exp_month'];
	    $requestParameters[ "card.currency" ] = 'AUD';						// How to handle? 
	    $requestParameters[ "order.amount" ] = number_format( (float)$amount * 100, 0, '.', '' );;
	    $requestParameters[ "order.ECI" ] = 'SSL';
	    
	    $requestText = $this->pw->formatRequestParameters( $requestParameters );

	    $responseText = $this->pw->processCreditCard( $requestText );
	
	    // Parse the response string into an array
	    $responseParameters = $this->pw->parseResponseParameters( $responseText );
	    
	    if ($responseParameters[ "response.summaryCode" ] == 0)
		{
			$this->ci->load->model('order_authorization_model');
			$this->ci->order_authorization_model->SaveAuthorization($order_id, $responseParameters['response.receiptNo'], $responseParameters['response.receiptNo']);
			$this->ci->charge_model->SetStatus($order_id, 1);
			
			$response_array = array('charge_id' => $order_id);
			$response = $this->ci->response->TransactionResponse(1, $response_array);
		}
		else 
		{
			$this->ci->load->model('charge_model');
			$this->ci->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $responseParameters['response.text']);
			$response = $this->ci->response->TransactionResponse(2, $response_array);
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
	// !PRIVATE METHODS
	//--------------------------------------------------------------------
	
	/**
	 * Initialises the PayWay client API object.
	 */
	private function init_payway()
	{
		// Don't re-initialise
		if ($this->pw->isInitialised())
		{
			return;
		}
		
		$log_directory		= SYSPATH .'logs/';			// Full path to a writable log file
		$certificate_file	= FCPATH .'writeable/payway/ccapi.pem';	// Full path to customer's certificate (downloaded from website)
		$ca_file			= APPPATH .'libraries/payway/cacerts.crt';		// Full path to provided ca file
		
		$params = "certificateFile=$certificate_file&caFile=$ca_file&logDirectory=$log_directory";
		
		unset($log_directory, $certificate_file, $ca_file);
		
		return $this->pw->initialise($params);
	}
	
	//--------------------------------------------------------------------
}

// End PayWay class
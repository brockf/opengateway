<?php

/**
 * eWAY processing gateway.
 *
 * @package 	OpenGateway
 * @author		Dave Ryan
 * @modified	Lonnie Ezell
 */

class eway
{
	var $settings;
	
	/**
	 * if true, will echo out debug strings to verify
	 * that things are working. 
	 */
	private $debug = false;
	
	//---------------------------------------------------------------
	
	function eway() {
		$this->settings = $this->Settings();
		
		if ($this->debug)
		{
			echo '<pre>';
		}
	}

	//---------------------------------------------------------------

	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'eWAY';
		$settings['class_name'] = 'eway';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'eWAY is the premier gateway solution in Australia.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$0';
		$settings['monthly_fee'] = '$29';
		$settings['transaction_fee'] = '$0.50';
		$settings['purchase_link'] = 'https://www.eway.com.au/join/secure/signup.aspx';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 0;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'customer_id',
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
										'customer_id' => array(
														'text' => 'Login ID',
														'type' => 'text'
														),
										
										'username' => array(
														'text' => 'Rebill Username',
														'type' => 'text'
														),
										
										'password' => array(
														'text' => 'Rebill Password',
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
	
	//---------------------------------------------------------------
	
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
		$post_url = $this->GetAPIUrl($gateway, 'rebill');	// Make sure we get the rebill url.
		
		$customer = array(
			'title'			=> 'Mr.',
			'first_name'	=> 'John',
			'last_name'		=> 'Doe',
			'address_1'		=> 'John Doe Enterprise',
			'city'			=> 'Capital City',
			'state'			=> 'ACT',
			'company'		=> 'Doe',
			'postal_code'	=> '2111',
			'country'		=> 'au',
			'email'			=> 'test@eway.com.au',
			'customer_id'	=> 'Ref123'
		);
		
		$response = $this->createRebillCustomer($gateway, $customer);
		
		if ($response) 
		{ 
			return TRUE;
		}
		
		return FALSE;
	}
	
	//---------------------------------------------------------------
	
	//---------------------------------------------------------------
	// !CUSTOMER FUNCTIONS
	//---------------------------------------------------------------
	
	function createRebillCustomer($gateway, $customer)
	{
	    $array = array(
	    	customerTitle		=> $customer['title'],
	    	customerFirstName 	=> $customer['first_name'],
	    	customerLastName	=> $customer['last_name'],
	    	customerAddress		=> $customer['address_1'],
	    	customerSuburb		=> $customer['city'],
	    	customerState		=> $customer['state'],
	    	customerCompany		=> $customer['company'],
	    	customerPostCode	=> $customer['postal_code'],
	    	customerCountry		=> $customer['country'],
	    	customerEmail		=> $customer['email'],
	    	customerRef			=> $customer['customer_id']
	    );
    
		$response = $this->processSoap($gateway, $this->complete_array($array), 'CreateRebillCustomer');
		
		if ($this->debug)
    	{
    		echo '<h2>Customer Array:</h2>';
    		print_r($customer);
    		
    		echo '<h2>CreateRebillCustomer Reponse:</h2>';
    		print_r($response);
    	}
		
		if($response->CreateRebillCustomerResult->Result == 'Success')
		{
			return $response->CreateRebillCustomerResult->RebillCustomerID;
		}
		else
		{
			return FALSE;
		}
	}
	
	//---------------------------------------------------------------
	
	function getCustomerProfile($gateway, $profile_id)
	{
		/*$xml = '<QueryRebillCustomer xmlns="http://www.eway.com.au/gateway/rebill/manageRebill">
      <RebillCustomerID>'.$profile_id.'</RebillCustomerID>
    </QueryRebillCustomer>';*/
    
    	$xml = array(
    		RebillCustomerID	=> $profile_id
    	);
    
		$response = $this->processSoap($gateway, $this->complete_array($xml), 'QueryRebillCustomer');
		
		if($response->QueryRebillCustomerResult->Result == 'Success')
		{
			return $response->QueryRebillCustomerResult;
		}
		else
		{
			return FALSE;
		}
	}
	
	//---------------------------------------------------------------
	
	//---------------------------------------------------------------
	// !EVENT FUNCTIONS
	//---------------------------------------------------------------
	
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{ 
		$CI =& get_instance();
	
		$post_url = $this->GetAPIUrl($gateway);
		
		$post['ewaygateway']['ewayCustomerID'] = $gateway['customer_id'];
		$post['ewaygateway']['ewayTotalAmount'] = number_format($amount,2,'','');
		
		$post['ewaygateway']['ewayCardNumber'] = $credit_card['card_num'];
		$post['ewaygateway']['ewayCardExpiryMonth'] = $credit_card['exp_month'];
		$post['ewaygateway']['ewayCardExpiryYear'] = substr($credit_card['exp_year'],-2,2);
		$post['ewaygateway']['ewayTrxnNumber'] = '';
		$post['ewaygateway']['ewayOption1'] = '';
		$post['ewaygateway']['ewayOption2'] = '';
		$post['ewaygateway']['ewayOption3'] = '';
		$post['ewaygateway']['ewayCustomerInvoiceDescription'] = '';
		$post['ewaygateway']['ewayCustomerInvoiceRef'] = '';
		
		$post['ewaygateway']['ewayCardHoldersName'] = $customer['first_name'].' '.$customer['last_name'];
		$post['ewaygateway']['ewayCustomerFirstName'] = $customer['first_name'];
		$post['ewaygateway']['ewayCustomerLastName'] = $customer['last_name'];
		$post['ewaygateway']['ewayCustomerAddress'] = $customer['address_1'];
		if (isset($customer['address_2']) and !empty($customer['address_2'])) {
			$post['ewaygateway']['ewayCustomerAddress'] .= ' - '.$customer['address_2'];
		}
		$post['ewaygateway']['ewayCustomerPostcode'] = $customer['postal_code'];
		$post['ewaygateway']['ewayCustomerEmail'] = $customer['email'];
		
		if(isset($credit_card['cvv'])) {
			$post['ewaygateway']['ewayCVN'] = $credit_card['cvv'];
		}
		
		$CI->load->library('arraytoxml');
		$xml = $CI->arraytoxml->toXml($post);
		
		$xml = str_replace('<ResultSet>','', $xml);
		$xml = str_replace('</ResultSet>','', $xml);
		
		if ($this->debug)
		{
			echo '<pre>';
			echo 'URL = '. $post_url .'<br/>';
			var_dump($xml);
		}
		
		$response = $this->Process($post_url,$xml);
		
		if($response['ewayTrxnStatus'] == 'True')
		{ 
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['ewayTrxnNumber'], $response['ewayAuthCode']);
			$CI->charge_model->SetStatus($order_id, 1);
			
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		else
		{
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);
			
			$response_array = array('reason' => $response['ewayTrxnError']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	//---------------------------------------------------------------
	
	function Recur ($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{		
		$CI =& get_instance();
		
		if($gateway['mode'] =='test')
		{
			$post_url = $gateway['arb_url_test'];
		}
		else
		{
			$post_url = $gateway['arb_url_live'];
		}

		// Create a new eway profile if one doesn't exist
		$CI->db->select('api_customer_reference');
		$CI->db->join('client_gateways', 'subscriptions.gateway_id = client_gateways.client_gateway_id', 'inner');
		$CI->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$CI->db->where('api_customer_reference !=','');
		$CI->db->where('subscriptions.gateway_id',$gateway['gateway_id']);
		$CI->db->where('subscriptions.active', 1);
		$CI->db->where('subscriptions.customer_id',$customer['customer_id']);
		$current_profile = $CI->db->get('subscriptions');
			
		if ($current_profile->num_rows() > 0) {
			// save the profile ID
			$current_profile = $current_profile->row_array();
			$profile_id = $current_profile['api_customer_reference'];
		}
		else {
			$response = $this->createRebillCustomer($gateway, $customer);
			
			if($response) {
				$profile_id = $response;	
			}
		}
		
		if (empty($profile_id)) {
			$add_text = (isset($response['reason'])) ? $response['reason'] : FALSE;
			$CI->recurring_model->DeleteRecurring($subscription_id);
			die($CI->response->Error(5005, $add_text));
		}

		// save the api_customer_reference
		$CI->load->model('recurring_model');
		$CI->recurring_model->SaveApiCustomerReference($subscription_id, $profile_id);
		
		
		// Create the rebill event
		
		$rebill_id = $this->createRebillEvent($profile_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences);
		
		if(!$rebill_id)
		{
			$add_text = (isset($response['reason'])) ? $response['reason'] : FALSE;
			$CI->recurring_model->DeleteRecurring($subscription_id);
			die($CI->response->Error(5005, $add_text));
		}
		
		// Save the api_payment_reference
		$CI->recurring_model->SaveApiPaymentReference($subscription_id, $rebill_id);
		
		$CI->load->model('charge_model');
		
		$CI->charge_model->SetStatus($order_id, 1);
		$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
		$response = $CI->response->TransactionResponse(100, $response_array);
		
		return $response;
	}
	
	//---------------------------------------------------------------
	
	function CancelRecurring($client_id, $subscription)
	{
		/*$xml =
		'<DeleteRebillEvent xmlns="http://www.eway.com.au/gateway/rebill/manageRebill">
      <RebillCustomerID>'.$subscription['api_customer_reference'].'</RebillCustomerID>
      <RebillID>'.$subscription['api_payment_reference'].'</RebillID>
    </DeleteRebillEvent>
';*/

		$xml = array(
			RebillCustomerID	=> $subscription['api_customer_reference'],
			RebillID			=> $subscription['api_payment_reference']
		);

		$CI =& get_instance();
		$CI->load->model('gateway_model');
		

		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $subscription['gateway_id']);
		$response = $this->processSoap($gateway, $this->complete_array($xml), 'DeleteRebillEvent');
		
		if($response->DeleteRebillEventResult->Result == 'Success')
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}

		
	}
	
	//---------------------------------------------------------------
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return TRUE;
	}
	
	//---------------------------------------------------------------
	
	function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params)
	{
		return TRUE;
	}
	
	//---------------------------------------------------------------
	
	function createRebillEvent($profile_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{
		
		if($charge_today)
		{
			$init_amount = number_format($amount,2,'','');
		}
		else
		{
			$init_amount = 0;
		}
		
		/*$xml =
		'<CreateRebillEvent xmlns="http://www.eway.com.au/gateway/rebill/manageRebill">
      <RebillCustomerID>'.$profile_id.'</RebillCustomerID>
      <RebillInvRef>'.$subscription_id.'</RebillInvRef>
      <RebillInvDes></RebillInvDes>
      <RebillCCName>'.$credit_card['name'].'</RebillCCName>
      <RebillCCNumber>'.$credit_card['card_num'].'</RebillCCNumber>
      <RebillCCExpMonth>'.$credit_card['exp_month'].'</RebillCCExpMonth>
      <RebillCCExpYear>'.substr($credit_card['exp_year'],-2,2).'</RebillCCExpYear>
      <RebillInitAmt>'.$init_amount.'</RebillInitAmt>
      <RebillInitDate>'.date('d/m/Y').'</RebillInitDate>
      <RebillRecurAmt>'.number_format($amount,2,'','').'</RebillRecurAmt>
      <RebillStartDate>'.date('d/m/Y',strtotime($start_date)).'</RebillStartDate>
      <RebillInterval>'.$interval.'</RebillInterval>
      <RebillIntervalType>1</RebillIntervalType>
      <RebillEndDate>'.date('d/m/Y',strtotime($end_date)).'</RebillEndDate>
    </CreateRebillEvent>
    */
    	
    	$xml = array(
    		RebillCustomerID	=> $profile_id,
    		RebillInvRef		=> $subscription_id,
    		RebillInvDes		=> '',
    		RebillCCName		=> $credit_card['name'],
    		RebillCCNumber		=> $credit_card['card_num'],
    		RebillCCExpMonth	=> $credit_card['exp_month'],
    		rebillCCExpYear		=> $credit_card['exp_year'],
    		RebillInitAmt		=> $init_amount,
    		RebillInitDate		=> date('d/m/Y'),
    		RebillRecurAmt		=> number_format($amount, 2, '', ''),
    		RebillStartDate		=> date('d/m/Y', strtotime($start_date)),
    		RebillInterval		=> $interval,
    		rebillIntervalType	=> 1,
    		RebillEndDate		=> date('d/m/Y', strtotime($end_date))
    	);

		$response = $this->processSoap($gateway, $xml, 'CreateRebillEvent');
		
		if($response->CreateRebillEventResult->Result == 'Success')
		{
			return $response->CreateRebillEventResult->RebillID;
		}
		else
		{
			return FALSE;
		}
	}
	
	//---------------------------------------------------------------
	
	//---------------------------------------------------------------
	// !PROCESSORS
	//---------------------------------------------------------------
	
	function Process($url, $xml)
	{			
		$ch = curl_init($url); // initiate curl object
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml); // use HTTP POST to send form data
		
		// We need to make curl recognize the CA certificated so it can get by...
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);	// Verify it belongs to the server.
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);	// Check common exists and matches the server host name
		curl_setopt($ch, CURLOPT_CAINFO, BASEPATH . "opengateway/libraries/payment/Certificates/eway.crt");
		
		$post_response = curl_exec($ch); // execute curl post and store results in $post_response
		
		if ($this->debug)
		{
			echo '<pre>';
			var_dump($post_response); die();
		}
		
		if (curl_errno($ch) == CURLE_OK)
		{
			$response_xml = @simplexml_load_string($post_response);
			$CI =& get_instance();
			$CI->load->library('arraytoxml');
			$response = $CI->arraytoxml->toArray($response_xml);
			
			return $response;
		}
		
		return FALSE;
		
	}
	
	//---------------------------------------------------------------
	
	/**
	 * ProcessSoap()
	 * 
	 * Uses the SOAP protocol to talk with eway. Sends a secure header
	 * 
	 */
	function processSoap($gateway, $xml, $action)
	{
		
		$url = $this->GetAPIUrl($gateway, 'rebill');
		
		$client = new SoapClient($url.'?WSDL', array('trace'=>TRUE));
		
		// Set our SOAP Headers for authentication
		$header_body = array(
			'eWAYCustomerID'	=> $gateway['customer_id'],
			'Username'			=> $gateway['username'],
			'Password'			=> $gateway['password']
		);
				
		$header = new SOAPHeader('http://www.eway.com.au/gateway/rebill/manageRebill', 'eWAYHeader', $header_body);
		$client->__setSoapHeaders($header);
				
		$response = $client->__soapCall($action, array($xml), null, $header);
				
		if ($this->debug)
		{
			echo 'URL = '. $url;
			
			/// TEST RESULTS ///
			echo '<h2>Request</h2>';
			print_r($client->__getLastRequest());
			
			echo '<h2>Response</h2>';
			print_r($response);
			// END TEST RESULTS ///
		}
		
		return $response;
	}
	
	//---------------------------------------------------------------
	
	//---------------------------------------------------------------
	// !UTILITY FUNCTIONS
	//---------------------------------------------------------------
	
	/**
	 * Returns the proper url for the remote gateway.
	 *
	 * Note that $mode param defaults to false, which will
	 * return the token payments url. If $mode is 'arb', 
	 * then it will return the rebill url.
	 */
	function GetAPIUrl ($gateway, $mode = FALSE) {
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
	
	/** 
	 * Creates an array containing all of the fields available
	 * to the eWay Rebill server. It fills in empty fields for 
	 * any that are not passed in.
	 *
	 * See: http://www.eway.com.au/Developer/eway-api/recurring-payments.aspx
	 *
	 * @param	array	$array - the array of fields that are required
	 * @returns	array	The original array with any blank fields added.
	 */
	function complete_array($orig_array = array(), $include_rebill=false) 
	{
		$complete_if_blank = array(
			'ewayCustomerID',
			
			// Customer Info
			'customerRef',
			'customerTitle',
			'customerFirstName',
			'customerLastName',
			'customerCompany',
			'customerJobDesc',
			'customerEmail',
			'customerAddress',
			'customerState',
			'customerPostCode',
			'customerCountry',
			'customerPhone1',
			'customerPhone2',
			'customerFax',
			'customerURL',
			'customerComments',
			
			// Rebill Info
			'rebillInvRef',
			'rebillInvDesc',
			'rebillCCName',
			'rebillCCNumber',
			'rebillCCExpMonth',
			'rebillCCExpYear',
			'rebillInitAmt',
			'rebillInitDate',
			'rebillRecurAmt',
			'rebillStartDate',
			'rebillInterval',
			'rebillEndDate'
		);
				
		// Add in any elements that do not exist.
		foreach ($complete_if_blank as $key => $index)
		{
			if (!array_key_exists($index, $orig_array))
			{
				$orig_array[$index] = ''; 
			}
		}
		
		return $orig_array;
	}
	
	//---------------------------------------------------------------
	
}
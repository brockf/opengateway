<?php

class eway
{
	var $settings;
	
	function eway() {
		$this->settings = $this->Settings();
	}

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
	
	function TestConnection($client_id, $gateway) 
	{
		//$post_url = $this->GetAPIUrl($gateway);
		$post_url = 'https://www.eway.com.au/gateway/xmltest/testpage.asp';
		
		$xml = '<ewaygateway>
				<ewayCustomerID>87654321</ewayCustomerID>
				<ewayTotalAmount>00</ewayTotalAmount>
				<ewayCustomerFirstName></ewayCustomerFirstName>
				<ewayCustomerLastName></ewayCustomerLastName>
				<ewayCustomerEmail></ewayCustomerEmail>
				<ewayCustomerAddress></ewayCustomerAddress>
				<ewayCustomerPostcode></ewayCustomerPostcode>
				<ewayCustomerInvoiceDescription></ewayCustomerInvoiceDescription>
				<ewayCustomerInvoiceRef></ewayCustomerInvoiceRef>
				<ewayCardHoldersName></ewayCardHoldersName>
				<ewayCardNumber>4444333322221111</ewayCardNumber>
				<ewayCardExpiryMonth>01</ewayCardExpiryMonth>
				<ewayCardExpiryYear>14</ewayCardExpiryYear>
				<ewayTrxnNumber></ewayTrxnNumber>
				<ewayOption1></ewayOption1>
				<ewayOption2></ewayOption2>
				<ewayOption3></ewayOption3>
			</ewaygateway>';
		
		$response = $this->Process($post_url,$xml);
		
		if($response['ewayTrxnStatus'] == 'True')
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{
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
	
	function createRebillCustomer($gateway, $customer)
	{
		$xml ='
    <CreateRebillCustomer xmlns="http://www.eway.com.au/gateway/rebill/manageRebill">
      <customerTitle></customerTitle>
      <customerFirstName>'.$customer['first_name'].'</customerFirstName>
      <customerLastName>'.$customer['last_name'].'</customerLastName>
      <customerAddress>'.$customer['address_1'].'</customerAddress>
      <customerSuburb>'.$customer['city'].'</customerSuburb>
      <customerState>'.$customer['state'].'</customerState>
      <customerCompany>'.$customer['company'].'</customerCompany>
      <customerPostCode>'.$customer['postal_code'].'</customerPostCode>
      <customerCountry>'.$customer['country'].'</customerCountry>
      <customerEmail>'.$customer['email'].'</customerEmail>
      <customerRef>'.$customer['customer_id'].'</customerRef>
      <customerJobDesc></customerJobDesc>
      <customerComments></customerComments>
      <customerURL></customerURL>
    </CreateRebillCustomer>';
    
		$response = $this->processSoap($gateway, $xml);
		
		if($response->CreateRebillCustomerResponse->CreateRebillCustomerResult->Result == 'Success')
		{
			return $response->CreateRebillCustomerResponse->CreateRebillCustomerResult->RebillCustomerID;
		}
		else
		{
			return FALSE;
		}
	}
	
	function getCustomerProfile($gateway, $profile_id)
	{
		$xml = '<QueryRebillCustomer xmlns="http://www.eway.com.au/gateway/rebill/manageRebill">
      <RebillCustomerID>'.$profile_id.'</RebillCustomerID>
    </QueryRebillCustomer>';
    
		$response = $this->processSoap($gateway, $xml);
		
		if($response->QueryRebillCustomerResponse->QueryRebillCustomerResult->Result == 'Success')
		{
			return $response->QueryRebillCustomerResponse->QueryRebillCustomerResult;
		}
		else
		{
			return FALSE;
		}
	}
	
	function processSoap($gateway, $xml)
	{
		
		$url = $this->GetAPIUrl($gateway, 'arb');
		
		$header = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Header>
    <eWAYHeader xmlns="http://www.eway.com.au/gateway/rebill/manageRebill">
      <eWAYCustomerID>'.$gateway['customer_id'].'</eWAYCustomerID>
      <Username>'.$gateway['username'].'</Username>
      <Password>'.$gateway['password'].'</Password>
    </eWAYHeader>
  </soap12:Header>
  <soap12:Body>';
		
		$footer = '</soap12:Body></soap12:Envelope>';

		$request = $header.$xml.$footer;
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL,$url);
       		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       		curl_setopt($ch, CURLOPT_TIMEOUT, 36000);
      		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/soap+xml; charset=utf-8'));
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request); 
       		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		
		$data = curl_exec($ch); 
		
		$data = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $data);

		$response =  simplexml_load_string($data);
		
		return $response->soapBody;
	}
	
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
		
		$xml =
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
';

		$response = $this->processSoap($gateway, $xml);
		
		if($response->CreateRebillEventResponse->CreateRebillEventResult->Result == 'Success')
		{
			return $response->CreateRebillEventResponse->CreateRebillEventResult->RebillID;
		}
		else
		{
			return FALSE;
		}
	}
	
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
	
	
	function Process($url, $xml)
	{
		$ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$xmlResponse = curl_exec($ch);
		
		if(curl_errno($ch) == CURLE_OK)
		{
			$response_xml = @simplexml_load_string($xmlResponse);
			$CI =& get_instance();
			$CI->load->library('arraytoxml');
			$response = $CI->arraytoxml->toArray($response_xml);
			
			return $response;
		}
		
		return FALSE;
		
	}
	
	function CancelRecurring($client_id, $subscription)
	{
		$xml =
		'<DeleteRebillEvent xmlns="http://www.eway.com.au/gateway/rebill/manageRebill">
      <RebillCustomerID>'.$subscription['api_customer_reference'].'</RebillCustomerID>
      <RebillID>'.$subscription['api_payment_reference'].'</RebillID>
    </DeleteRebillEvent>
';

		$CI =& get_instance();
		$CI->load->model('gateway_model');
		

		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $subscription['gateway_id']);
		$response = $this->processSoap($gateway, $xml);
		
		if($response->DeleteRebillEventResponse->DeleteRebillEventResult->Result == 'Success')
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}

		
	}
	
	
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return TRUE;
	}
	
	function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params)
	{
		return TRUE;
	}
	
	
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
		elseif ($mode == 'arb') {
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
	
}
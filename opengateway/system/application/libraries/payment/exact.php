<?php
class exact
{
	function Charge($client_id, $order_id, $gateway, $customer, $params, $credit_card)
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
			
		$trxnProperties = array(
		'ExactID'			=> $gateway['terminal_id'],	
  		'Password'			=> $gateway['password'],
		'Transaction_Type'  => '00',
	 	'Card_Number' 		=> $credit_card['card_num'],
		'Expiry_Date'		=> $credit_card['exp_month'] . substr($credit_card['exp_year'],-2,2),
		'CVD_Presence_Ind' 	=> (empty($credit_card['cvv'])) ? '9' : '1',
		'Customer_Ref' 		=> $order_id,
		'DollarAmount' 		=> $params['amount']
		  );
		
		if(isset($credit_card->cvv)) {
			$trxnProperties['VerificationStr1'] = $credit_card['cvv'];
		}  
		
		if(isset($customer['customer_id'])) {
			$trxnProperties['CardHoldersName'] = $customer['first_name'].' '.$customer['last_name'];
		} else {
			$name = explode(' ', $credit_card['card_name']);
			$trxnProperties['CardHoldersName'] = $name[0].' '.$name[1];
			
		}
		  
		$trxnProperties = $this->CompleteArray($trxnProperties); 
		  
		$trxnResult = $this->Process($trxnProperties, $post_url, $order_id);
		
		if($trxnResult->EXact_Resp_Code == '00'){
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $trxnResult->Transaction_Tag);
			$response_array = array('order_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $trxnResult->EXact_Message);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;

	}
	
	function Recur($client_id, $gateway, $customer, $params, $start_date, $end_date, $interval, $credit_card, $subscription_id)
	{
		$CI =& get_instance();
		
		// Create an order for today's payment
		$CI->load->model('order_model');
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $credit_card, $subscription_id);
		
		// Create the recurring seed
		$response = $this->CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $params, $order_id);
		  
		// Process today's payment
		if(date('Y-m-d', $start_date) == date('Y-m-d')) {
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $response['transaction_tag'], $response['auth_num'], $params);
		
			if($response['success'] == TRUE){
				$CI->order_model->SetStatus($order_id, 1);
				$response_array = array('order_id' => $order_id, 'subscription_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->subscription_model->MakeInactive($subscription_id);
				$CI->order_model->SetStatus($order_id, 0);
				
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $CI->response->TransactionResponse(100);
		}
		
		return $response;
	}
	
	function CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $params, $order_id)
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

		// Create the recurring seed
		
		$trxnProperties = array(
		'ExactID'			=> $gateway['terminal_id'],	
  		'Password'			=> $gateway['password'],
		'Transaction_Type'  => '00',
	 	'Card_Number' 		=> $credit_card['card_num'],
		'Expiry_Date'		=> $credit_card['exp_month'] . substr($credit_card['exp_year'],-2,2),
		'CVD_Presence_Ind' 	=> (empty($credit_card['cvv'])) ? '9' : '1',
		'Customer_Ref' 		=> $order_id,
		'DollarAmount' 		=> $params['amount']
		  );
		
		if(isset($credit_card->cvv)) {
			$trxnProperties['VerificationStr1'] = $credit_card['cvv'];
		}  
		
		if(isset($customer['customer_id'])) {
			$trxnProperties['CardHoldersName'] = $customer['first_name'].' '.$customer['last_name'];
		} else {
			$name = explode(' ', $credit_card['card_name']);
			$trxnProperties['CardHoldersName'] = $name[0].' '.$name[1];
			
		}
		
		$trxnProperties = $this->CompleteArray($trxnProperties);
		
		$post_response = $this->Process($trxnProperties, $post_url, $order_id);
		
		if($post_response->EXact_Resp_Code == '00') {
			$response['success'] = TRUE;
			// Save the Auth information
			$CI->load->model('subscription_model');
			$CI->subscription_model->SaveApiCustomerReference($subscription_id, $post_response->Transaction_Tag);
			$CI->subscription_model->SaveApiAuthNumber($subscription_id, $post_response->Authorization_Num);
			$response['transaction_tag'] = $post_response->Transaction_Tag;
			$response['auth_num'] = $post_response->Authorization_Num;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = $post_response->EXact_Message;
		}
		
		return $response;
	}
	
	function ChargeRecurring($client_id, $gateway, $order_id, $transaction_tag, $auth_num, $params)
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

		// Create the charge
		
		$trxnProperties = array(
		'ExactID'			=> $gateway['terminal_id'],	
  		'Password'			=> $gateway['password'],
		'Transaction_Type'  => '30',
	 	'Transaction_Tag'	=> $transaction_tag,
		'Authorization_Num'	=> $auth_num,
		'Customer_Ref' 		=> $order_id,
		'DollarAmount' 		=> $params['amount']
		  );
		
		$trxnProperties = $this->CompleteArray($trxnProperties); 
		
		$post_response = $this->Process($trxnProperties, $post_url, $order_id);
		
		if($post_response->EXact_Resp_Code == '00') {
			$response['success'] = TRUE;
			// Save the Auth information
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $post_response->Authorization_Num);
		} else {
			$response['success'] = FALSE;
			$response['reason'] = $post_response->EXact_Message;
		}
		
		
		return $response;
	}
	
	function CancelRecurring($client_id, $subscription)
	{	
		return TRUE;
	}
	
	function Process($trxnProperties, $post_url, $order_id) 
	{
		$trxn = array("Transaction"=>$trxnProperties);
		
		$client = new SoapClient($post_url);
		$trxnResult = $client->__soapCall('SendAndCommit', $trxn);
		
		$this->LogResponse($order_id, $trxnResult);
		
		return $trxnResult;
	}
	
	function CompleteArray($array = array())
	{
		$complete_if_blank = array(
								"Ecommerce_Flag",
								"XID",
								"ExactID",
								"CAVV",
								"Password",
								"CAVV_Algorithm",
								"Transaction_Type",
								"Reference_No",
								"Customer_Ref",
								"Reference_3",
								"Client_IP",		
								"Client_Email",		
								"Language",	
								"Card_Number",
								"Expiry_Date",
								"CardHoldersName",
								"Track1",
								"Track2",
								"Authorization_Num",
								"Transaction_Tag",
								"DollarAmount",
								"VerificationStr1",
								"VerificationStr2",
								"CVD_Presence_Ind",
								"Secure_AuthRequired",
								"Secure_AuthResult",
								
								// Level 2 fields 
								"ZipCode",
								"Tax1Amount",
								"Tax1Number",
								"Tax2Amount",
								"Tax2Number",
								
								"SurchargeAmount",	//Used for debit transactions only
								"PAN"
							);
							
		while (list(,$v) = each($complete_if_blank)) {
			if (!key_exists($v, $array)) {
				$array[$v] = '';
			}
		}
		
		return $array;
	}
	
	function LogResponse($order_id, $response)
	{		
		$response_array = array(
		'ExactID',
		'Password',
		'Transaction_Type',
		'DollarAmount',
		'SurchargeAmount',
		'Transaction_Tag',
		'PAN',
		'Expiry_Date',
		'CardHoldersName',
		'VerificationStr1',
		'CVD_Presence_Ind',
		'Secure_AuthRequired',
		'Secure_AuthResult',
		'Ecommerce_Flag',
		'CAVV_Algorithm',
		'Customer_Ref',
		'LogonMessage',
		'Error_Number',
		'Error_Description',
		'Transaction_Error',
		'Transaction_Approved',
		'EXact_Resp_Code',
		'EXact_Message',
		'MerchantName',
		'MerchantAddress',
		'MerchantCity',
		'MerchantProvince',
		'MerchantCountry',
		'MerchantPostal',
		'MerchantURL',
		'CTR'
		);
		
		foreach($response_array as $item) {
			if(isset($response->$item)) {
				$insert_data[$item] = $response->$item;
			} else {
				$insert_data[$item] = '';
			}
		}
		
		$insert_data['order_id'] = $order_id;
		$insert_data['Card_Number'] = substr($response->Card_Number,-4);
		
		$CI =& get_instance();
		$CI->log_model->LogApiResponse('exact', $insert_data);
		
	}
	
}
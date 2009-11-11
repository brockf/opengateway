<?php
class authnet
{
	function Charge($client_id, $order_id, $gateway, $customer, $params)
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
			"x_card_num"		=> $params['card_num'],
			"x_exp_date"		=> $params['exp_month'].$params['exp_year'],
		
			"x_amount"			=> $params['amount'],
			"x_description"		=> $params['description'],
		
			"x_first_name"		=> $customer['first_name'],
			"x_last_name"		=> $customer['last_name'],
			"x_address"			=> $customer['address_1'].'-'.$customer['address_2'],
			"x_state"			=> $customer['state'],
			"x_zip"				=> $customer['postal_code']
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		return $response;
	}
	
	function Process($order_id, $post_url, $post_string)
	{
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
		
		$CI =& get_instance();
		$CI->load->library('response');
		
		// Get the response.  1 for the first part meant that it was successful.  Anything else and it failed
		if($response[0] == 1) {
			$response = $CI->response->TransactionResponse(1);
		} else {
			$response = $CI->response->TransactionResponse(2);
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
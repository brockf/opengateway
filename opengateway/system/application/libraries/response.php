<?php

class Response
{
	function FormatResponse ($array = '')
	{
		//Load the CI object
		$CI =& get_instance();
		
		//Check to make sure an array was passed
		if(is_array($array))
		{
			//Load the XML library
			$CI->load->library('xml');
			
			//Loop through the array and add it to our response array
			foreach($array as $key => $value)
			{
				$response['response'][$key] = $value; 
			}
			//Format the XML
			$CI->xml->setArray($response);
			$response = $CI->xml->outputXML('return');
			
			//Return it
			return $response;

		}
		else
		{
			return FALSE;
		}
	}
	
	// return the transaction response
	function TransactionResponse($code, $response_array = FALSE)
	{
		if (!$code) {
			$this->SystemError('Response code not passed to function.');
		}
		
		$response = array(
							'1' => 'Transaction approved.',
							'2' => 'Transaction declined'
							);
		
				
		$responses = array(
							'response_code' => $code,
							'response_text' => $response[$code]
							);
							
		if($response_array) {
			$response = array_merge($responses, $response_array);
		}
							
		return $response;
	}
	
	// return a formatted error response to the client
	function Error ($code) {
		if (!$code) {
			$this->SystemError('Error code not passed to function.');
		}
		
		$errors = array(
							'1000' => 'Invalid request.',
							'1001' => 'Unable to authenticate.',
							'1002' => 'Invalid request type.',
							'1003' => 'Required fields are missing.',
							'1004' => 'Required fields are missing for this request',
							'2000' => 'Client is not authorized to create new clients.',
							'2001' => 'Invalid External API.',
							'3000' => 'Invalid gateway ID for this client.',
							'3001' => 'Gateway ID is required.',
							'4000' => 'Invalid customer ID.',
							'4001' => 'Inavlid Order ID.'
							);
		
				
		$error_array = array(
							'error' => $code,
							'error_text' => $errors[$code]
							);
				
		return $this->FormatResponse($error_array);
	}
	
	// a system error, not a client error
	function SystemError ($text) {
		log_message('error','Error code not passed to function.');
		echo $this->Error('01','System error.');
		die();
	}
}

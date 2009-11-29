<?php

class Response
{
	function FormatResponse ($array = '', $format = 'xml')
	{
		// Load the CI object
		$CI =& get_instance();
		
		// Check to make sure an array was passed
		if(is_array($array))
		{
			// Loop through the array and add it to our response array
			foreach($array as $key => $value)
			{
				$response[$key] = $value; 
			}
			
			// check the format
			$format = (empty($format)) ? 'xml' : $format;
			
			if ($format == 'xml') {
				//Load the XML library
				$CI->load->library('arraytoxml');

				$response = $CI->arraytoxml->toXML($response, 'response');
			}
			elseif ($format == 'php') {
				$response = serialize($response);
			}
			elseif ($format == 'json') {
				$response = json_encode($response);
			}
			else {
				return $this->FormatResponse($this->Error(1006));
			}
			
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
							'2' => 'Transaction declined',
							'100' => 'Subscription created.',
							'101' => 'Subscription cancelled.'
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
						'1005' => 'Gateway type is required.',
						'1006' => 'Invalid format passed.  Acceptable formats: xml, php, and json.',
						'2000' => 'Client is not authorized to create new clients.',
						'2001' => 'Invalid External API.',
						'3000' => 'Invalid gateway ID for this client.',
						'3001' => 'Gateway ID is required.',
						'4000' => 'Invalid customer ID.',
						'4001' => 'Invalid Order ID.',
						'5000' => 'Not a valid recurring subscription.',
						'5001' => 'Start date cannot be in the past.',
						'5002' => 'End date cannot be in the past',
						'5003' => 'End date must be later than start date.',
						'5004' => 'A customer ID or cardholder name must be supplied.',
						'5005' => 'Error creating customer profile.',
						'5006' => 'Error creating customer payment profile.',
						'6000' => 'A valid Charge ID is required.'
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

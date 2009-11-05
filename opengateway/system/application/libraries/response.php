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
	
	// return a formatted error response to the client
	function Error ($code) {
		if (!$code) {
			die(show_error('No error code supplied.'));
		}
		
		$errors = array(
					'1000' => 'Invalid request.',
					'1001' => 'Unable to authenticate.'
				);
				
		$error_array = array(
					'error' => $code,
					'error_text' => $errors[$code]
					);
				
		return $this->FormatResponse($error_array);
	}
}

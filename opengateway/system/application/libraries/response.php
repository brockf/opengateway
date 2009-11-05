<?php
class Response
{
	function format_response($array = '')
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
}
?>
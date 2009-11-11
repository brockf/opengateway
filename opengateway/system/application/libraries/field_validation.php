<?php

class Field_validation
{
	function ValidateRequiredFields($request_type, $params)
	{
		// Load the CI object
		$CI =& get_instance();
		
		$error = FALSE;
		
		// Validate that all required fields are present
		$CI->load->model('request_type_model');
		$required_fields = $CI->request_type_model->GetRequiredFields($request_type);
		
		$params = array_keys($params);
		
		if($required_fields) {
			foreach($required_fields as $required_value)
			{
				foreach($required_value as $key => $value)
				{
					if(!in_array($value, $params)) {
							$error = TRUE;
					}
				}
			}
		}	 
		if($error) {
			die($CI->response->Error(1003));
		} else {
			return TRUE;
		}
	}
	
function ValidateRequiredGatewayFields($gateway_type, $params)
	{
		// Load the CI object
		$CI =& get_instance();
		
		$error = FALSE;
		
		// Validate that all required fields are present
		$CI->load->model('gateway_model');
		$required_fields = $CI->gateway_model->GetRequiredGatewayFields($gateway_type);
		
		$params = array_keys($params);
		
		foreach($required_fields as $required_value)
		{
			foreach($required_value as $key => $value)
			{
				if(!in_array($value, $params)) {
						$error = TRUE;
				}
			}
		}
			 
		if($error) {
			die($CI->response->Error(1004));
		} else {
			return TRUE;
		}
	}
}
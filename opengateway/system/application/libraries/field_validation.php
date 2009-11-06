<?php

class Field_validation
{
	function ValidateRequiredFields($request_type, $params)
	{
		//Load the CI object
		$CI =& get_instance();
		
		$error = FALSE;
		
		//Create an array from the params object
		foreach($params as $key => $value)
		{
			$fields[] = $key;
		}
		
		// Validate that all required fields are present
		$CI->load->model('request_type_model');
		$required_fields = $CI->request_type_model->GetRequiredFields($request_type);
		
		foreach($required_fields as $required_value)
		{
			foreach($required_value as $key => $value)
			{
				if(!in_array($value, $fields)) {
						$error = TRUE;
					}
			}
		}
			 
		if($error) {
			die($CI->response->Error(1003));
		} else {
			return TRUE;
		}
	}
}
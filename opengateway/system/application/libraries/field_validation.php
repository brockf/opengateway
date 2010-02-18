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
		
		$param_keys = array_keys($params);
		
		if($required_fields) {
			foreach($required_fields as $required_value)
			{
				foreach($required_value as $key => $value)
				{
					if(!in_array($value, $param_keys)) {
						$error = TRUE;
					}
					
					if(!isset($params[$value]) or $params[$value] == '') {
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
	
	function ValidateRequiredGatewayFields($required_fields, $params)
	{
		$CI =& get_instance();
		
		$error = FALSE;
		
		$params = array_keys($params);
		
		foreach($required_fields as $required_value)
		{
			if(!in_array($required_value, $params)) {
					$error = TRUE;
			}
		}
			 
		if($error) {
			die($CI->response->Error(1004));
		} else {
			return TRUE;
		}
	}
	
	function ValidateCountry($country_code)
	{
		$CI =& get_instance();
		
		$CI->db->where('iso2', $country_code);
		$query = $CI->db->get('countries');
		if($query->num_rows() > 0) {
			return $query->row()->country_id;
		} else {
			return FALSE;
		}
	}
	
	function ValidateEmailAddress($email)
	{
		return preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $email);
	}
	
	function ValidateCreditCard($card_number, $gateway)
	{	
		$patterns = array();
		
		if($gateway['accept_amex'] == 1) {
			$patterns['amex'] = "/^([34|37]{2})([0-9]{13})$/";
		}
		
		if($gateway['accept_discover'] == 1) {
			$patterns['disc'] = "/^([6011]{4})([0-9]{12})$/";
		}
		
		if($gateway['accept_visa'] == 1) {
			$patterns['visa'] = "/^([4]{1})([0-9]{12,15})$/";
		}
		
		if($gateway['accept_mc'] == 1) {
			$patterns['mc'] = "/^([51|52|53|54|55]{2})([0-9]{14})$/";
		}
		
		if($gateway['accept_dc'] == 1) {
			$patterns['dc'] = "/^([30|36|38]{2})([0-9]{12})$/";
		}
		
		foreach($patterns as $key => $value) {
			if(preg_match($value, $card_number)) {
				return $key;
			}
		}

		return FALSE;
		
	}
	
	function ValidateAmount($amount)
	{
		if(!is_numeric($amount)) {
			return FALSE;
		}
		
		if($amount < 0) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	function ValidateDate($date)
	{
		//Check the length of the entered Date value
		if((strlen($date) < 10) OR (strlen($date) > 10)) {
			return FALSE;
		}
		
		//The entered value is checked for proper Date format
		if((substr_count($date, '-')) != 2) {
			return FALSE;
		}
		
		$pos = explode('-', $date);
		$year = $pos[0];
		$result = preg_match('/^\d+$/', $year);
		
		if(!$result) {
			return FALSE;
		}
		
		if(($year < 1900) OR ($year > 2200)) {
			return FALSE;
		}
		
		$month = $pos[1];
		if(($month <= 0) OR ($month > 12)) {
			return FALSE;
		}
		
		$result = preg_match('/^\d+$/', $month);
		
		if(!$result) {
			return FALSE;
		}
		
		$day = $pos[2];
		if(($day <= 0) OR ($day > 31)) {
			return FALSE;
		}
		
		$result = preg_match('/^\d+$/', $day);
		
		if(!$result) {
			return FALSE;
		}
		
		return TRUE;
		
		
	}
}
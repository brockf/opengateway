<?php

class OpenGateway
{		
	function Authenticate ($api_id, $secret_key, $server ='https://platform.opengateway.net') 
	{
		$this->api_id = $api_id;
		$this->secret_key = $secret_key;
		$this->post_url = $server;
	}


	function SetMethod($method) 
	{
		$this->method = ucwords($method);
	}
	
    function Param($name, $value, $parent = FALSE) 
    {
           if($parent) {
           $this->params->$parent->$name = (string)$value;
           } else {
           $this->params->$name = (string)$value;
           }
    }

	function Process($debug = FALSE) 
	{
	    // See which params are set
	    $i=0;
	    if(isset($this->params)) {
		    foreach($this->params as $key => $value)
		    {
		      	if(is_object($value))
		      	{
		      		$xml_params[$i] = '<'.$key.'>';
		      		foreach($value as $key1 => $value1)
		      		{
		      			$xml_params[$i] .= '<'.strtolower($key1).'>'.$value1.'</'.strtolower($key1).'>';
		      		}
		      		$xml_params[$i] .= '</'.$key.'>';
		      	} else {
		      		$xml_params[$i] = '<'.strtolower($key).'>'.$value.'</'.strtolower($key).'>';
		      	}
		      	
		      	$i++;
		    }
	    }
			
	      
	    // put our XML together
	    $xml = '<?xml version="1.0" encoding="UTF-8"?><request>';
	    if(isset($this->api_id) AND isset($this->secret_key)) {
	    	$xml .= '<authentication><api_id>'.$this->api_id.'</api_id><secret_key>'.$this->secret_key.'</secret_key></authentication>';
	    }
		
	    if(isset($this->method)) {
	    	$xml .= '<type>'.$this->method.'</type>';
	    }
	    
	    if(isset($xml_params)) {
	    	foreach($xml_params as $xml_param)
	    	{
	    		$xml .= $xml_param;
	    	}
	    }

	    $xml .= '</request>';
	    
	    if($debug)
	    {
	    	$xml = simplexml_load_string($xml);
	    	$doc = new DOMDocument('1.0');
        	$doc->preserveWhiteSpace = false;
        	$doc->loadXML($xml->asXML());
        	$doc->formatOutput = true;
        	echo $doc->saveXML();
	    }
	    
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($ch, CURLOPT_URL, $this->post_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml); 
		
		
		$data = curl_exec($ch); 
		
		if(curl_errno($ch))
		{
		    print curl_error($ch);
		}
		else
		{
			curl_close($ch);
		    return $data;
		}
	}
			 
}	

class Recur extends OpenGateway 
{
	
	function Amount($amount) 
	{
		$this->Param('amount', $amount);
	}
	
	function CreditCard($name, $number, $exp_month, $exp_year, $security_code = FALSE) 
	{
		$this->Param('name', $name, 'credit_card');
		$this->Param('card_num', $number, 'credit_card');
		$this->Param('exp_month', $exp_month, 'credit_card');
		$this->Param('exp_year', $exp_year, 'credit_card');
		if($security_code) {
			$this->Param('cvv', $security_code, 'credit_card');
		}
	}
	
	function Customer($firstname, $lastname, $company, $address_1, $address_2, $city, $state, $country, $postal_code, $phone, $email) 
	{
		$this->Param('first_name', $firstname, 'customer');
		$this->Param('last_name', $lastname, 'customer');
		$this->Param('company', $company, 'customer');
		$this->Param('address_1', $address_1, 'customer');
		$this->Param('address_2', $address_2, 'customer');
		$this->Param('city', $city, 'customer');
		$this->Param('state', $state, 'customer');
		$this->Param('country', $country, 'customer');
		$this->Param('postal_code', $postal_code, 'customer');
		$this->Param('phone', $phone, 'customer');
		$this->Param('email', $email, 'customer');
	}
	
	function UsePlan($plan_id) 
	{
		$this->Param('plan_id', $plan_id, 'recur');
	}
	
	function UseGateway($gateway_id) 
	{
		$this->Param('gateway_id', $gateway_id);
	}
	
	function Schedule($interval, $occurrences, $free_trial, $start_date, $end_date = FALSE) 
	{
		$this->Param('interval', $interval, 'recur');
		$this->Param('free_trial', $free_trial, 'recur');
		$this->Param('occurrences', $occurrences, 'recur');
		$this->Param('start_date', $start_date, 'recur');
		if($end_date) {
			$this->Param('end_date', $end_date, 'recur');
		}
		
	}
	
	function Charge($debug = FALSE) 
	{
		$this->SetMethod('Recur');
		$this->Process($debug);
	}
}
	
class Charge extends OpenGateway 
{
	function Amount($amount) 
	{
		$this->Param('amount', $amount);
	}
	
	function CreditCard($name, $number, $exp_month, $exp_year, $security_code = FALSE) 
	{
		$this->Param('name', $name, 'credit_card');
		$this->Param('card_num', $number, 'credit_card');
		$this->Param('exp_month', $exp_month, 'credit_card');
		$this->Param('exp_year', $exp_year, 'credit_card');
		if($security_code) {
			$this->Param('cvv', $security_code, 'credit_card');
		}
	}
	
	function Customer($firstname, $lastname, $company, $address_1, $address_2, $city, $state, $country, $postal_code, $phone, $email) 
	{
		$this->Param('first_name', $firstname, 'customer');
		$this->Param('last_name', $lastname, 'customer');
		$this->Param('company', $company, 'customer');
		$this->Param('address_1', $address_1, 'customer');
		$this->Param('address_2', $address_2, 'customer');
		$this->Param('city', $city, 'customer');
		$this->Param('state', $state, 'customer');
		$this->Param('country', $country, 'customer');
		$this->Param('postal_code', $postal_code, 'customer');
		$this->Param('phone', $phone, 'customer');
		$this->Param('email', $email, 'customer');
	}
	
	function UsePlan($plan_id) 
	{
		$this->Param('plan_id', $plan_id, 'recur');
	}
	
	function UseGateway($gateway_id) 
	{
		$this->Param('gateway_id', $gateway_id);
	}
	
	function Charge($debug = FALSE) 
	{
		$this->SetMethod('Charge');
		$this->Process($debug);
	}
}

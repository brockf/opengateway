<?php

class Email
{
    function TriggerTrip($trigger_type_id, $client_id, $plan_id = false, $customer = false, $subscription = false, $charge = false)
    {
    	$CI =& get_instance();
    	// check to see if they have an email specified
    	$CI->load->model('email_model');
    	$email = $CI->email_model->GetEmail($client_id, $trigger_type_id);
    	
    	if(!$email) {
    		return FALSE;
    	}
    	
    	// Get the available variables
    	$vars = unserialize($email->available_variables);
    	
    	$message = $email->email_body;
    	
    	foreach($vars as $var) {
    		//echo '[['.strtoupper($var).']]';
    		$var = strtoupper($var);
    		echo (preg_match('/[['.$var.']]/', $message)) ? "yes": "no";
    	}
    	
    	//str_replace("/this/", "1000", $message);
    	
    	echo $message;
    	
    	//echo gettype($message);
    	
    	//print_r($vars);
    	
    }
}
<?php

function TriggerTrip($trigger_type, $client_id, $charge_id = false, $subscription_id = false, $customer_id = false)
{
	$CI =& get_instance();
	$CI->load->model('email_model');
	
	// get trigger ID
	$trigger_type_id = $CI->email_model->GetTriggerId($trigger_type);
	
	if (!$trigger_type_id) {
		return FALSE;
	}
	
	// load all available data
    if ($subscription_id) {
    	$CI->load->model('recurring_model');
    	$subscription = $CI->recurring_model->GetRecurring($client_id, $subscription_id);
    	
    	if (isset($subscription['customer']['id'])) {
    		$customer_id = $subscription['customer']['id'];
    	}
    }
    
    if ($charge_id) {
    	$CI->load->model('charge_model');
    	$charge = $CI->charge_model->GetCharge($client_id, $charge_id);
    	
    	if (isset($charge['customer']['id'])) {
    		$customer_id = $charge['customer']['id'];
    	}
    }
    
    if ($customer_id) {
    	$CI->load->model('customer_model');
    	$customer = $CI->customer_model->GetCustomer($client_id, $customer_id);
    }
    
    // dynamically get customer information for charge-related trips to save on SQL queries
    if (isset($subscription) and is_array($subscription['customer'])) {
    	$customer = $subscription['customer'];
    }
    elseif (isset($charge) and isset($charge['customer']) and is_array($charge['customer'])) {
    	$customer = $charge['customer'];
    }
    
    // dynamically get plan-related info for recurring-related stuff
    if ($subscription_id and isset($subscription['plan'])) {
    	if (is_array($subscription['plan'])) {
    		$plan = $subscription['plan'];
    		$plan_id = $plan['id'];
    	}
    }
    
    if (!isset($plan_id)) {
    	$plan_id = false;
    }
    
    // build array of all possible variables, if they exist
	$variables = array();
	
	if (isset($charge) and is_array($charge)) {
		$variables['amount'] = $charge['amount'];
		$variables['date'] = date("Y-m-d h:i");
		$variables['charge_id'] = $charge['id'];
		$variables['card_last_four'] = $charge['card_last_four'];
	}
	
	if (isset($subscription) and is_array($subscription)) {
		$variables['recurring_id'] = $subscription['id'];
		$variables['start_date'] = $subscription['start_date'];
		$variables['end_date'] = $subscription['end_date'];
		$variables['expiry_date'] = $subscription['end_date'];
		$variables['next_charge_date'] = $subscription['next_charge_date'];
		$variables['card_last_four'] = $subscription['card_last_four'];
		
		if (isset($plan) and is_array($plan)) {
			$variables['plan_id'] = $plan['id'];
			$variables['plan_name'] = $plan['name'];
		}
		
		if (!isset($variables['amount'])) {
			$variables['amount'] = $subscription['amount'];
		}
		
		// if this is a delayed recurring charge, then there won't be a 
		// date variable yet... so let's create one
		if (!isset($variables['date'])) {
			$variables['date'] = date('Y-m-d h:i');
		}
	}
	
	if (isset($customer) and is_array($customer)) {
		$variables['customer_id'] = $customer['id'];
		$variables['customer_first_name'] = $customer['first_name'];
		$variables['customer_last_name'] = $customer['last_name'];
		$variables['customer_internal_id'] = $customer['internal_id'];
		$variables['customer_company'] = $customer['company'];
		$variables['customer_address_1'] = $customer['address_1'];
		$variables['customer_address_2'] = $customer['address_2'];
		$variables['customer_city'] = $customer['city'];
		$variables['customer_state'] = $customer['state'];
		$variables['customer_postal_code'] = $customer['postal_code'];
		$variables['customer_country'] = $customer['country'];
		$variables['customer_email'] = $customer['email'];
		$variables['customer_phone'] = $customer['phone'];
	}
	
	if (!isset($variables['amount'])) {
		$variables['amount'] = '0.00';
	}
	
	// which events should go in the client log?
	$loggable = array(1,2,3,4,9,10);
	
	// log for the client if it's loggable
	if (in_array($trigger_type_id,$loggable)) {
	    $CI->load->model('log_model');
	    $CI->log_model->ClientLog($client_id, $trigger_type_id, $variables);
	}
	    	
	// just in case, we'll grab the email of the client
	$CI->load->model('client_model');
	$client = $CI->client_model->GetClientDetails($client_id);
	$client_email = $client->email;
	$secret_key = $client->secret_key; // for notification security
	
	// notification_url needs triggering too, if it exists
	
	// check for plan notification_url
	$notification_url = FALSE;
	if (isset($plan) and is_array($plan) and !empty($plan['notification_url'])) {
		$notification_url = $plan['notification_url'];
	}
	elseif (isset($subscription) and is_array($subscription) and !empty($subscription['notification_url'])) {
		$notification_url = $subscription['notification_url'];
	}
	
    if (!empty($notification_url)) {
		$CI->load->library('notifications');
		
		// build var array
		$array = array(
					'action' => $trigger_type,
					'client_id' => $client_id,
					'secret_key' => $secret_key
				);
		
		if (isset($variables['plan_id'])) {
			$array['plan_id'] = $variables['plan_id'];
		}
		if (isset($variables['customer_id'])) {
			$array['customer_id'] = $variables['customer_id'];
		}
		if (isset($variables['charge_id'])) {
			$array['charge_id'] = $variables['charge_id'];
		}
		if (isset($variables['recurring_id'])) {
			$array['recurring_id'] = $variables['recurring_id'];
		}
			
		$CI->notifications->QueueNotification($notification_url,$array);
    }
    
    // should we send a receipt, even if it's free?
    $test_amount = (isset($variables['amount'])) ? (float)$variables['amount'] : FALSE;
	if (empty($test_amount) and in_array($trigger_type, array('recurring_charge','charge')) and $CI->config->item('no_receipt_for_free_charges') === TRUE) {
		log_message('debug','No email sent for ' . $trigger_type . ' because amount was 0 (config item set to TRUE).');
		return TRUE;
	}
	
    // check to see if this triggers any emails for the client
	$emails = $CI->email_model->GetEmailsByTrigger($client_id, $trigger_type_id, $plan_id);

	if(!$emails) {
		return FALSE;
	}	
	
	// load validation
	$CI->load->library('field_validation');
	
	$email_count = 0;
	
	foreach ($emails as $email) {		
		// is this HTML?
		$config['mailtype'] = ($email['is_html'] == '1') ? 'html' : 'text';
		$config['wordwrap'] = ($email['is_html'] == '1') ? FALSE : TRUE;
		$CI->email->initialize($config);
		
		// who is this going to?
		$to_address = false;
		if ($email['to_address'] == 'customer' and isset($customer['email']) and !empty($customer['email']) and $CI->field_validation->ValidateEmailAddress($customer['email'])) {
			$to_address = $customer['email'];
		}
		elseif ($email['to_address'] == 'client') {
			$to_address = $client_email;
		}
		elseif ($CI->field_validation->ValidateEmailAddress($email['to_address'])) {
			$to_address = $email['to_address'];
		}
		
		if ($to_address) {	
			$subject = $email['email_subject'];
			$body = $email['email_body'];
			$from_name = $email['from_name'];
			$from_email = $email['from_email'];
			
			// make email variables available globally
			$GLOBALS['EMAIL_TRIGGER_VARIABLES'] = serialize($variables);
			
			// replace all possible variables that have parameter
			$body = preg_replace_callback('/\[\[([a-zA-Z_]*?)\|\"(.*?)\"\]\]/i', 'trigger_parse_variable_with_parameter', $body);
			$subject = preg_replace_callback('/\[\[([a-zA-Z_]*?)\|\"(.*?)\"\]\]/i', 'trigger_parse_variable_with_parameter', $subject);
			
			// replace all possible variables
			while (list($name,$value) = each($variables)) {
				$subject = str_ireplace('[[' . $name . ']]',$value,$subject);
				$body = str_ireplace('[[' . $name . ']]',$value,$body);
			}
			reset($variables);
			
			// send the email
			$CI->email->from($from_email, $from_name);
			$CI->email->to($to_address);
			$CI->email->subject($subject);
			$CI->email->message($body);
			
			$CI->email->send();
			
			// send a BCC?
			$send_bcc = false;
			if (!empty($email['bcc_address'])) {
				if ($email['bcc_address'] == 'client') {
					$send_bcc = $client_email;
				}
				elseif ($CI->field_validation->ValidateEmailAddress($email['bcc_address'])) {
					$send_bcc = $email['bcc_address'];
				}
			}
			
			if ($send_bcc != false) {
				$CI->email->from($from_email, $from_name);
				$CI->email->to($send_bcc);
				$CI->email->subject($subject);
				$CI->email->message($body);
				$CI->email->send();
			}
		}
		
		$email_count++;		
	}
	
	return $email_count;
}

// replaces a variable that is being modified with a parameter
// for now, this is only date parameters
function trigger_parse_variable_with_parameter ($params) {
	// load $variables array
	$variables = unserialize($GLOBALS['EMAIL_TRIGGER_VARIABLES']);
	
	$variable = $params[1];
	$parameter = $params[2];
	
	$array_key = strtolower($variable);
	
	$return = $variables[$array_key];
	
	// format the date
	// we'll take strftime or date formatting:
	if (strpos($parameter, '%') !== FALSE) {				
		$return = strftime($parameter, strtotime($return));
	}
	else {
		$return = date($parameter, strtotime($return));
	}
	
	return $return;
}
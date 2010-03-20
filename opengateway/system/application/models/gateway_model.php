<?php
/**
* Gateway Model 
*
* Contains all the methods used to create and manage client gateways, process credit card charges, and create recurring subscriptions.
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/

class Gateway_model extends Model
{	
	function Gateway_model()
	{
		parent::Model();
	}
	
	
	/**
	* Create a new gateway instance
	*
	* Creates a new gateway instance in the client_gateways table.  Inserts the different gateway paramaters into the 
	* client_gateway_params table.  These are not declared in this documentation as they can be anything.  Returns the resulting gateway_id.
	*
	* @param int $client_id	The Client ID
	* @param string $params['gateway_type'] The type of gateway to be created (authnet, exact etc.)
	* @param int $params['accept_mc'] Whether the gateway will accept Mastercard
	* @param int $params['accept_visa'] Whether the gateway will accept Visa
	* @param int $params['accept_amex'] Whether the gateway will accept American Express
	* @param int $params['accept_discover'] Whether the gateway will accept Discover
	* @param int $params['accept_dc'] Whether the gateway will accept Diner's Club
	* @param int $params['enabled'] Whether the gateway is enabled or disabled
	* 
	* @return int New Gateway ID
	*/
	
	function NewGateway($client_id, $params)
	{
		// Get the gateway type
		if(!isset($params['gateway_type'])) {
			die($this->response->Error(1005));
		}
		
		$gateway_type = $params['gateway_type'];
		
		// Validate the required fields
		$this->load->library('payment/'.$gateway_type);
		$settings = $this->$gateway_type->Settings();
		$required_fields = $settings['required_fields'];
		$this->load->library('field_validation');
		$validate = $this->field_validation->ValidateRequiredGatewayFields($required_fields, $params);
		
		// Get the external API id
		$external_api_id = $this->GetExternalApiId($gateway_type);
		
		// Create the new Gateway
		
		$create_date = date('Y-m-d');
		
		$insert_data = array(
							'client_id' 		=> $client_id,
							'external_api_id' 	=> $external_api_id,
							'enabled'			=> $params['enabled'],
							'create_date'		=> $create_date
							);  
		
		$this->db->insert('client_gateways', $insert_data);
		
		$new_gateway_id = $this->db->insert_id();
		
		// Add the params, but not the client id or gateway type
		unset($params['authentication']);
		unset($params['client_id']);
		unset($params['gateway_type']);
		unset($params['enabled']);
		unset($params['type']);
		
		$this->load->library('encrypt');
		
		foreach($params as $key => $value)
		{
			$insert_data = array(
								'client_gateway_id'	=> $new_gateway_id,
								'field' 			=> $key,
								'value'				=> $this->encrypt->encode($value)
								);  
		
			$this->db->insert('client_gateway_params', $insert_data);
		}
		
		// If there is not default gateway, we'll set this one.
		$CI =& get_instance();
		$CI->load->model('client_model');
		$client = $CI->client_model->GetClientDetails($client_id);
		
		if($client->default_gateway_id == 0) {
			$this->MakeDefaultGateway($client_id, $new_gateway_id);
		}
		
		return $new_gateway_id;

	}
	
	/**
	* Process a credit card charge
	*
	* Processes a credit card CHARGE transaction using the gateway_id to use the proper client gateway.
	* Returns an array response from the appropriate payment library
	*
	* @param int $client_id	The Client ID
	* @param int $params['gateway_id'] The client_gateway used to process the charge
	* @param int $params['customer_id'] The customer ID.  Required only if a cardholder name is not supplied
	* @param int $params['credit_card']['card_num'] The credit card number
	* @param int $params['credit_card']['exp_month'] The credit card expiration month in 2 digit format (01 - 12)
	* @param int $params['credit_card']['exp_year'] The credit card expiration year (YYYY)
	* @param int $params['credit_card']['name'] The credit card cardholder name.  Required only is customer ID is not supplied.
	* @param int $params['credit_card']['cvv'] The Card Verification Value.  Optional
	* @param int $params['amount'] The amount to be charged.  
	* 
	* @return mixed Array with response_code and response_text
	*/
	function Charge($client_id, $params)
	{
		// Make sure it came from a secure connection
		if(empty($_SERVER["HTTPS"]) and $this->config->item('ssl_active') == TRUE) {
			die($this->response->Error(1010));
		}
		
		if(isset($params['gateway_id'])) {
			$gateway_id = $params['gateway_id'];
		} else {
			$gateway_id = FALSE;
		}
		
		$CI =& get_instance();
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('Charge', $params);
		
		// Get the credit card object
		$credit_card = $params['credit_card'];
		
		// Get the gateway info to load the proper library
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
		$params['gateway_id'] = $gateway['gateway_id'];
		
		if ($gateway['enabled'] == '0') {
			die($this->response->Error(5017));
		}
		
		// Validate the Credit Card number
		$params['card_type'] = $this->field_validation->ValidateCreditCard($credit_card['card_num'], $gateway);
		
		if(!$params['card_type']) {
			die($this->response->Error(5008));
		}
		
		// Validate the amount
		$valid_amount = $this->field_validation->ValidateAmount($params['amount']);
		
		if(!$valid_amount) {
			die($this->response->Error(5009));
		}
		
		// Get the customer details if a customer id was included
		if(isset($params['customer_id'])) {
			$CI->load->model('customer_model');
			$customer = $CI->customer_model->GetCustomer($client_id, $params['customer_id']);
			$customer['customer_id'] = $customer['id'];
			$created_customer = false;
		}
		elseif (isset($params['customer']) and is_array($params['customer'])) {
			$CI->load->model('customer_model');
			// look for embedded customer information
			$customer = $params['customer'];
			$customer['customer_id'] = $CI->customer_model->NewCustomer($client_id, $customer);
			$created_customer = true;
		}
		else {
			$customer = array();
		}
		
		// Create a new order
		$CI->load->model('order_model');
		$passed_customer = (isset($customer['customer_id'])) ? $customer['customer_id'] : false;
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params, false, $passed_customer);
		
		// if amount is greater than $0, we require a gateway
		if ($params['amount'] > 0) {
			// Load the proper library
			$gateway_name = $gateway['name'];
			$this->load->library('payment/'.$gateway_name);
			$response = $this->$gateway_name->Charge($client_id, $order_id, $gateway, $customer, $params, $credit_card);	
		}
		else {
			// it's a charge of $0, it's ok
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		
		if (isset($created_customer) and $created_customer == true and ($response['response_code'] != 1)) {
			$CI->customer_model->DeleteCustomer($client_id, $customer['customer_id']);
		}
		elseif (isset($created_customer)) {
			$response['customer_id'] = $customer['customer_id'];
		}
		
		// If it was successful, send an email
		if($response['response_code'] == 1) {
			$this->order_model->SetStatus($order_id, 1);
			TriggerTrip('charge', $client_id, $response['charge_id']);
		} else {
			$this->order_model->SetStatus($order_id, 0);
		}
		
		return $response;
	}
	
	function Refund($client_id, $params)
	{
		// Make sure it came from a secure connection
		if(empty($_SERVER["HTTPS"])) {
			die($this->response->Error(1010));
		}
		
		if(!isset($params['gateway_id'])) {
			die($this->response->Error(3001));
		}
		
		$CI =& get_instance();
		
		// Get the order details
		$CI->load->model('order_model');
		$order = $CI->order_model->GetCharge($client_id, $params['order_id']);
		$order_id = $order['id'];
		
		$params['order'] = $order;
		
		// Get the credit card object
		if(isset($params['credit_card'])) {
			$credit_card = $params['credit_card']; 
		} else {
			$credit_card = FALSE;
		}
			
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('Refund', $params);
		
		// Get the gateway info to load the proper library
		$gateway_id = $params['gateway_id'];
		$CI->load->model('gateway_model');
		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $gateway_id);
		
		// Get the customer details
		$CI->load->model('customer_model');
		$customer = $CI->customer_model->GetCustomer($client_id, $params['customer_id']);
		$customer['customer_id'] = $customer;
		
		// Get the order authorization
		$CI->load->model('order_authorization_model');
		$params['authorization'] = $CI->order_authorization_model->getAuthorization($order_id);
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->Refund($client_id, $order_id, $gateway, $customer, $params, $credit_card);
	}
	
	
	/**
	* Create a new recurring subscription.
	*
	* Creates a new recurring subscription and processes a charge for today.
	*
	* @param int $client_id	The Client ID
	* @param int $params['gateway_id'] The gateway_id to be used for creating the subscription.
	* @param int $params['credit_card']['card_num'] The credit card number
	* @param int $params['credit_card']['exp_month'] The credit card expiration month in 2 digit format (01 - 12)
	* @param int $params['credit_card']['exp_year'] The credit card expiration year (YYYY)
	* @param int $params['credit_card']['name'] The credit card cardholder name.  Required only is customer ID is not supplied.
	* @param int $params['credit_card']['cvv'] The Card Verification Value.  Optional
	* @param date $params['recur']['start_date'] The date the subscription should start. If no start_date is supplied, today's date will be used.  Optional.
	* @param date $params['recur']['end_date'] The date the subscription should end. If no start_date is supplied, the end_date is calculated based on a config item.  Optional.
	* @param int $params['recur']['interval'] The number of days between subscription charges.
	* @param int $params['amount'] The amount to be charged on each subscription date.
	* 
	* @return mixed Array with subscription_id
	*/
	
	function Recur($client_id, $params)
	{		
		// Make sure it came from a secure connection
		if(empty($_SERVER["HTTPS"]) and $this->config->item('ssl_active') == TRUE) {
			die($this->response->Error(1010));
		}
		
		$CI =& get_instance();
		
		if(isset($params['gateway_id'])) {
			$gateway_id = $params['gateway_id'];
		} else {
			$gateway_id = FALSE;
		}
		
		// Get the credit card object
		$credit_card = $params['credit_card'];
		
		// Get the gateway info to load the proper library
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
		$params['gateway_id'] = $gateway['gateway_id'];
		
		if ($gateway['enabled'] == '0') {
			die($this->response->Error(5017));
		}
		
		$this->load->library('field_validation');
		
		// Validate the Credit Card number
		$params['card_type'] = $this->field_validation->ValidateCreditCard($credit_card['card_num'], $gateway);
		
		if(!$params['card_type']) {
			die($this->response->Error(5008));
		}
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('Recur', $params);
		
		// Get the customer details if a customer id was included
		$this->load->model('customer_model');
		
		if(isset($params['customer_id'])) {
			$customer = $CI->customer_model->GetCustomer($client_id, $params['customer_id']);
			$customer['customer_id'] = $customer['id'];
		}
		elseif (isset($params['customer']) and is_array($params['customer'])) {
			// look for embedded customer information
			$customer = $params['customer'];
			$customer['customer_id'] = $CI->customer_model->NewCustomer($client_id, $customer);
			$created_customer = true;
		}
		else {
			// If a customer ID was not passed we need to make sure that a cardholder name was
			if(!isset($credit_card['name'])) {
				die($this->response->Error(5004));
			} else {
				$name = explode(' ', $credit_card['name']);
				$customer['first_name'] = $name[0];
				$customer['last_name'] = $name[count($name) - 1];
				$customer['customer_id'] = $CI->customer_model->SaveNewCustomer($client_id, $customer['first_name'], $customer['last_name']);
			}
		}
		
		// Get the subscription details
		if(!isset($params['recur'])) {
			die($this->response->Error(5004));
		}
		
		$recur = $params['recur'];
		
		if(isset($recur['plan_id'])) {
			$CI->load->model('plan_model');
			$plan_details = $CI->plan_model->GetPlanDetails($client_id, $recur['plan_id']);
			
			$interval 			= (isset($recur['interval'])) ? $recur['interval'] : $plan_details->interval;
			$notification_url 	= (isset($recur['notification_url'])) ? $recur['notification_url'] : $plan_details->notification_url;
			$amount 			= (isset($recur['amount'])) ? $recur['amount'] : $plan_details->amount;
			$free_trial 		= (isset($recur['free_trial'])) ? $recur['free_trial'] : $plan_details->free_trial;
			$occurrences		= (isset($recur['occurrences'])) ? $recur['occurrences'] : $plan_details->occurrences;
			
			// use plan amount if a different first amount was not given
			$params['amount'] = (isset($params['amount'])) ? $params['amount'] : $amount;
			
			$plan_id = $plan_details->plan_id;
		
			// if there are specified occurrences, we should create an end-date that works with it
			if ($occurrences != 0) {
				$total = $occurrences * ($interval*86400);
				$recur['end_date'] = date('Y-m-d',time() + ($interval*86400));
			}	
		} else {	
			if(!isset($recur['interval'])) {
				die($this->response->Error(5011));
			}
			
			if(!is_numeric($recur['interval'])) {
				die($this->response->Error(5011));
			} else {
				$interval = $recur['interval'];
			}
			
			// Check for a notification URL
			if(isset($recur['notification_url'])) {
				$notification_url = $recur['notification_url'];
			} else {
				$notification_url = '';
			}
			
			// Validate the amount
			$amount = $params['amount'];
			$valid_amount = $this->field_validation->ValidateAmount($amount);
			
			if(!$valid_amount) {
				die($this->response->Error(5009));
			}
			
			$plan_id = 0;
			$free_trial = (isset($recur['free_trial']) and is_numeric($recur['free_trial'])) ? $recur['free_trial'] : FALSE;
		}
		
		// Validate the start date to make sure it is in the future
		if(isset($recur['start_date'])) {
			// adjust to server time
			$recur['start_date'] = server_time($recur['start_date'], 'Y-m-d', true);
		
			if(!$this->field_validation->ValidateDate($recur['start_date']) or $recur['start_date'] < date('Y-m-d')) {
				die($this->response->Error(5001));
			} else {
				$start_date = date('Y-m-d', strtotime($recur['start_date']));
			}
		} else {
			$start_date = date('Y-m-d');
		}
		
		if($free_trial) {
			$start_date = date('Y-m-d', strtotime($start_date) + ($free_trial * 86400));
		}
		
		// Get the next payment date
		if (date('Y-m-d', strtotime($start_date)) == date('Y-m-d')) {
			$next_charge_date = date('Y-m-d', strtotime($start_date) + ($interval * 86400));
		}
		else {
			$next_charge_date = date('Y-m-d', strtotime($start_date));
		}
		
		// If an end date was passed, make sure it's valid
		if(isset($recur['end_date'])) {
			// adjust to server time
			$recur['end_date'] = server_time($recur['end_date']);
			
			if(strtotime($recur['end_date']) < time()) {
				die($this->response->Error(5002));
			} elseif(strtotime($recur['end_date']) < strtotime($start_date)) {
				die($this->response->Error(5003));
			} else {
				$end_date = date('Y-m-d', strtotime($recur['end_date']));
			}
		} elseif (isset($recur['occurrences'])) {
			$end_date = date('Y-m-d', strtotime($start_date) + ($interval * 86400 * ($recur['occurrences']-1)) + 86400);
		} else {
			// Find the end date based on the max end date
			$end_date = date('Y-m-d', strtotime($start_date) + ($this->config->item('max_recurring_days_from_today') * 86400));
		}
		
		// If the credit card expiration date is before the end date, we need to set the end date to one day before the expiration
		$expiry = mktime(0,0,0, $params['credit_card']['exp_month'], days_in_month($params['credit_card']['exp_month'], $params['credit_card']['exp_year']), $params['credit_card']['exp_year']);
		
		if($expiry < strtotime($end_date)) {
			$end_date = mktime(0,0,0, $params['credit_card']['exp_month'], (days_in_month($params['credit_card']['exp_month'], $params['credit_card']['exp_year']) - 1), $params['credit_card']['exp_year']);
			$end_date = date('Y-m-d', $end_date);
		}
		
		// Figure the total number of occurrences
		$total_occurrences = round((strtotime($end_date) - strtotime($start_date)) / ($interval * 86400), 0);
		
		// Save the subscription info
		$CI->load->model('subscription_model');
		$subscription_id = $CI->subscription_model->SaveSubscription($client_id, $params['gateway_id'], $customer['customer_id'], $interval, $start_date, $end_date, $next_charge_date, $total_occurrences, $notification_url, $amount, $plan_id);
		
		// set last_charge as today, if today was a charge
		if (date('Y-m-d', strtotime($start_date)) == date('Y-m-d')) {
			$CI->subscription_model->SetChargeDates($subscription_id, date('Y-m-d'), $next_charge_date);
		}
		
		// if amount is greater than 0, we require a gateway
		if ($params['amount'] > 0) {
			// load the proper library
			$gateway_name = $gateway['name'];
			$this->load->library('payment/'.$gateway_name);
			$response = $this->$gateway_name->Recur($client_id, $gateway, $customer, $params, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences);
		}
		else {
			// this is a free subscription
			if(date('Y-m-d', strtotime($start_date)) == date('Y-m-d')) {
				// Create an order for today's payment
				$CI->load->model('order_model');
				$customer['customer_id'] = (isset($customer['customer_id'])) ? $customer['customer_id'] : FALSE;
				$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $subscription_id, $customer['customer_id']);
				$CI->order_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
			}
			else {
				$response_array = array('recurring_id' => $subscription_id);
			}
			
			$response = $CI->response->TransactionResponse(100, $response_array);
		}
		
		if (isset($created_customer) and $created_customer == true and $response['response_code'] != 100) {
			$CI->customer_model->DeleteCustomer($client_id, $customer['customer_id']);
		}
		elseif (isset($created_customer) and $created_customer == true) {
			$response['customer_id'] = $customer['customer_id'];
		}
		
		if ($response['response_code'] == '100') {
			TriggerTrip('new_recurring', $client_id, $response['charge_id'], $response['recurring_id']);
		}
		
		return $response;
	}
	
	/**
	* Process a credit card recurring charge
	*
	* Processes a credit card CHARGE transaction for a recurring subscription using the gateway_id to use the proper client gateway.
	* Returns an array response from the appropriate payment library
	*
	* @param int $client_id	The Client ID
	* @param int $params['gateway_id'] The client_gateway used to process the charge
	* @param int $params['subscription_id']  The subscription_id to charge 
	* 
	* @return mixed Array with response_code and response_text
	*/
	
	function ChargeRecurring($client_id, $params)
	{
		$CI =& get_instance();
		
		$gateway_id = $params['gateway_id'];
		
		// Get the gateway info to load the proper library
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
		if (!$gateway or $gateway['enabled'] == '0') {
			return FALSE;
		}
		
		// get the credit card last four digits
		$this->db->select('card_last_four');
		$this->db->where('subscription_id',$params['subscription_id']);
		$this->db->order_by('timestamp','asc');
		$this->db->limit('1');
		$result = $this->db->get('orders');
		$row = $result->result_array();
		$params['credit_card']['card_num'] = $row[0]['card_last_four'];
		
		// Create a new order
		$CI->load->model('order_model');
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $params['subscription_id'], $params['customer_id']);
		
		if ($params['amount'] > 0) {
			// Load the proper library
			$gateway_name = $gateway['name'];
			$this->load->library('payment/'.$gateway_name);
			$response = $this->$gateway_name->AutoRecurringCharge($client_id, $order_id, $gateway, $params);
		}
		else {
			$response = array();
			$response['success'] = TRUE;
		}	

		$CI->load->model('subscription_model');
		if ($response['success'] == TRUE) {
			// Save the last_charge and next_charge
			$last_charge = date('Y-m-d');
			$next_charge = $CI->subscription_model->GetNextChargeDate($params['subscription_id']);
			
			$CI->subscription_model->SetChargeDates($params['subscription_id'], $last_charge, $next_charge);
			
			$CI->order_model->SetStatus($order_id, 1);
			
			TriggerTrip('recurring_charge', $client_id, $order_id, $params['subscription_id']);
		} else {
			$response = FALSE;
			
			// Check the number of failures allowed
			$num_allowed = $this->config->item('recurring_charge_failures_allowed');
			$failures = $params['number_charge_failures'];
			
			$CI->order_model->SetStatus($order_id, 0);
			
			$failures++;
			$CI->subscription_model->AddFailure($params['subscription_id'], $failures);
			
			if($failures >= $num_allowed) {	
				$CI->subscription_model->CancelRecurring($client_id, $params['subscription_id']);
			}
		}
		
		return $response;
	}
	
	
	/**
	* Set a default gateway.
	*
	* Sets a provided gateway_id as the default gateway for that client.
	*
	* @param int $client_id	The Client ID
	* @param int $gateway_id The gateway_id to be set as default.
	* 
	* @return bool True on success, FALSE on failure
	*/
	function MakeDefaultGateway($client_id, $gateway_id)
	{		
		// Make sure the gateway is actually theirs
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
		if(!$gateway) {
			die($this->response->Error(3000));
		}
		
		$update_data['default_gateway_id'] = $gateway_id;
				
		$this->db->where('client_id', $client_id);
		if ($this->db->update('clients', $update_data)) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
	
	/**
	* Update the Client Gateway
	*
	* Updates the client_gateway_params with supplied details
	*
	* @param int $client_id The client ID
	* @param int $params['gateway_id'] The gateway ID to update
	* @param int $params['accept_mc'] Whether the gateway will accept Mastercard
	* @param int $params['accept_visa'] Whether the gateway will accept Visa
	* @param int $params['accept_amex'] Whether the gateway will accept American Express
	* @param int $params['accept_discover'] Whether the gateway will accept Discover
	* @param int $params['accept_dc'] Whether the gateway will accept Diner's Club
	* @param int $params['enabled'] Whether the gateway is enabled or disabled
	* 
	* @return bool TRUE on success, FALSE on fail.
	*/
	
	function UpdateGateway($client_id, $params)
	{
		// Make sure the gateway is actually theirs
		$gateway = $this->GetGatewayDetails($client_id, $params['gateway_id']);
		
		if(!$gateway) {
			die($this->response->Error(3000));
		}
		
		// Validate the required fields
		$this->load->library('payment/'.$gateway['name'], $gateway['name']);
		$settings = $this->$gateway['name']->Settings();
		$required_fields = $settings['required_fields'];
		$this->load->library('field_validation');
		$validate = $this->field_validation->ValidateRequiredGatewayFields($required_fields, $params);
				
		$this->load->library('encrypt');
		
		// manually handle "enabled"
		if (isset($params['enabled']) and ($params['enabled'] == '0' or $params['enabled'] == '1')) {
			$update_data['enabled'] = $params['enabled'];
			$this->db->where('client_gateway_id', $params['gateway_id']);
			$this->db->update('client_gateways', $update_data);
			unset($update_data);
		}
				
		$i = 0;
		foreach($required_fields as $field)
		{
			if(isset($params[$field]) and $params[$field] != '') {
				$update_data['value'] = $this->encrypt->encode($params[$field]);
				$this->db->where('client_gateway_id', $params['gateway_id']);
				$this->db->where('field', $field);
				$this->db->update('client_gateway_params', $update_data);
				$i++;
			}
		}
		
		if($i === 0) {
			die($this->response->Error(6003));
		}
		
		return TRUE;
	}
	
	/**
	* Delete a gateway
	*
	* Marks a gateway as deleted and removes the authentication information from the client_gateway_params table.
	* Does not actually deleted the gateway, but sets deleted to 1 in the client_gateways table.
	*
	* @param int $client_id	The Client ID
	* @param int $gateway_id The gateway_id to be set deleted.
	* 
	* @return bool TRUE on success
	*/
	
	function DeleteGateway($client_id, $gateway_id)
	{
		$CI =& get_instance();
		
		// Make sure the gateway is actually theirs
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
		if(!$gateway) {
			die($this->response->Error(3000));
		}
		
		// cancel all subscriptions related to it
		$CI->load->model('subscription_model');
		$subscriptions = $CI->subscription_model->GetAllSubscriptionsByGatewayID($gateway_id);
		if (is_array($subscriptions)) {
			foreach ($subscriptions as $subscription) {
				$CI->subscription_model->CancelRecurring($subscription['client_id'],$subscription['subscription_id']);
			}
		}
		
		// Mark as deleted
		$update_data['deleted'] = 1;
		$this->db->where('client_gateway_id', $gateway_id);
		$this->db->update('client_gateways', $update_data);
		
		// Delete the client gateway params
		$this->db->where('client_gateway_id', $gateway_id);
		$this->db->delete('client_gateway_params');
		
		return TRUE;
	}
	
	/**
	* Get the External API ID
	*
	* Gets the External API ID from the external_apis table based on the gateway type ('authnet', 'exact' etc.)
	*
	* @param string $gateway_name The name to match with External API ID
	* 
	* @return int External API ID
	*/
	
	// Get the gateway id
	function GetExternalApiId($gateway_name = FALSE)
	{
		if($gateway_name) {
			$this->db->where('name', $gateway_name);
			$query = $this->db->get('external_apis');
			if($query->num_rows > 0) {
				return $query->row()->external_api_id;
			} else {
				die($this->response->Error(2001));
			}
			
		}
	}
	
	/**
	* Get list of current gateways
	*
	* Returns details of all gateways for a client.  All parameters except $client_id are optional. 
	*
	* @param int $client_id The client ID
	*
	* @param int $params['deleted'] Whether or not the gateway is deleted.  Possible values are 1 for deleted and 0 for active
	* @param int $params['id'] The email ID.  GetGateway could also be used. Optional.
	* @param int $params['offset']
	* @param int $params['limit'] The number of records to return. Optional.
	* 
	* @return mixed Array containg all gateways meeting criteria
	*/
	
	function GetGateways ($client_id, $params)
	{		
		if(isset($params['deleted']) and $params['deleted'] == '1') {
			$this->db->where('client_gateways.deleted', '1');
		}
		else {
			$this->db->where('client_gateways.deleted', '0');
		}
		
		if (isset($params['offset'])) {
			$offset = $params['offset'];
		}
		else {
			$offset = 0;
		}
		
		if(isset($params['limit'])) {
			$this->db->limit($params['limit'], $offset);
		}
		
		$this->db->join('external_apis', 'external_apis.external_api_id = client_gateways.external_api_id', 'left');
		
		$this->db->select('client_gateways.*');
		$this->db->select('external_apis.*');
		
		$this->db->where('client_gateways.client_id', $client_id);
		
		$query = $this->db->get('client_gateways');
		$data = array();
		if($query->num_rows() > 0) {
			foreach($query->result_array() as $row)
			{
				$array = array(
								'id' => $row['client_gateway_id'],
								'gateway' => $row['display_name'],
								'date_created' => $row['create_date']
								);
								
				$data[] = $array;
			}
			
		} else {
			return FALSE;
		}
		
		return $data;
	}
	
	/**
	* Get the gateway details.
	*
	* Returns an array containg all the details for the Client Gateway
	*
	* @param int $client_id	The Client ID
	* @param int $gateway_id The gateway_id
	* 
	* @return array All gateway details
	*/
	
	function GetGatewayDetails($client_id, $gateway_id = FALSE, $deleted_ok = FALSE)
	{
		$CI =& get_instance();
		$CI->load->model('client_model');
		$client = $CI->client_model->GetClientDetails($client_id);
		
		// If they have not passed a gateway ID, we will choose the first one created.
		if($gateway_id) {
			$this->db->where('client_gateways.client_gateway_id', $gateway_id);
		} elseif (!empty($client->default_gateway_id)) {
			$this->db->where('client_gateways.client_gateway_id', $client->default_gateway_id);
		} else {
			$this->db->order_by('create_date', 'ASC');
		}
		
		$this->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$this->db->where('client_gateways.client_id', $client_id);
		if ($deleted_ok == FALSE) {
			$this->db->where('deleted', 0);
		}
		$this->db->limit(1);
		$query = $this->db->get('client_gateways');
		if($query->num_rows > 0) {
			
			$row = $query->row();
			$data = array();
			$data['url_live'] = $row->prod_url;
			$data['url_test'] = $row->test_url;
			$data['url_dev'] = $row->dev_url;
			$data['arb_url_live'] = $row->arb_prod_url;
			$data['arb_url_test'] = $row->arb_test_url;
			$data['arb_url_dev'] = $row->arb_dev_url;
			$data['name'] = $row->name;
			$data['enabled'] = $row->enabled;
			$data['gateway_id'] = $row->client_gateway_id;
			
			// Get the params
			$this->load->library('encrypt');
			$this->db->where('client_gateway_id', $row->client_gateway_id);
			$query = $this->db->get('client_gateway_params');
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					$data[$row->field] = $this->encrypt->decode($row->value);
				}
			}
			
			return $data;
		} else {
			die($this->response->Error(3000));
		}		
	}
	
	/**
	* Get available Gateway External API's
	*
	* Loads a list of all possible gateway types, as well as their required fields
	* 
	* @return array|bool Returns an array containing all of the fields required for that gateway type or FALSE upon failure
	*/
	function GetExternalAPIs()
	{
		$this->db->order_by('display_name');
		
		$query = $this->db->get('external_apis');
		if($query->num_rows() > 0) {
			$gateways = array();
			
			foreach ($query->result_array() as $row) {
				$this->load->library('payment/' . $row['name'] . '.php',$row['name']);
				
				$settings = $this->$row['name']->Settings();
			
				$gateways[] = $settings;
			}	
			
			return $gateways;
		} else {
			return FALSE;
		}
	}
	
	
	/* Future Code - This may be used in the future.  Please code above this line.
	
	function Auth($client_id, $params, $xml)
	{
		if(!isset($params['gateway_id'])) {
			die($this->response->Error(3001));
		}
		
		$CI =& get_instance();
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('Auth', $params);
		
		// Get the credit card object
		$credit_card = $xml->credit_card;
		
		// Create a new order
		$CI->load->model('order_model');
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $credit_card);
		
		// Get the gateway info to load the proper library
		$gateway_id = $params['gateway_id'];
		$CI->load->model('gateway_model');
		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $gateway_id);
		
		// Get the customer details if a customer id was included
		if(isset($params['customer_id'])) {
			$CI->load->model('customer_model');
			$customer = $CI->customer_model->GetCustomerDetails($client_id, $params['customer_id']);
		} else {
			$customer = array();
		}
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->Auth($client_id, $order_id, $gateway, $customer, $params, $credit_card);
	}
	
	function Capture($client_id, $params)
	{
		if(!isset($params['gateway_id'])) {
			die($this->response->Error(3001));
		}
		
		$CI =& get_instance();
		
		// Get the order details
		$CI->load->model('order_model');
		$order = $CI->order_model->GetOrder($client_id, $params['order_id']);
		$order_id = $order->order_id;
		
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('Capture', $params);
		
		// Get the gateway info to load the proper library
		$gateway_id = $params['gateway_id'];
		$CI->load->model('gateway_model');
		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $gateway_id);
		
		// Get the customer details
		$CI->load->model('customer_model');
		$customer = $CI->customer_model->GetCustomerDetails($client_id, $params['customer_id']);
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->Capture($client_id, $order_id, $gateway, $customer, $params);
	}
	
	
	
	function Void($client_id, $params)
	{
		if(!isset($params['gateway_id'])) {
			die($this->response->Error(3001));
		}
		
		$CI =& get_instance();
		
		// Get the order details
		$CI->load->model('order_model');
		$order = $CI->order_model->GetOrder($client_id, $params['order_id']);
		$order_id = $order->order_id;
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('Void', $params);
		
		// Get the gateway info to load the proper library
		$gateway_id = $params['gateway_id'];
		$CI->load->model('gateway_model');
		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $gateway_id);
		
		// Get the customer details
		$CI->load->model('customer_model');
		$customer = $CI->customer_model->GetCustomerDetails($client_id, $params['customer_id']);
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->Void($client_id, $order_id, $gateway, $customer, $params);
	}
	*/
}
<?php
/**
* Gateway Model 
*
* Contains all the methods used to create and manage client gateways, process credit card charges, and create recurring subscriptions.
*
* @version 1.0
* @author David Ryan
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
		$this->load->library('field_validation');
		$request_type_id = $this->field_validation->ValidateRequiredGatewayFields($gateway_type, $params);
		
		// Get the external API id
		$external_api_id = $this->GetExternalApiId($gateway_type);
		
		// Create the new Gateway
		
		$create_date = date('Y-m-d');
		
		$insert_data = array(
							'client_id' 		=> $params['client_id'],
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
		
		foreach($params as $key => $value)
		{
			$insert_data = array(
								'client_gateway_id'	=> $new_gateway_id,
								'field' 			=> $key,
								'value'				=> $value
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
		
		// Create a new order
		$CI->load->model('order_model');
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params);
		
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
		$response = $this->$gateway_name->Charge($client_id, $order_id, $gateway, $customer, $params, $credit_card);	
		
		// If it was successful, send an email
		if($response['response_code'] == 1) {
			$this->email->TriggerTrip('charge', $client_id, $response['charge_id']);
		}
		
		return $response;
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
		if(isset($params['gateway_id'])) {
			$gateway_id = $params['gateway_id'];
		} else {
			$gateway_id = FALSE;
		}
		
		$CI =& get_instance();
		
		// Get the credit card object
		$credit_card = $params['credit_card'];
		
		// Get the gateway info to load the proper library
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
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
		$CI->load->model('customer_model');
		
		if(isset($params['customer_id'])) {
			$customer = $CI->customer_model->GetCustomerDetails($client_id, $params['customer_id']);
		} else {
			// If a customer ID was not passed we need to make sure that a cardholder name was
			if(!isset($credit_card['name'])) {
				die($this->response->Error(5004));
			} else {
				$name = explode(' ', $credit_card['name']);
				$customer['first_name'] = $name[0];
				$customer['last_name'] = $name[1];
				$customer['customer_id'] = $CI->customer_model->SaveNewCustomer($client_id, $name[0], $name[1]);
				
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
			
			$interval 			= $plan_details->interval;
			$notification_url 	= $plan_details->notification_url;
			$amount 			= $plan_details->amount;
			$free_trial 		= $plan_details->free_trial;
			
			$plan_id = $plan_details->plan_id;
			
		} else {
			
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
			$free_trial = FALSE;
		}
		
		// Validate the start date to make sure it is in the future
		if(isset($recur['start_date'])) {
			if(!$this->field_validation->ValidateDate($recur['start_date']) or strtotime($recur['start_date']) < time()) {
				die($this->response->Error(5001));
			} else {
				$start_date = date('Y-m-d', strtotime($recur['start_date']));
			}
		} else {
			$start_date = date('Y-m-d', (time()));
		}
		
		if($free_trial) {
			$start_date = date('Y-m-d', strtotime($start_date) + ($free_trial * 86400));
		}
		
		// Get the next payment date
		$next_charge_date = date('Y-m-d', strtotime($start_date) + ($interval * 86400));
		
		// If an end date was passed, make sure it's valid
		if(isset($recur['end_date'])) {
			if(strtotime($recur['end_date']) < time()) {
				die($this->response->Error(5002));
			} elseif(strtotime($recur->end_date) < strtotime($start_date)) {
				die($this->response->Error(5003));
			} else {
				$end_date = date('Y-m-d', strtotime($recur['end_date']));
			}
		} else {
			// Find the end date based on the interval and the max end date
			$end_date = date('Y-m-d', strtotime($start_date) + ($this->config->item('max_recurring_days_from_today') * 86400));
		}
		
		// Figure the total number of occurrences
		$total_occurrences = round((strtotime($end_date) - strtotime($start_date)) / ($interval * 86400), 0);
		
		// Save the subscription info
		$CI->load->model('subscription_model');
		$subscription_id = $CI->subscription_model->SaveSubscription($client_id, $params['gateway_id'], $customer['customer_id'], $start_date, $end_date, $next_charge_date, $total_occurrences, $notification_url, $amount, $plan_id);
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->Recur($client_id, $gateway, $customer, $params, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences);
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
		
		// Get the gateway fields
		$fields = $this->GetRequiredGatewayFields($gateway['name']);
		
		$i = 0;
		foreach($fields as $required_value)
		{
			foreach($required_value as $key => $value)
			{
				if(isset($params[$value]) && $params[$value] != '') {
					$update_data['value'] = $params[$value];
					$this->db->where('client_gateway_id', $params['gateway_id']);
					$this->db->where('field', $value);
					$this->db->update('client_gateway_params', $update_data);
					$i++;
				}
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
		// Make sure the gateway is actually theirs
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
		if(!$gateway) {
			die($this->response->Error(3000));
		}
		
		// Mark as deleted
		$update_data['deleted'] = 1;
		$this->db->where('client_gateway_id', $params['gateway_id']);
		$this->db->update('client_gateways', $update_data);
		
		// Delete the client gateway params
		$this->db->where('client_gateway_id', $params['gateway_id']);
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
	* Get the gateway details.
	*
	* Returns an array containg all the details for the Client Gateway
	*
	* @param int $client_id	The Client ID
	* @param int $gateway_id The gateway_id
	* 
	* @return array All gateway details
	*/
	
	function GetGatewayDetails($client_id, $gateway_id = FALSE)
	{
		// If they have not passed a gateway ID, we will choose the first one created.
		if($gateway_id) {
			$this->db->where('client_gateways.client_gateway_id', $gateway_id);
		} else {
			$this->db->order_by('create_date', 'ASC');
		}
		
		$this->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$this->db->where('client_gateways.client_id', $client_id);
		$this->db->where('enabled', 1);
		$this->db->where('deleted', 0);
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
			
			// Get the params
			$this->db->where('client_gateway_id', $gateway_id);
			$query = $this->db->get('client_gateway_params');
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					$data[$row->field] = $row->value;
				}
			}
			return $data;
		} else {
			die($this->response->Error(3000));
		}		
	}
	
	/**
	* Get required gateway fields.
	*
	* Get the fields required for creating a new instance of a client_gateway depending on gateway type.
	*
	* @param string $gateway_type The name of the gateway ('authnet', 'exact', etc.)
	* 
	* @return array|bool Returns an array containing all of the fields required for that gateway type or FALSE upon failure
	*/
	function GetRequiredGatewayFields($gateway_type = FALSE)
	{
		if($gateway_type)
		{
			$this->db->select('external_api_required_fields.field_name');
			$this->db->join('external_api_required_fields', 'external_api_required_fields.external_api_id = external_apis.external_api_id', 'inner');
			$this->db->where('external_apis.name', $gateway_type);
			$query = $this->db->get('external_apis');
			if($query->num_rows() > 0) {
				return $query->result_array();	
			} else {
				return FALSE;
			}
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
	
	function Credit($client_id, $params)
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
		$this->field_validation->ValidateRequiredFields('Credit', $params);
		
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
		return $this->$gateway_name->Credit($client_id, $order_id, $gateway, $customer, $params);
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
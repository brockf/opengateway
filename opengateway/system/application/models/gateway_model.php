<?php
class Gateway_model extends Model
{
	function Gateway_model()
	{
		parent::Model();
	}
	
	// Create a new instance of a gateway
	function NewGateway($client_id = FALSE, $params = FALSE, $xml = FALSE)
	{
		
		// Get the gateway type
		if(!isset($gateway_params->gateway_type)) {
			die($this->response->Error(1005));
		}
		
		$gateway_type = $params['gateway_type'];
		
		// Validate the required fields
		$this->load->library('field_validation');
		$request_type_id = $this->field_validation->ValidateRequiredGatewayFields($gateway_type, $gateway_params);
		
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
		
		//Add the params, but not the client id or gateway type
		unset($params['client_id']);
		unset($params['gateway_type']);
		unset($params['enabled']);
		
		foreach($params as $key => $value)
		{
			$insert_data = array(
								'client_gateway_id'	=> $new_gateway_id,
								'field' 			=> $key,
								'value'				=> $value
								);  
		
			$this->db->insert('client_gateway_params', $insert_data);
		}
		
		$response = array('gateway_id' 	=> $new_gateway_id);
		
		return $response; 

	}
	
	
	
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
		
		// Create a new order
		$CI->load->model('order_model');
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params, $credit_card);
		
		// Get the gateway info to load the proper library
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
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
		return $this->$gateway_name->Charge($client_id, $order_id, $gateway, $customer, $params, $credit_card);
		
	}
	
	function Recur($client_id, $params, $xml)
	{
		if(isset($params['gateway_id'])) {
			$gateway_id = $params['gateway_id'];
		} else {
			$gateway_id = FALSE;
		}
		
		$CI =& get_instance();
		
		// Get the credit card object
		$credit_card = $params['credit_card'];
		
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('NewRecurring', $params);
		
		// Get the gateway info to load the proper library
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);
		
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
		$recur = $params['recur'];
		
		// Validate the start date to make sure it is in the future
		if(isset($recur['start_date'])) {
			if(strtotime($recur['start_date']) < time()) {
				die($this->response->Error(5001));
			} else {
				$start_date = date('Y-m-d', strtotime($recur['start_date']));
			}
		} else {
			$start_date = date('Y-m-d', (time() + ($recur['interval * 86400'])));
		}
		
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
		
		// Check for a notification URL
		if(isset($recur['notification_url'])) {
			$notification_url = $recur['notification_url'];
		} else {
			$notification_url = '';
		}
		
		// Figure the total number of occurrences
		$total_occurrences = round((strtotime($end_date) - strtotime($start_date)) / ($recur['interval'] * 86400), 0);
		
		// Save the subscription info
		$CI->load->model('subscription_model');
		$subscription_id = $CI->subscription_model->SaveSubscription($client_id, $params['gateway_id'], $customer['customer_id'], $start_date, $end_date, $total_occurrences, $notification_url, $params);
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->Recur($client_id, $gateway, $customer, $params, $start_date, $end_date, $recur['interval'], $credit_card, $subscription_id);
	}
	
	function CancelRecur($client_id, $params)
	{
		if(isset($params['gateway_id'])) {
			$gateway_id = $params['gateway_id'];
		} else {
			$gateway_id = FALSE;
		}
		
		$CI =& get_instance();
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('CancelRecur', $params);
		
		// Get the gateway info to load the proper library
		$gateway = $this->GetGatewayDetails($client_id, $gateway_id);

		// Get the subscription information
		$CI->load->model('subscription_model');
		$subscription = $CI->subscription_model->GetSubscriptionDetails($client_id, $params['subscription_id']);
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->CancelRecur($client_id, $subscription);
	}
	
	
	
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
	
	// Get the gateway details
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
	
	// Get the required fields
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
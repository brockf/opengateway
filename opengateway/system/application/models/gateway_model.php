<?php
class Gateway_model extends Model
{
	function Gateway_model()
	{
		parent::Model();
	}
	
	// Create a new instance of a gateway
	function NewGateway($client_id = FALSE, $params = FALSE)
	{
		// Get the gateway type
		$gateway_type = $params['gateway_type'];
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredGatewayFields($gateway_type, $params);
		
		// Get the external API id
		$external_api_id = $this->GetExternalApiId($gateway_type);
		
		// Create the new Gateway
		$insert_data = array(
							'client_id' 		=> $params['client_id'],
							'external_api_id' 	=> $external_api_id,
							'enabled'			=> $params['enabled']
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
	function GetGatewayDetails($client_id, $gateway_id)
	{
		$this->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$this->db->where('client_gateways.client_id', $client_id);
		$this->db->where('client_gateways.client_gateway_id', $gateway_id);
		$query = $this->db->get('client_gateways');
		if($query->num_rows > 0) {
			
			$row = $query->row();
			$data = array();
			$data['url_live'] = $row->prod_url;
			$data['url_test'] = $row->test_url;
			$data['url_dev'] = $row->dev_url;
			$data['name'] = $row->name;
		    
			// Get the params
			$this->db->where('client_gateway_id', $gateway_id);
			$query = $this->db->get('client_gateway_params');
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					$data[$row->field] = $row->value;
				}
				
				return $data;
			}
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
	
	function Charge($client_id, $params)
	{
		if(!isset($params['gateway_id'])) {
			die($this->response->Error(3001));
		}
		
		$CI =& get_instance();
		
		// Create a new order
		$CI->load->model('order_model');
		$order_id = $CI->order_model->CreateNewOrder($client_id, $params);
		
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
		return $this->$gateway_name->Charge($client_id, $order_id, $gateway, $customer, $params);
		
	}
}
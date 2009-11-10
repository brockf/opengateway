<?php

class Customer_model extends Model
{
	function Customer_model()
	{
		parent::Model();
	}
	
	// Save new customer
	function NewCustomer($client_id, $params)
	{
		$insert_data = array(
							'client_id'		=> $client_id,
							'first_name' 	=> $params['first_name'],
							'last_name' 	=> $params['last_name'],
							'company'		=> $params['company'],
							'internal_id' 	=> $params['internal_id'],
							'address_1'		=> $params['address_1'],
							'address_2'		=> $params['address_2'],
							'city'			=> $params['city'],
							'state'			=> $params['state'],
							'postal_code'	=> $params['postal_code'],
							'phone'			=> $params['phone'],
							'email'			=> $params['email'],
							'active'		=> 1
							);
		$this->db->insert('customers', $insert_data);
		
		$response = array('customer_id' => $this->db->insert_id());
		return $response;
							
	}
	
	// Get the customer info
	function GetCustomerDetails($client_id, $customer_id)
	{
		$this->db->where('customer_id', $customer_id);
		$this->db->where('client_id', $client_id);
		$this->db->limit(1);
		$query = $this->db->get('customers');
		if($query->num_rows > 0) {
			foreach($query->row() as $key => $value) {
				$data[$key] = $value;
			}
			return $data;	
		} else {
			die($this->response->Error(4000));
		}
	}
}
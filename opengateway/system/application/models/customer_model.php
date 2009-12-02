<?php

class Customer_model extends Model
{
	function Customer_model()
	{
		parent::Model();
	}
	
	// Create new customer
	function NewCustomer($client_id, $params)
	{
		$customer_id = $this->SaveNewCustomer($client_id, $params['first_name'], $params['last_name'], $params['company'], $params['internal_id'], $params['address_1'], $params['address_2'], $params['city'], $params['state'], $params['postal_code'], $params['phone'], $params['email']);
		
		$response = array('customer_id' => $this->db->insert_id());
		return $response;
							
	}
	
	// Save new customer 
	function SaveNewCustomer($client_id, $first_name, $last_name, $company = '', $internal_id = '', $address_1 = '', $address_2 = '', $city = '', $state = '', $postal_code = '', $phone = '', $email = '')
	{
		$insert_data = array(
							'client_id'		=> $client_id,
							'first_name' 	=> $first_name,
							'last_name' 	=> $last_name,
							'company'		=> $company,
							'internal_id' 	=> $internal_id,
							'address_1'		=> $address_1,
							'address_2'		=> $address_2,
							'city'			=> $city,
							'state'			=> $state,
							'postal_code'	=> $postal_code,
							'phone'			=> $phone,
							'email'			=> $email,
							'active'		=> 1
							);
		$this->db->insert('customers', $insert_data);
		
		return $this->db->insert_id();
							
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
	
	function UpdateCustomer($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}

		
		if(isset($params['first_name'])) {
			$update_data['first_name'] = $params['first_name'];
		}
		
		if(isset($params['last_name'])) {
			$update_data['last_name'] = $params['last_name'];
		}
		
		if(isset($params['company'])) {
			$update_data['company'] = $params['company'];
		}
		
		if(isset($params['internal_id'])) {
			$update_data['internal_id'] = $params['internal_id'];
		}
		
		if(isset($params['address_1'])) {
			$update_data['address_1'] = $params['address_1'];
		}
		
		if(isset($params['address_2'])) {
			$update_data['address_2'] = $params['address_2'];
		}
		
		if(isset($params['city'])) {
			$update_data['city'] = $params['city'];
		}
		
		if(isset($params['state'])) {
			$update_data['state'] = $params['state'];
		}
		
		if(isset($params['postal_code'])) {
			$update_data['postal_code'] = $params['postal_code'];
		}
		
		if(isset($params['country'])) {
			$update_data['country'] = $params['country'];
		}
		
		if(isset($params['phone'])) {
			$update_data['phone'] = $params['phone'];
		}
		
		if(isset($params['email'])) {
			$update_data['email'] = $params['email'];
		}
		
		if(!isset($update_data)) {
			die($this->response->Error(6003));
		}
		
		// Make sure they update their own customer
		$this->db->where('client_id', $client_id);
		$this->db->where('customer_id', $params['customer_id']);
		
		$this->db->update('customers', $update_data);
		
		$response = $this->response->TransactionResponse(103);
		
		return $response;
	}
	
	function DeleteCustomer($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}
		
		// Make sure they update their own customer
		$this->db->where('client_id', $client_id);
		$this->db->where('customer_id', $params['customer_id']);
		
		$this->db->update('customers', array('active' => 0));
		
		$response = $this->response->TransactionResponse(104);
		
		return $response;
	}
	
	function GetCustomers($client_id, $params)
	{
		// Make sure they only get their own customers
		$this->db->where('customers.client_id', $client_id);
		
		// Check which search paramaters are set
		if(isset($params['first_name'])) {
			$this->db->where('first_name', $params['first_name']);
		}
		
		if(isset($params['last_name'])) {
			$this->db->where('last_name', $params['last_name']);
		}
		
		if(isset($params['company'])) {
			$this->db->where('company', $params['company']);
		}
		
		if(isset($params['internal_id'])) {
			$this->db->where('internal_id', $params['internal_id']);
		}
		
		if(isset($params['address_1'])) {
			$this->db->where('address_1', $params['address_1']);
		}
		
		if(isset($params['address_2'])) {
			$this->db->where('address_2', $params['address_2']);
		}
		
		if(isset($params['city'])) {
			$this->db->where('city', $params['city']);
		}
		
		if(isset($params['state'])) {
			$this->db->where('state', $params['state']);
		}
		
		if(isset($params['postal_code'])) {
			$this->db->where('postal_code', $params['postal_code']);
		}
		
		if(isset($params['country'])) {
			$this->db->where('country', $params['country']);
		}
		
		if(isset($params['phone'])) {
			$this->db->where('phone', $params['phone']);
		}
		
		if(isset($params['email'])) {
			$this->db->where('email', $params['email']);
		}
		
		if(isset($params['limit'])) {
			$this->db->limit($params['limit']);
		} else {
			$this->db->limit($this->config->item('query_result_default_limit'));
		}
		
		if(isset($params['active_recurring'])) {
			$this->db->join('subscriptions', 'customers.customer_id = subscriptions.customer_id', 'inner');
			if($params['active_recurring'] == 1) {
				$this->db->where('subscriptions.active', 1);
			} elseif($params['active_recurring'] === 0) {
				$this->db->where('subscriptions.active', 0);
			}
			
		}
		
		
		$this->db->order_by('customers.customer_id', 'DESC');
		$query = $this->db->get('customers');
		if($query->num_rows() > 0) {
			$data['results'] = $query->num_rows();
			$i=0;
			foreach($query->result() as $row) {
				
				$data['customers']['customer'][$i]['id'] = $row->customer_id;
				$data['customers']['customer'][$i]['internal_id'] = $row->internal_id;
				$data['customers']['customer'][$i]['firstname'] = $row->first_name;
				$data['customers']['customer'][$i]['lastname'] = $row->last_name;
				$data['customers']['customer'][$i]['company'] = $row->company;
				$data['customers']['customer'][$i]['address_1'] = $row->address_1;
				$data['customers']['customer'][$i]['address_2'] = $row->address_2;
				$data['customers']['customer'][$i]['city'] = $row->city;
				$data['customers']['customer'][$i]['state'] = $row->state;
				$data['customers']['customer'][$i]['postal_code'] = $row->postal_code;
				$data['customers']['customer'][$i]['email'] = $row->email;
				$data['customers']['customer'][$i]['phone'] = $row->phone;
				
				$i++;
			}
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
	function GetCustomer($client_id, $params)
	{
		// Get the gateway type
		if(!isset($params['customer_id'])) {
			die($this->response->Error(4000));
		}
		
		$this->db->where('orders.client_id', $client_id);
		$this->db->where('orders.order_id', $params['charge_id']);
		$this->db->limit(1);
		$query = $this->db->get('orders');
		if($query->num_rows() > 0) {
			$row = $query->row();
			
			$data['customer']['id'] = $row->customer_id;
			$data['customer']['internal_id'] = $row->internal_id;
			$data['customer']['firstname'] = $row->first_name;
			$data['customer']['lastname'] = $row->last_name;
			$data['customer']['company'] = $row->company;
			$data['customer']['address_1'] = $row->address_1;
			$data['customer']['address_2'] = $row->address_2;
			$data['customer']['city'] = $row->city;
			$data['customer']['state'] = $row->state;
			$data['customer']['postal_code'] = $row->postal_code;
			$data['customer']['email'] = $row->email;
			$data['customer']['phone'] = $row->phone;
				
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
}
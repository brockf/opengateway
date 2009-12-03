<?php

class Client_model extends Model
{
	function Client_model()
	{
		parent::Model();
	}
	
	// Create a new gateway user
	function NewClient($client_id = FALSE, $params = FALSE)
	{
		// Make sure this client is authorized to create a child client
		if($client_id) {
			$client = $this->GetClientDetails($client_id);
			if($client->client_type_id != 1) {
				die($this->response->Error(2000));
			}
		}
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('NewClient', $params);
		
		// Make sure the username is not already in use
		$exists = $this->UsernameExists($params['username']);
		
		if($exists) {
			die($this->response->Error(2002));
		}
		
		// Make sure the password meets the requirements
		$valid_pass = $this->ValidatePassword($params['password']);
		
		if(!$valid_pass) {
			die($this->response->Error(2003));
		}
		
		//Generate an API ID and Secret Key
		$this->load->library('key');
		$api_id = strtoupper($this->key->GenerateKey(20));
		$secret_key = strtoupper($this->key->GenerateKey(40));
		
		// Create the new Client
		$insert_data = array(
							'client_type_id'	=> 2,
							'first_name' 		=> $params['first_name'],
							'last_name'  		=> $params['last_name'],
							'company'	 		=> $params['company'],
							'address_1'  		=> $params['address_1'],
							'address_2'  		=> $params['address_2'],
							'city'				=> $params['city'],
							'state'		 		=> $params['state'],
							'postal_code'		=> $params['postal_code'],
							'country'	 		=> $params['country'],
							'phone'				=> $params['phone'],
							'email'		 		=> $params['email'],
							'parent_client_id' 	=> $client_id,
							'api_id'			=> $api_id,
							'secret_key'		=> $secret_key,
							'username'			=> $params['username'],
							'password'			=> md5($params['password'])
							);  
		$this->db->insert('clients', $insert_data);
		
		$response = array(
						 'user_id' 		=> $this->db->insert_id(),
						 'api_id' 		=> $api_id,
						 'secret_key' 	=> $secret_key
						 );
		
		return $response; 
		
	
		
	}
	
	function UpdateAccount($client_id, $params)
	{
		$params['client_id'] = $client_id;
		return $this->UpdateClient($client_id, $params);
	}
	
	function UpdateClient($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('UpdateClient', $params);
		
		// Make sure it's them or their client
		
		if($params['client_id'] == $client_id) {
			$client = $this->GetClientDetails($client_id);
		} else {
			$client = $this->GetChildClientDetails($client_id, $params['client_id']);
		}
		
		if(!$client) {
			die($this->response->Error(2004));
		}
		
		if(isset($params['first_name']) && $params['first_name'] != '') {
			$update_data['first_name'] = $params['first_name'];
		}
		
		if(isset($params['last_name']) && $params['last_name'] != '') {
			$update_data['last_name'] = $params['last_name'];
		}
		
		if(isset($params['company'])) {
			$update_data['company'] = $params['company'];
		}
		
		if(isset($params['address_1']) && $params['address_1'] != '') {
			$update_data['address_1'] = $params['address_1'];
		}
		
		if(isset($params['address_2'])) {
			$update_data['address_2'] = $params['address_2'];
		}
		
		if(isset($params['city']) && $params['city'] != '') {
			$update_data['city'] = $params['city'];
		}
		
		if(isset($params['state']) && $params['state'] != '') {
			$update_data['state'] = $params['state'];
		}
		
		if(isset($params['postal_code']) && $params['postal_code'] != '') {
			$update_data['postal_code'] = $params['postal_code'];
		}
		
		if(isset($params['country']) && $params['country'] != '') {
			$update_data['country'] = $params['country'];
		}
		
		if(isset($params['phone']) && $params['phone'] != '') {
			$update_data['phone'] = $params['phone'];
		}
		
		if(isset($params['email']) && $params['email'] != '') {
			$update_data['email'] = $params['email'];
		}
		
		if(isset($params['username']) && $params['username'] != '') {
			// Make sure the username is not already in use
			$exists = $this->UsernameExists($params['username']);
			
			if($exists) {
				die($this->response->Error(2002));
			}
			$update_data['username'] = $params['username'];
		}
		
		if(isset($params['password'])) {
			// Make sure the password meets the requirements
			$valid_pass = $this->ValidatePassword($params['password']);
			
			if(!$valid_pass) {
				die($this->response->Error(2003));
			}
			$update_data['password'] = md5($params['password']);
		}
		
		if(!isset($update_data)) {
			die($this->response->Error(6003));
		}
		
		$this->db->where('client_id', $params['client_id']);
		$this->db->update('clients', $update_data);
		
		$response = $this->response->TransactionResponse(200,array());
		
		return $response;
	}
	
	function SuspendClient($client_id, $params)
	{
		$client = $this->GetChildClientDetails($client_id, $params['client_id']);
		
		// Make sure it's their client
		if(!$client) {
			die($this->response->Error(2004));
		}
		
		$update_data['suspended'] = 1;
		
		$this->db->where('client_id', $params['client_id']);
		$this->db->update('clients', $update_data);
		
		$response = $this->response->TransactionResponse(201,array());
		
		return $response;
	}
	
	function UnsuspendClient($client_id, $params)
	{
		$client = $this->GetChildClientDetails($client_id, $params['client_id']);
		
		// Make sure it's their client
		if(!$client) {
			die($this->response->Error(2004));
		}
		
		$update_data['suspended'] = 0;
		
		$this->db->where('client_id', $params['client_id']);
		$this->db->update('clients', $update_data);
		
		$response = $this->response->TransactionResponse(202,array());
		
		return $response;
	}
	
	function DeleteClient($client_id, $params)
	{
		$client = $this->GetChildClientDetails($client_id, $params['client_id']);
		
		// Make sure it's their client
		if(!$client) {
			die($this->response->Error(2004));
		}
		
		$update_data['deleted'] = 1;
		
		$this->db->where('client_id', $params['client_id']);
		$this->db->update('clients', $update_data);
		
		$response = $this->response->TransactionResponse(203,array());
		
		return $response;
	}
	
	
	
	function GetChildClientDetails($parent_client_id, $child_client_id)
	{
		$this->db->where('parent_client_id', $parent_client_id);
		$this->db->where('client_id', $child_client_id);
		$this->db->limit(1);
		$query = $this->db->get('clients');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			return FALSE;
		}
	}
	
	function GetClientDetails($client_id)
	{
		$this->db->where('client_id', $client_id);
		$this->db->limit(1);
		$query = $this->db->get('clients');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			return FALSE;
		}
	}
	
	function UsernameExists($username)
	{
		$this->db->where('username', $username);
		$query = $this->db->get('clients');
		if($query->num_rows() > 0) {
			return TRUE;	
		} else {
			return FALSE;
		}
	}
	
	function ValidatePassword($password)
	{
		if(
			ctype_alnum($password) // numbers & digits only
			&& strlen($password) > 6 // at least 7 chars
			&& strlen($password) < 21 // at most 20 chars
			&& preg_match('`[A-Z]`',$password) // at least one upper case
			&& preg_match('`[a-z]`',$password) // at least one lower case
			&& preg_match('`[0-9]`',$password) // at least one digit
			) {
				return TRUE;
			}else {
				return FALSE;
			} 
	}
}
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
		
		//Generate an API ID and Secret Key
		$this->load->library('key');
		$api_id = strtoupper($this->key->GenerateKey(20));
		$secret_key = strtoupper($this->key->GenerateKey(40));
		
		// Create the new Client
		$insert_data = array(
							'client_type_id'	=> 2,
							'first_name' 		=> (string)$params->first_name,
							'last_name'  		=> (string)$params->last_name,
							'company'	 		=> (string)$params->company,
							'address_1'  		=> (string)$params->address_1,
							'address_2'  		=> (string)$params->address_2,
							'city'				=> (string)$params->city,
							'state'		 		=> (string)$params->state,
							'postal_code'		=> (string)$params->postal_code,
							'country'	 		=> (string)$params->country,
							'phone'				=> (string)$params->phone,
							'email'		 		=> (string)$params->email,
							'parent_client_id' 	=> $client_id,
							'api_id'			=> $api_id,
							'secret_key'		=> $secret_key
							);  
		$this->db->insert('clients', $insert_data);
		
		$response = array(
						 'user_id' 		=> $this->db->insert_id(),
						 'api_id' 		=> $api_id,
						 'secret_key' 	=> $secret_key
						 );
		
		return $response; 
		
	
		
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
}
<?php
/**
* Client Model 
*
* Contains all the methods used to create, update, and delete clients.
*
* @version 1.0
* @author David Ryan
* @package OpenGateway

*/
class Client_model extends Model
{
	function Client_model()
	{
		parent::Model();
	}
	
	/**
	* Create a new gateway client
	*
	* Creates a new client.
	*
	* @param int $client_id The client ID of the Parent Client
	* @param string $params['first_name'] Client's first name
	* @param string $params['last_name'] Client's last name
	* @param string $params['company'] Client's company
	* @param string $params['address_1'] Client's address line 1.
	* @param string $params['address_2'] Client's address line 2. Optional.
	* @param string $params['city'] Client's city
	* @param string $params['state'] Client's state
	* @param string $params['postal_code'] Client's postal code
	* @param string $params['country'] Client's country
	* @param string $params['phone'] Client's phone
	* @param string $params['email'] Client's email
	* @param string $params['username'] Client's username
	* @param string $params['password'] Client's password
	* 
	*
	* 
	* @return mixed Array containing new API ID and Secret Key
	*/
	function NewClient($client_id = FALSE, $params = FALSE)
	{
		// Make sure this client is authorized to create a child client
		if($client_id) {
			$client = $this->GetClientDetails($client_id);
			if($client->client_type_id != 1 or $client->client_type_id != 3) {
				die($this->response->Error(2000));
			}
		}
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('NewClient', $params);
		
		// Make sure the country is in the proper format
		$country_id = $this->field_validation->ValidateCountry($params['country']);
		
		if(!$country_id) {
			die($this->response->Error(1007));
		}
		
		$valid_email = $this->field_validation->ValidateEmailAddress($params['email']);
		
		if(!$valid_email) {
			die($this->response->Error(1008));
		}
		
		// Make sure the username is not already in use
		$exists = $this->UsernameExists($params['username']);
		
		if($exists) {
			die($this->response->Error(2002));
		}
		
		// let's see what type of client we are making
		if (isset($params['client_type'])) {
			if ($params['client_type'] == 1 and $client->client_type_id != 3) {
				// only Administrators can make Service Providers
				die($this->response->Error(2006));
			}
			elseif ($params['client_type'] < 1 or $params['client_type'] > 2) {
				die($this->response->Error(2007));
			}
		}
		else {
			$params['client_type'] == 2;
		}
		
		// Make sure the password meets the requirements
		$valid_pass = $this->ValidatePassword($params['password']);
		
		if(!$valid_pass) {
			die($this->response->Error(2003));
		}
		
		// Generate an API ID and Secret Key
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
							'country'	 		=> $country_id,
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
						 
		$response = $this->response->TransactionResponse(300,$response);
		
		return $response; 		
	}
	
	function UpdateAccount($client_id, $params)
	{
		$params['client_id'] = $client_id;
		return $this->UpdateClient($client_id, $params);
	}
	
	
	/**
	* Update Client information.
	*
	* Updates client information.  All fields are optional
	*
	* @param int $client_id The client ID of the Parent Client
	* @param string $params['first_name'] Client's first name
	* @param string $params['last_name'] Client's last name
	* @param string $params['company'] Client's company
	* @param string $params['address_1'] Client's address line 1.
	* @param string $params['address_2'] Client's address line 2.
	* @param string $params['city'] Client's city
	* @param string $params['state'] Client's state
	* @param string $params['postal_code'] Client's postal code
	* @param string $params['country'] Client's country
	* @param string $params['phone'] Client's phone
	* @param string $params['email'] Client's email
	* @param string $params['username'] Client's username
	* @param string $params['password'] Client's password
	* 
	*
	* 
	* @return mixed Result
	*/
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
			// Make sure the country is in the proper format
			$country_id = $this->field_validation->ValidateCountry($params['country']);
			
			if(!$country_id) {
				die($this->response->Error(1007));
			}
			$update_data['country'] = $country_id;
		}
		
		if(isset($params['phone']) && $params['phone'] != '') {
			$update_data['phone'] = $params['phone'];
		}
		
		if(isset($params['email']) && $params['email'] != '') {
			$valid_email = $this->field_validation->ValidateEmailAddress($params['email']);
			
			if(!$valid_email) {
				die($this->response->Error(1008));
			}
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
		
		$response = $this->response->TransactionResponse(301,array());
		
		return $response;
	}
	
	
	/**
	* Mark a client as suspended
	*
	* Suspends a client.  When a client is suspended, they can no longer perform any API funcions
	*
	* @param int $client_id The client ID of the Parent Client
	* @param int $params['client_id'] Client to be suspended

	* 
	* @return mixed Result
	*/
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
		
		$response = $this->response->TransactionResponse(302,array());
		
		return $response;
	}
	
	/**
	* Mark a client as unsuspended
	*
	* Unsuspends a client.  Restores full functionality to a client.
	*
	* @param int $client_id The client ID of the Parent Client
	* @param int $params['client_id'] Client to be suspended

	* 
	* @return mixed Result
	*/
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
		
		$response = $this->response->TransactionResponse(303,array());
		
		return $response;
	}
	
	/**
	* Mark a client as deleted
	*
	* Deletes a client.  Does not actually delete the client, but marks it as deleted in the clients table.
	*
	* @param int $client_id The client ID of the Parent Client
	* @param int $params['client_id'] Client to be deleted

	* 
	* @return mixed Result
	*/
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
		
		$response = $this->response->TransactionResponse(304,array());
		
		return $response;
	}
	
	/**
	* Get the client details for a child client.
	*
	* Returns an array containg all the childs client's details.  
	* If the child does not belong to the parent, an error is returned.
	*
	* @param int $parent_client_id The client ID of the Parent Client
	* @param int $child_client_id Child client

	* 
	* @return mixed Array containing all the client details.
	*/
	
	function GetChildClientDetails($parent_client_id, $child_client_id)
	{
		$this->db->select('clients.*, countries.iso2 as country');
		$this->db->join('countries', 'countries.country_id = clients.country_id', 'left');
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
	
	/**
	* Get the client details for a client.
	*
	* Returns an array containg all the client's details.  
	*
	* @param int $client_id The client ID
	* 
	* @return mixed Array containing all the client details.
	*/
	function GetClientDetails($client_id)
	{
		$this->db->select('clients.*, countries.iso2 as country');
		$this->db->join('countries', 'countries.country_id = clients.country', 'left');
		$this->db->where('client_id', $client_id);
		$this->db->limit(1);
		$query = $this->db->get('clients');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			return FALSE;
		}
	}
	
	/**
	* Check to see if a username already exists.
	*
	* Returns TRUE or FALSE if the username already exists in the database.
	*
	* @param string $username The desired username.
	* 
	* @return boolean Returns TRUE or FALSE if the username already exists
	*/
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
	
	/**
	* Check to see if a passord meets strength requirements.
	*
	* Returns TRUE or FALSE if the password meets the requirements.
	*
	* @param string $password The desired password.
	* 
	* @return boolean Returns TRUE or FALSE if the password meets the requirements.
	*/
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
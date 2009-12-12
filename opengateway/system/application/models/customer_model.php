<?php
/**
* Customer Model 
*
* Contains all the methods used to create, update, and delete customers.
*
* @version 1.0
* @author David Ryan
* @package OpenGateway

*/
class Customer_model extends Model
{
	function Customer_model()
	{
		parent::Model();
	}
	
	/**
	* Create a new customer
	*
	* Creates a new customer.
	*
	* @param int $client_id The client ID of the gateway client.
	* @param string $params['first_name'] Client's first name
	* @param string $params['last_name'] Client's last name
	* @param string $params['company'] Client's company. Optional.
	* @param string $params['address_1'] Client's address line 1. Optional.
	* @param string $params['address_2'] Client's address line 2. Optional.
	* @param string $params['city'] Client's city. Optional.
	* @param string $params['state'] Client's state. Optional.
	* @param string $params['postal_code'] Client's postal code. Optional. 
	* @param string $params['country'] Client's country. Optional.
	* @param string $params['phone'] Client's phone. Optional.
	* @param string $params['email'] Client's email. Optional.
	* 
	* @return mixed Array containing new customer_id
	*/
	function NewCustomer($client_id, $params)
	{
		
		// Make sure the country is in the proper format
		$this->load->library('field_validation');
		$country_id = $this->field_validation->ValidateCountry($params['country']);
		
		if(!$country_id) {
			die($this->response->Error(1007));
		}
		
		// Make sure the email address is valid
		$this->load->library('field_validation');
		$valid_email = $this->field_validation->ValidateEmailAddress($params['email']);
		
		if(!$valid_email) {
			die($this->response->Error(1008));
		}
		
		$customer_id = $this->SaveNewCustomer($client_id, $params['first_name'], $params['last_name'], $params['company'], $params['internal_id'], $params['address_1'], $params['address_2'], $params['city'], $params['state'], $params['postal_code'], $country_id, $params['phone'], $params['email']);
		
		$response = array('customer_id' => $this->db->insert_id());
		
		$response = $this->response->TransactionResponse(200,$response);
		
		return $response;
							
	}
	
	// Save new customer 
	function SaveNewCustomer($client_id, $first_name, $last_name, $company = '', $internal_id = '', $address_1 = '', $address_2 = '', $city = '', $state = '', $postal_code = '', $country_id = '', $phone = '', $email = '')
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
							'country'		=> $country_id,
							'phone'			=> $phone,
							'email'			=> $email,
							'active'		=> 1
							);
		$this->db->insert('customers', $insert_data);
		
		return $this->db->insert_id();
							
	}
	
	/**
	* Get the customer details.
	*
	* Returns a array containg all the customers's details.  If the customer does not belong to the client, an error is returned.
	*
	* @param int $client_id The client ID
	* @param int $customer_id The customer ID
	* 
	* @return mixed Array containing all the customer details.
	*/
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
	
	
	/**
	* Updates a customer's details.
	*
	* Updates a customer details. If the customer does not belong to the client, an error is returned.
	*
	* @param int $client_id The client ID of the gateway client.
	* @param int $params['customer_id'] The Customer to update.
	* @param string $params['first_name'] Customer's first name. Optional.
	* @param string $params['last_name'] Customer's last name. Optional.
	* @param string $params['company'] Customer's company. Optional.
	* @param string $params['address_1'] Customer's address line 1. Optional.
	* @param string $params['address_2'] Customer's address line 2. Optional.
	* @param string $params['city'] Customer's city. Optional.
	* @param string $params['state'] Customer's state. Optional.
	* @param string $params['postal_code'] Customer's postal code. Optional. 
	* @param string $params['country'] Customer's country. Optional.
	* @param string $params['phone'] Customer's phone. Optional.
	* @param string $params['email'] Customer's email. Optional.
	* 
	* @return mixed Array containing new customer_id
	*/
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
			// Make sure the country is in the proper format
			$this->load->library('field_validation');
			$country_id = $this->field_validation->ValidateCountry($params['country']);
			
			if(!$country_id) {
				die($this->response->Error(1007));
			}
			$update_data['country'] = $params['country'];
		}
		
		if(isset($params['phone'])) {
			$update_data['phone'] = $params['phone'];
		}
		
		if(isset($params['email'])) {
			$valid_email = $this->field_validation->ValidateEmailAddress($params['email']);
			
			if(!$valid_email) {
				die($this->response->Error(1008));
			}
			$update_data['email'] = $params['email'];
		}
		
		if(!isset($update_data)) {
			die($this->response->Error(6003));
		}
		
		// Make sure they update their own customer
		$this->db->where('client_id', $client_id);
		$this->db->where('customer_id', $params['customer_id']);
		
		$this->db->update('customers', $update_data);
		
		$response = $this->response->TransactionResponse(201);
		
		return $response;
	}
	
	/**
	* Delete a customer.
	*
	* Marks a customer as deleted.  Does not actually delete the customer from the database, but only marks it as deleted.
	*
	* @param int $client_id The client ID
	* @param int $params['customer_id'] The customer ID
	* 
	* @return mixed Array containing all the customer details.
	*/
	function DeleteCustomer($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}
		
		// Make sure they update their own customer
		$this->db->where('client_id', $client_id);
		$this->db->where('customer_id', $params['customer_id']);
		
		$this->db->update('customers', array('active' => 0));
		
		// cancel all active subscriptions
		$this->db->where('client_id', $client_id);
		$this->db->where('customer_id', $params['customer_id']);
		
		$this->db->update('subscriptions', array('active' => 0));
		
		$response = $this->response->TransactionResponse(202);
		
		return $response;
	}
	
	/**
	* Get a list of customer details.
	*
	* Searches the database for customers belonging to the client and returns an array with all the details.
	* All search parameters are optional.
	*
	* @param int $client_id The client ID of the gateway client.
	* @param int $params['customer_id'] Customer ID. Optional
	* @param string $params['first_name'] Customer's first name. Optional.
	* @param string $params['last_name'] Customer's last name. Optional.
	* @param string $params['company'] Customer's company. Optional.
	* @param string $params['address_1'] Customer's address line 1. Optional.
	* @param string $params['address_2'] Customer's address line 2. Optional.
	* @param string $params['city'] Customer's city. Optional.
	* @param string $params['state'] Customer's state. Optional.
	* @param string $params['postal_code'] Customer's postal code. Optional. 
	* @param string $params['country'] Customer's country. Optional.
	* @param string $params['phone'] Customer's phone. Optional.
	* @param string $params['email'] Customer's email. Optional.
	* 
	* @return mixed Array containing the search results
	*/
	
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
		
		if (isset($params['offset'])) {
			$offset = $params['offset'];
		}
		else {
			$offset = 0;
		}
		
		if(isset($params['limit'])) {
			$this->db->limit($params['limit'], $offset);
		} else {
			$this->db->limit($this->config->item('query_result_default_limit'), $offset);
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
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$query = $this->db->get('customers');
		if($query->num_rows() > 0) {
			$data['results'] = $query->num_rows();
			$i=0;
			foreach($query->result() as $row) {
				
				$data['customers']['customer'][$i]['id'] = $row->customer_id;
				$data['customers']['customer'][$i]['internal_id'] = $row->internal_id;
				$data['customers']['customer'][$i]['first_name'] = $row->first_name;
				$data['customers']['customer'][$i]['last_name'] = $row->last_name;
				$data['customers']['customer'][$i]['company'] = $row->company;
				$data['customers']['customer'][$i]['address_1'] = $row->address_1;
				$data['customers']['customer'][$i]['address_2'] = $row->address_2;
				$data['customers']['customer'][$i]['city'] = $row->city;
				$data['customers']['customer'][$i]['state'] = $row->state;
				$data['customers']['customer'][$i]['postal_code'] = $row->postal_code;
				$data['customers']['customer'][$i]['country'] = $row->iso2;
				$data['customers']['customer'][$i]['email'] = $row->email;
				$data['customers']['customer'][$i]['phone'] = $row->phone;
				
				$i++;
			}
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
	/**
	* Get customer details.
	*
	* Searches the database for customers belonging to the client and with a specific customer_id.
	*
	* @param int $client_id The client ID of the gateway client.
	* @param int $params['customer_id'] Customer ID. Optional
	* 
	* @return mixed Array containing the search results
	*/
	
	function GetCustomer($client_id, $params)
	{
		// Get the customer id
		if(!isset($params['customer_id'])) {
			die($this->response->Error(4000));
		}
		
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$this->db->where('customers.client_id', $client_id);
		$this->db->where('customers.customer_id', $params['customer_id']);
		$this->db->limit(1);
		$query = $this->db->get('customers');
		if($query->num_rows() > 0) {
			$row = $query->row();
			
			$data['customer']['id'] = $row->customer_id;
			$data['customer']['internal_id'] = $row->internal_id;
			$data['customer']['first_name'] = $row->first_name;
			$data['customer']['last_name'] = $row->last_name;
			$data['customer']['company'] = $row->company;
			$data['customer']['address_1'] = $row->address_1;
			$data['customer']['address_2'] = $row->address_2;
			$data['customer']['city'] = $row->city;
			$data['customer']['state'] = $row->state;
			$data['customer']['postal_code'] = $row->postal_code;
			$data['customer']['country'] = $row->iso2;
			$data['customer']['email'] = $row->email;
			$data['customer']['phone'] = $row->phone;
				
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
}
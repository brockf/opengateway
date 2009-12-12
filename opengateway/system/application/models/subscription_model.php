<?php
/**
* Subscription Model 
*
* Contains all the methods used to create, update, and search subscriptions.
*
* @version 1.0
* @author David Ryan
* @package OpenGateway

*/
class Subscription_model extends Model
{
	function Subscription_model()
	{
		parent::Model();
	}
	
	/**
	* Create a new recurring subscription.
	*
	* Creates a new recurring subscription and returns the subscription ID.
	*
	* @param int $client_id The client ID of the gateway client.
	* @param int $gateway_id The gateway ID
 	* @param int $customer_id The customer ID
	* @param date $start_date The date the subscription should begin
	* @param date $end_date The date the subscription should end
	* @param int $total_occurrence The total number of charges for this subscription.
	* @param string $notifcation_url The notification URL
	* @param int $params['amount'] The amount to be charged
	* 
	* @return int The new subscription ID
	*/
	function SaveSubscription($client_id, $gateway_id, $customer_id, $start_date, $end_date, $next_charge_date, $total_occurrences, $notification_url, $params)
	{
		$timestamp = date('Y-m-d H:i:s');
		$insert_data = array(
							'client_id' 		=> $client_id,
							'gateway_id' 		=> $gateway_id,
							'customer_id' 		=> $customer_id,
							'start_date' 		=> $start_date,
							'end_date'			=> $end_date,
							'next_charge'		=> $next_charge_date,
							'number_occurrences'=> $total_occurrences,
							'notification_url'	=> stripslashes($notification_url),
							'amount'			=> $params['amount'],
							'timestamp'			=> $timestamp
			  				);  					  				
			  				
		$this->db->insert('subscriptions', $insert_data);
		
		return $this->db->insert_id();
	}
	
	/**
	* Add a customer profile ID.
	*
	* For API's that require a customer profile
	*
	* @param int $subscription_id The subscription_id
	* @param int $api_customer_reference The customer profile id
	*/
	function SaveApiCustomerReference($subscription_id, $api_customer_reference)
	{
		$update_data = array('api_customer_reference' => $api_customer_reference);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	/**
	* Add a customer payment ID.
	*
	* For API's that require a customer payment profile
	*
	* @param int $subscription_id The subscription_id
	* @param int $api_payment_reference The customer payment id
	*/
	function SaveApiPaymentReference($subscription_id, $api_payment_reference)
	{
		$update_data = array('api_payment_reference' => $api_payment_reference);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	/**
	* Add a Auth number.
	*
	* For API's that require an Auth code be used for future charges
	*
	* @param int $subscription_id The subscription_id
	* @param int $api_auth_number The API auth code.
	*/
	function SaveApiAuthNumber($subscription_id, $api_auth_number)
	{
		$update_data = array('api_auth_number' => $api_auth_number);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	/**
	* Make a subscription inactive
	*
	* Makes a subscription Inactive
	*
	* @param int $subscription_id The subscription_id
	*/
	function MakeInactive($subscription_id)
	{
		$update_data = array('active' => 0);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	/**
	* Get subscription details
	*
	* Returns an array of details about the subscription.
	*
	* @param int $client_id The client ID 
	* @param int $subscription_id The subscription_id
	* 
	* @return mixed Array Containing subscription details
	*/
	function GetSubscriptionDetails($client_id, $subscription_id)
	{
		$this->db->join('client_gateways', 'client_gateways.client_gateway_id = subscriptions.gateway_id', 'inner');
		$this->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$this->db->where('subscriptions.client_id', $client_id);
		$this->db->where('subscription_id', $subscription_id);
		$query = $this->db->get('subscriptions');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			die($this->response->Error(5000));
		}
	}
	
	/**
	* Retrieve details for a specific subscription
	*
	* Returns an array of data for the requested subscription.
	*
	* @param int $client_id The client ID.
	* @param int $params['recurring_id'];
	* 
	* @return mixed Array containing results
	*/
	
	function GetRecurring ($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('GetRecurring', $params);
	
		// Make sure they only get their own charges
		$this->db->where('subscriptions.client_id', $client_id);
		
		// Check which search paramaters are set
		$this->db->where('gateway_id', $params['gateway_id']);
		
		$this->db->join('customers', 'customers.customer_id = subscriptions.customer_id', 'left');
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$query = $this->db->get('subscriptions');
		
		if ($query->num_rows() == 0) {
			 die($this->response->Error(6004));
		}
		
		$row = $query->result();
		
		$data['recurrings']['recurring'][$i]['id'] = $row->subscription_id;
		$data['recurrings']['recurring'][$i]['create_date'] = $row->timestamp;
		$data['recurrings']['recurring'][$i]['amount'] = $row->amount;
		$data['recurrings']['recurring'][$i]['start_date'] = $row->start_date;
		$data['recurrings']['recurring'][$i]['end_date'] = $row->end_date;
		$data['recurrings']['recurring'][$i]['number_occurences'] = $row->number_occurrences;
		$data['recurrings']['recurring'][$i]['notification_url'] = $row->notification_url;
		$data['recurrings']['recurring'][$i]['status'] = ($row->active == '1') ? 'active' : 'cancelled';
		
		if($row->customer_id !== 0) {
			$data['recurrings']['recurring'][$i]['customer']['id'] = $row->customer_id;
			$data['recurrings']['recurring'][$i]['customer']['internal_id'] = $row->internal_id;
			$data['recurrings']['recurring'][$i]['customer']['firstname'] = $row->first_name;
			$data['recurrings']['recurring'][$i]['customer']['lastname'] = $row->last_name;
			$data['recurrings']['recurring'][$i]['customer']['company'] = $row->company;
			$data['recurrings']['recurring'][$i]['customer']['address_1'] = $row->address_1;
			$data['recurrings']['recurring'][$i]['customer']['address_2'] = $row->address_2;
			$data['recurrings']['recurring'][$i]['customer']['city'] = $row->city;
			$data['recurrings']['recurring'][$i]['customer']['state'] = $row->state;
			$data['recurrings']['recurring'][$i]['customer']['postal_code'] = $row->postal_code;
			$data['recurrings']['recurring'][$i]['customer']['country'] = $row->iso2;
			$data['recurrings']['recurring'][$i]['customer']['email'] = $row->email;
			$data['recurrings']['recurring'][$i]['customer']['phone'] = $row->phone;
		}
		
		return $data;
	}
	
	/**
	* Search subscriptions.
	*
	* Returns an array of results based on submitted search criteria.  All fields are optional.
	*
	* @param int $client_id The client ID.
	* @param int $params['gateway_id'] The gateway ID used for the order. Optional.
	* @param date $params['created_after'] Only subscriptions created after or on this date will be returned. Optional.
	* @param date $params['created_before'] Only subscriptions created before or on this date will be returned. Optional.
	* @param int $params['customer_id'] The customer id associated with the subscription. Optional.
	* @param string $params['customer_internal_id'] The customer's internal id associated with the subscription. Optional.
	* @param int $params['amount'] Only subscriptions for this amount will be returned. Optional.
	* @param boolean $params['active'] Returns only active subscriptions. Optional.
	* @param int $params['limit'] Limits the number of results returned. Optional.
	* 
	* @return mixed Array containing results
	*/
	function GetRecurrings ($client_id, $params)
	{
		// Make sure they only get their own charges
		$this->db->where('subscriptions.client_id', $client_id);
		
		// Check which search paramaters are set
		
		if(isset($params['gateway_id'])) {
			$this->db->where('gateway_id', $params['gateway_id']);
		}
		
		if(isset($params['created_after'])) {
			$start_date = date('Y-m-d H:i:s', strtotime($params['created_after']));
			$this->db->where('timestamp >=', $start_date);
		}
		
		if(isset($params['created_before'])) {
			$end_date = date('Y-m-d H:i:s', strtotime($params['created_before']));
			$this->db->where('timestamp <=', $end_date);
		}
		
		if(isset($params['customer_id'])) {
			$this->db->where('orders.customer_id', $params['customer_id']);
		}
		
		if(isset($params['customer_internal_id'])) {
			$this->db->where('customers.internal_id', $params['customer_internal_id']);
		}
		
		if(isset($params['amount'])) {
			$this->db->where('amount', $params['amount']);
		}
		
		if(isset($params['active'])) {
			$this->db->where('orders.active', $params['active']);
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
		
		$this->db->join('customers', 'customers.customer_id = subscriptions.customer_id', 'left');
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$query = $this->db->get('subscriptions');
		if($query->num_rows() > 0) {
			$data['results'] = $query->num_rows();
			$i=0;
			foreach($query->result() as $row) {
				$data['recurrings']['recurring'][$i]['id'] = $row->subscription_id;
				$data['recurrings']['recurring'][$i]['create_date'] = $row->timestamp;
				$data['recurrings']['recurring'][$i]['amount'] = $row->amount;
				$data['recurrings']['recurring'][$i]['start_date'] = $row->start_date;
				$data['recurrings']['recurring'][$i]['end_date'] = $row->end_date;
				$data['recurrings']['recurring'][$i]['number_occurences'] = $row->number_occurrences;
				$data['recurrings']['recurring'][$i]['notification_url'] = $row->notification_url;
				$data['recurrings']['recurring'][$i]['status'] = ($row->active == '1') ? 'active' : 'cancelled';
				
				if($row->customer_id !== 0) {
					$data['recurrings']['recurring'][$i]['customer']['id'] = $row->customer_id;
					$data['recurrings']['recurring'][$i]['customer']['internal_id'] = $row->internal_id;
					$data['recurrings']['recurring'][$i]['customer']['firstname'] = $row->first_name;
					$data['recurrings']['recurring'][$i]['customer']['lastname'] = $row->last_name;
					$data['recurrings']['recurring'][$i]['customer']['company'] = $row->company;
					$data['recurrings']['recurring'][$i]['customer']['address_1'] = $row->address_1;
					$data['recurrings']['recurring'][$i]['customer']['address_2'] = $row->address_2;
					$data['recurrings']['recurring'][$i]['customer']['city'] = $row->city;
					$data['recurrings']['recurring'][$i]['customer']['state'] = $row->state;
					$data['recurrings']['recurring'][$i]['customer']['postal_code'] = $row->postal_code;
					$data['recurrings']['recurring'][$i]['customer']['country'] = $row->iso2;
					$data['recurrings']['recurring'][$i]['customer']['email'] = $row->email;
					$data['recurrings']['recurring'][$i]['customer']['phone'] = $row->phone;
				}
				
				$i++;
			}
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
	/**
	* Update an existing subscription.
	*
	* Updates an existing subscription with new parameters.
	*
	* @param int $client_id The client ID of the gateway client.
	* @param int $params['recurring_id'] The subscription ID to update.
 	* @param string $params['notification_url'] The new notification URL. Optional.
	* @param int $params['customer_id'] The new customer id. Optional.
	* @param int $params['amount'] The new amount to charge. Optional
	* @param int $params['interval'] The new number of days between charges. Optional.
	* 
	*/
	function UpdateRecurring($client_id, $params)
	{
		if(!isset($params['recurring_id'])) {
			die($this->response->Error(6002));
		}

		
		if(isset($params['notification_url'])) {
			$update_data['notification_url'] = $params['notification_url'];
		}
		
		if(isset($params['customer_id'])) {
			$update_data['customer_id'] = $params['customer_id'];
		}
		
		if(isset($params['amount'])) {
			$update_data['amount'] = $params['amount'];
		}
		
		if(isset($params['next_charge_date'])) {
			$this->load->library('field_validation');
			if ($this->field_validation->ValidateDate($params['next_charge_date'])) {
				$update_data['next_charge_date'] = $params['next_charge_date'];			
			}
			else {
				die($this->response->Error(5007));
			}
		}
		
		if(isset($params['interval'])) {
			// Get the subcription details
			$subscription = $this->GetSubscriptionDetails($client_id, $params['recurring_id']);
			$start_date = $subscription->start_date;
			$end_date = $subscription->end_date;
			// Figure the total number of occurrences
			$update_data['number_occurrences'] = round((strtotime($end_date) - strtotime($start_date)) / ($params['interval'] * 86400), 0);
		}
		
		if(!isset($update_data)) {
			die($this->response->Error(6003));
		}
		
		// Make sure they update their own subscriptions
		$this->db->where('client_id', $client_id);
		$this->db->where('subscription_id', $params['recurring_id']);
		
		$this->db->update('subscriptions', $update_data);
		
		$response = $this->response->TransactionResponse(102,array());
		
		return $response;
	}
	
	function CancelRecurring($client_id, $params)
	{
		if(!isset($params['recurring_id'])) {
			die($this->response->Error(6002));
		}
	
		$CI =& get_instance();
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('CancelRecur', $params);

		// Get the subscription information
		$CI->load->model('subscription_model');
		$subscription = $CI->subscription_model->GetSubscriptionDetails($client_id, $params['recurring_id']);
		
		// Get the gateway info to load the proper library
		$CI->load->model('gateway_model');
		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $subscription->gateway_id);
		
		$gateway_name = $subscription->name;
		$this->load->library('payment/'.$gateway_name);
		$cancelled = $this->$gateway_name->CancelRecurring($client_id, $subscription, $gateway);
		
		if($cancelled) {
			$this->MakeInactive($params['recurring_id']);
			$response = $this->response->TransactionResponse(101,array());
		} else {
			die($this->response->Error(5014));
		}
		
		return $response;
	}
}
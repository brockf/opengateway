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
	* @param date $next_charge_data The date that the subscription should next be charged
	* @param int $total_occurrences The total number of charges for this subscription.
	* @param string $notification_url The notification URL
	* @param int $amount The amount to be charged
	* @param int $plan_id A link to a plan.  Optional.
	* 
	* @return int The new subscription ID
	*/
	
	function SaveSubscription($client_id, $gateway_id, $customer_id, $start_date, $end_date, $next_charge_date, $total_occurrences, $notification_url, $amount, $plan_id = 0)
	{
		$timestamp = date('Y-m-d H:i:s');
		$insert_data = array(
							'client_id' 		=> $client_id,
							'gateway_id' 		=> $gateway_id,
							'customer_id' 		=> $customer_id,
							'plan_id'			=> $plan_id,
							'start_date' 		=> $start_date,
							'end_date'			=> $end_date,
							'next_charge'		=> $next_charge_date,
							'number_occurrences'=> $total_occurrences,
							'notification_url'	=> stripslashes($notification_url),
							'amount'			=> $amount,
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
	*
	* @return bool TRUE upon success.
	*/
	function SaveApiCustomerReference($subscription_id, $api_customer_reference)
	{
		$update_data = array('api_customer_reference' => $api_customer_reference);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
		
		return TRUE;
	}
	
	/**
	* Add a customer payment ID.
	*
	* For API's that require a customer payment profile
	*
	* @param int $subscription_id The subscription_id
	* @param int $api_payment_reference The customer payment id
	*
	* @return bool TRUE upon success.
	*/
	function SaveApiPaymentReference($subscription_id, $api_payment_reference)
	{
		$update_data = array('api_payment_reference' => $api_payment_reference);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
		
		return TRUE;
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
	* Makes a subscription inactive
	*
	* @param int $subscription_id The subscription_id
	*
	* @return bool TRUE upon success.
	*/
	function MakeInactive($subscription_id)
	{
		$update_data = array('active' => 0);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
		
		return TRUE;
	}
	
	/**
	* Get subscription details
	*
	* Returns an array of details about the subscription.
	*
	* @param int $client_id The client ID 
	* @param int $subscription_id The subscription_id
	* 
	* @return array Subscription details
	*/
	function GetSubscriptionDetails($client_id, $subscription_id)
	{
		$this->db->join('client_gateways', 'client_gateways.client_gateway_id = subscriptions.gateway_id', 'inner');
		$this->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$this->db->where('subscriptions.client_id', $client_id);
		$this->db->where('subscription_id', $subscription_id);
		$query = $this->db->get('subscriptions');
		if($query->num_rows() > 0) {
			return $query->row_array();
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
	* @return array|bool Details for a specific subscription or FALSE upon failure
	*/
	
	function GetRecurring ($client_id, $recurring_id)
	{
		// Make sure they only get their own charges
		$this->db->where('subscriptions.client_id', $client_id);
		
		$this->db->where('subscriptions.subscription_id', $recurring_id);
		
		$this->db->join('customers', 'customers.customer_id = subscriptions.customer_id', 'left');
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$this->db->join('plans', 'plans.plan_id = subscriptions.plan_id', 'left');
		$this->db->join('plan_types', 'plan_types.plan_type_id = plans.plan_type_id', 'left');
		$query = $this->db->get('subscriptions');
		
		if ($query->num_rows() == 0) {
			 return FALSE;
		}
		
		$row = $query->row();
		
		$data['id'] = $row->subscription_id;
		$data['create_date'] = $row->timestamp;
		$data['amount'] = $row->amount;
		$data['start_date'] = $row->start_date;
		$data['end_date'] = $row->end_date;
		$data['next_charge_date'] = $row->next_charge;
		$data['number_occurrences'] = $row->number_occurrences;
		$data['notification_url'] = $row->notification_url;
		$data['status'] = ($row->active == '1') ? 'active' : 'cancelled';
		
		if($row->customer_id !== 0) {
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
		}
		
		if($row->plan_id != 0) {
			$data['plan']['id'] = $row->plan_id;
			$data['plan']['plan_type'] = $row->type;
			$data['plan']['name'] = $row->name;
			$data['plan']['amount'] = $row->amount;
			$data['plan']['interval'] = $row->interval;
			$data['plan']['notification_url'] = $row->notification_url;
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
			$this->db->where('subscriptions.customer_id', $params['customer_id']);
		}
		
		if(isset($params['customer_internal_id'])) {
			$this->db->where('customers.internal_id', $params['customer_internal_id']);
		}
		
		if(isset($params['amount'])) {
			$this->db->where('amount', $params['amount']);
		}
		
		if(isset($params['active'])) {
			$this->db->where('subscriptions.active', $params['active']);
		}
		
		if(isset($params['plan_id'])) {
			$this->db->where('subscriptions.plan_id', $params['plan_id']);
		}
		
		if (isset($params['offset'])) {
			$offset = $params['offset'];
		}
		else {
			$offset = 0;
		}
		
		if(isset($params['limit'])) {
			$this->db->limit($params['limit'], $offset);
		}
		
		$this->db->join('customers', 'customers.customer_id = subscriptions.customer_id', 'left');
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$this->db->join('plans', 'plans.plan_id = subscriptions.plan_id', 'left');
		$this->db->join('plan_types', 'plan_types.plan_type_id = plans.plan_type_id', 'left');
		$query = $this->db->get('subscriptions');
		$data = array();
		if($query->num_rows() > 0) {
			$i=0;
			foreach($query->result() as $row) {
				$data[$i]['id'] = $row->subscription_id;
				$data[$i]['create_date'] = $row->timestamp;
				$data[$i]['amount'] = $row->amount;
				$data[$i]['start_date'] = $row->start_date;
				$data[$i]['end_date'] = $row->end_date;
				$data[$i]['next_charge_date'] = $row->next_charge;
				$data[$i]['number_occurrences'] = $row->number_occurrences;
				$data[$i]['notification_url'] = $row->notification_url;
				$data[$i]['status'] = ($row->active == '1') ? 'active' : 'cancelled';
				
				if($row->customer_id !== 0) {
					$data[$i]['customer']['id'] = $row->customer_id;
					$data[$i]['customer']['internal_id'] = $row->internal_id;
					$data[$i]['customer']['first_name'] = $row->first_name;
					$data[$i]['customer']['last_name'] = $row->last_name;
					$data[$i]['customer']['company'] = $row->company;
					$data[$i]['customer']['address_1'] = $row->address_1;
					$data[$i]['customer']['address_2'] = $row->address_2;
					$data[$i]['customer']['city'] = $row->city;
					$data[$i]['customer']['state'] = $row->state;
					$data[$i]['customer']['postal_code'] = $row->postal_code;
					$data[$i]['customer']['country'] = $row->iso2;
					$data[$i]['customer']['email'] = $row->email;
					$data[$i]['customer']['phone'] = $row->phone;
				}
				
				if($row->plan_id != 0) {
					$data[$i]['plan']['plan_id'] = $row->plan_id;
					$data[$i]['plan']['plan_type'] = $row->type;
					$data[$i]['plan']['name'] = $row->name;
					$data[$i]['plan']['amount'] = $row->amount;
					$data[$i]['plan']['interval'] = $row->interval;
					$data[$i]['plan']['notification_url'] = $row->notification_url;
				}
				
				$i++;
			}
		} else {
			return FALSE;
		}
		
		return $data;
	}
	
	/**
	* Updates a subscription based on moving it to a new plan
	*
	* Upgrades or downgrades a subscription to a new plan
	*
	* @param int $client_id The Client ID
	* @param int $recurring_id The ID of the recurring charge
	* @param int $new_plan_id The ID of the new plan
	*
	* @return bool TRUE upon success, FALSE upon failure
	*
	*/
	function ChangeRecurringPlan ($client_id, $recurring_id, $new_plan_id) {
		$plan_details = $this->plan_model->GetPlanDetails($client_id, $new_plan_id);
		
		$update = array(
					'plan_id' => $plan_details->plan_id,
					'amount' => $plan_details->amount,
					'interval' => $plan_details->interval,
					'notification_url' => $plan_details->notification_url,
					'recurring_id' => $recurring_id
					);
					
		return $this->UpdateRecurring($client_id, $update);
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
	* @param int $params['plan_id'] The new plan ID. Optional.
	*
	* @return bool TRUE upon success, FALSE upon failure
	* 
	*/
	function UpdateRecurring($client_id, $params)
	{
		if(!isset($params['recurring_id'])) {
			return FALSE;
		}

		if(isset($params['notification_url'])) {
			$update_data['notification_url'] = $params['notification_url'];
		}
		
		if(isset($params['customer_id'])) {
			$update_data['customer_id'] = $params['customer_id'];
			$this->load->model('customer_model');
			$customer = $this->customer_model->GetCustomerDetails($client_id, $params['customer_id']);
		} else {
			$customer = FALSE;
		}
		
		if(isset($params['amount'])) {
			$update_data['amount'] = $params['amount'];
		}
		
		if(isset($params['plan_id'])) {
			$update_data['plan_id'] = $params['plan_id'];
		}
		
		if(isset($params['next_charge_date'])) {
			$this->load->library('field_validation');
			if ($this->field_validation->ValidateDate($params['next_charge_date'])) {
				$update_data['next_charge'] = $params['next_charge_date'];			
			}
			else {
				die($this->response->Error(5007));
			}
		}
		
		if(isset($params['recur']['interval'])) {
			// Get the subcription details
			$subscription = $this->GetSubscriptionDetails($client_id, $params['recurring_id']);
			$start_date = $subscription['start_date'];
			$end_date = $subscription['end_date'];
			// Figure the total number of occurrences
			$update_data['number_occurrences'] = round((strtotime($end_date) - strtotime($start_date)) / ($params['recur']['interval'] * 86400), 0);
		}
		
		if(!isset($update_data)) {
			die($this->response->Error(6003));
		}
		
		// Make sure they update their own subscriptions
		$this->db->where('client_id', $client_id);
		$this->db->where('subscription_id', $params['recurring_id']);
		
		$this->db->update('subscriptions', $update_data);
		
		// Update the subscription with the gateway
		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($client_id, $subscription->gateway_id);
		$gateway_type = $gateway['name'];
		
		$this->load->library('payment/'.$gateway_type);
		$update_success = $this->$gateway_type->UpdateRecurring($client_id, $gateway, $subscription, $customer, $params);
		
		if(!$update_success) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/*
	* Cancels the recurring billing
	*
	* @param int $client_id The Client ID
	* @param int $recurring_id The recurring charge ID
	*
	* @return bool TRUE upon success, FALSE upon fail
	*
	*/
	
	function CancelRecurring($client_id, $recurring_id)
	{
		// Get the subscription information
		$this->load->model('subscription_model');
		$subscription = $this->subscription_model->GetSubscriptionDetails($client_id, $recurring_id);
		
		// Get the gateway info to load the proper library
		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($client_id, $subscription['gateway_id']);
		
		$gateway_name = $subscription['name'];
		$this->load->library('payment/'.$gateway_name);
		$cancelled = $this->$gateway_name->CancelRecurring($client_id, $subscription, $gateway);
		
		if($cancelled) {
			$this->MakeInactive($recurring_id);
		} else {
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	function GetPlansByCustomer($customer_id)
	{
		$this->db->join('plans', 'plans.plan_id = subscriptions.plan_id', 'inner');
		$this->db->join('plan_types', 'plan_types.plan_type_id = plans.plan_type_id', 'inner');
		$this->db->where('customer_id', $customer_id);
		$this->db->where('subscriptions.plan_id <>', 0);
		$query = $this->db->get('subscriptions');
		if($query->num_rows() > 0) {
			return $query->result();
		} else {
			return FALSE;
		}
	}
}
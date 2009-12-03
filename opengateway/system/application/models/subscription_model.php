<?php

class Subscription_model extends Model
{
	function Subscription_model()
	{
		parent::Model();
	}
	
	// Save a new recurring subscription
	function SaveSubscription($client_id, $gateway_id, $customer_id, $start_date, $end_date, $total_occurrences, $notification_url, $params)
	{
		$timestamp = date('Y-m-d H:i:s');
		$insert_data = array(
							'client_id' 		=> $client_id,
							'gateway_id' 		=> $gateway_id,
							'customer_id' 		=> $customer_id,
							'start_date' 		=> $start_date,
							'end_date'			=> $end_date,
							'number_occurences' => $total_occurrences,
							'notification_url'	=> stripslashes($notification_url),
							'amount'			=> $params['amount'],
							'timestamp'			=> $timestamp
			  				);  					  				
			  				
		$this->db->insert('subscriptions', $insert_data);
		
		return $this->db->insert_id();
	}
	
	function SaveApiCustomerReference($subscription_id, $api_customer_reference)
	{
		$update_data = array('api_customer_reference' => $api_customer_reference);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	function SaveApiPaymentReference($subscription_id, $api_payment_reference)
	{
		$update_data = array('api_payment_reference' => $api_payment_reference);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	function SaveApiAuthNumber($subscription_id, $api_auth_number)
	{
		$update_data = array('api_auth_number' => $api_auth_number);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	function MakeInactive($subscription_id)
	{
		$update_data = array('active' => 0);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	function GetSubscriptionDetails($client_id, $subscription_id)
	{
		$this->db->where('client_id', $client_id);
		$this->db->where('subscription_id', $subscription_id);
		$query = $this->db->get('subscriptions');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			die($this->response->Error(5000));
		}
	}
	
	function GetRecurring($client_id, $params)
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
		
		if(isset($params['limit'])) {
			$this->db->limit($params['limit']);
		} else {
			$this->db->limit($this->config->item('query_result_default_limit'));
		}
		
		$this->db->join('customers', 'customers.customer_id = subscriptions.customer_id', 'left');
		$query = $this->db->get('subscriptions');
		if($query->num_rows() > 0) {
			$data['results'] = $query->num_rows();
			$i=0;
			foreach($query->result() as $row) {
				$data['recurrrings']['recurring'][$i]['create_date'] = $row->timestamp;
				$data['recurrrings']['recurring'][$i]['amount'] = $row->amount;
				$data['recurrrings']['recurring'][$i]['start_date'] = $row->start_date;
				$data['recurrrings']['recurring'][$i]['end_date'] = $row->end_date;
				$data['recurrrings']['recurring'][$i]['number_occurences'] = $row->number_occurrences;
				$data['recurrrings']['recurring'][$i]['notification_url'] = $row->notification_url;
				
				if($row->subscription_id != 0) {
					$data['recurrrings']['recurring'][$i]['recurring_id'] = $row->subscription_id;
				}
				
				if($row->customer_id !== 0) {
					$data['recurrrings']['recurring'][$i]['customer']['id'] = $row->customer_id;
					$data['recurrrings']['recurring'][$i]['customer']['internal_id'] = $row->internal_id;
					$data['recurrrings']['recurring'][$i]['customer']['firstname'] = $row->first_name;
					$data['recurrrings']['recurring'][$i]['customer']['lastname'] = $row->last_name;
					$data['recurrrings']['recurring'][$i]['customer']['company'] = $row->company;
					$data['recurrrings']['recurring'][$i]['customer']['address_1'] = $row->address_1;
					$data['recurrrings']['recurring'][$i]['customer']['address_2'] = $row->address_2;
					$data['recurrrings']['recurring'][$i]['customer']['city'] = $row->city;
					$data['recurrrings']['recurring'][$i]['customer']['state'] = $row->state;
					$data['recurrrings']['recurring'][$i]['customer']['postal_code'] = $row->postal_code;
					$data['recurrrings']['recurring'][$i]['customer']['email'] = $row->email;
					$data['recurrrings']['recurring'][$i]['customer']['phone'] = $row->phone;
				}
				
				$i++;
			}
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
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
		
		$this->MakeInactive($params['recurring_id']);
		
		$response = $this->response->TransactionResponse(101,array());
		
		return $response;
	}
}
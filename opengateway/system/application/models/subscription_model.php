<?php

class Subscription_model extends Model
{
	function Subscription_model()
	{
		parent::Model();
	}
	
	// Save a new recurring subscription
	function SaveSubscription($client_id, $gateway_id, $customer_id, $start_date, $end_date, $total_occurrences, $params)
	{
		$insert_data = array(
							'client_id' 		=> $client_id,
							'gateway_id' 		=> $gateway_id,
							'customer_id' 		=> $customer_id,
							'start_date' 		=> $start_date,
							'end_date'			=> $end_date,
							'number_occurences' => $total_occurrences,
							'amount'			=> $params['amount']
			  				);

		if(isset($params['notification_url'])) {
			$insert_data['notification_url'] = $params['notification_url'];
		}	  				
			  				
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
	
	function MakeInactive($subscription_id)
	{
		$update_data = array('active' => 0);
		
		$this->db->where('subscription_id', $subscription_id);
		$this->db->update('subscriptions', $update_data);
	}
	
	function GetRecurringDetails($client_id, $order_id)
	{
		$this->db->where('client_id', $client_id);
		$this->db->where('order_id', $order_id);
		$query = $this->db->get('recurring_payments');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			die($this->response->Error(5000));
		}
	}
}
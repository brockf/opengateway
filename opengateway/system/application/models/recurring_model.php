<?php

class Recurring_model extends Model
{
	function Recurring_model()
	{
		parent::Model();
	}
	
	// Save a new recurring subscription
	function SaveRecurring($client_id, $gateway_id, $customer_id, $start_date, $end_date, $total_occurrences, $params, $api_reference)
	{
		$insert_data = array(
							'client_id' 		=> $client_id,
							'gateway_id' 		=> $gateway_id,
							'customer_id' 		=> $customer_id,
							'notification_url'	=> $params['notification_url'],
							'start_date' 		=> $start_date,
							'end_date'			=> $end_date,
							'number_occurences' => $total_occurrences,
							'amount'			=> $params['amount'],
							'api_reference'		=> $api_reference
			  				);
			  				
		$this->db->insert('subscriptions', $insert_data);
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
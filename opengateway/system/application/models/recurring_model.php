<?php

class Recurring_model extends Model
{
	function Recurring_model()
	{
		parent::Model();
	}
	
	// Save a new recurring subscription
	function SaveRecurring($client_id, $gateway_id, $order_id, $customer_id, $params, $api_reference)
	{
		$insert_data = array(
							'client_id' 		=> $client_id,
							'gateway_id' 		=> $gateway_id,
							'customer_id' 		=> $customer_id,
							'order_id'			=> $order_id,
							'start_date' 		=> $params['start_date'],
							'number_occurences' => $params['total_occurences'],
							'amount'			=> $params['amount'],
							'api_reference'		=> $api_reference
			  				);
			  				
		$this->db->insert('recurring_payments', $insert_data);
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
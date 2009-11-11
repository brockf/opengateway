<?php

class Order_model extends Model
{
	function Order_Model()
	{
		parent::Model();
	}
	
	function CreateNewOrder($client_id, $params)
	{
		$timestamp = date('Y-m-d H:i:s');
		$insert_data = array(
							'client_id' 	 => $client_id,
							'gateway_id' 	 => $params['gateway_id'],
							'card_last_four' => substr($params['card_num'],-4,4),
							'amount'		 => $params['amount'],
							'timestamp'		 => $timestamp
							);	
		
		$this->db->insert('orders', $insert_data);
		
		return $this->db->insert_id();
		
	}
}
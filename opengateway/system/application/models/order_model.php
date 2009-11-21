<?php

class Order_model extends Model
{
	function Order_Model()
	{
		parent::Model();
	}
	
	function CreateNewOrder($client_id, $params, $credit_card, $subscription_id = 0)
	{
		
		$timestamp = date('Y-m-d H:i:s');
		$insert_data = array(
							'client_id' 	  => $client_id,
							'gateway_id' 	  => $params['gateway_id'],
							'subscription_id' => $subscription_id,
							'card_last_four'  => substr($credit_card->card_num,-4,4),
							'amount'		  => $params['amount'],
							'timestamp'		  => $timestamp
							);	
		
		if(isset($params['customer_ip_address'])) {
			$insert_data['customer_ip_address'] = $params['customer_ip_address'];
		}					
		
							
		$this->db->insert('orders', $insert_data);
		
		return $this->db->insert_id();
		
	}
	
	function GetOrder($client_id, $order_id)
	{
		$this->db->join('order_authorizations', 'order_authorizations.order_id = orders.order_id', 'inner');
		$this->db->where('client_id', $client_id);
		$this->db->where('orders.order_id', $order_id);
		$query = $this->db->get('orders');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			die($this->response->Error(4001));
		}
	}
}
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

		if(isset($params['customer_id'])) {
			$insert_data['customer_id'] = $params['customer_id'];
		}
		
							
		$this->db->insert('orders', $insert_data);
		
		return $this->db->insert_id();
		
	}
	
	function GetCharges($client_id, $params)
	{
		
		// Make sure they only get their own charges
		$this->db->where('orders.client_id', $client_id);
		
		// Check which search paramaters are set
		
		if(isset($params['gateway_id'])) {
			$this->db->where('gateway_id', $params['gateway_id']);
		}
		
		if(isset($params['start_date'])) {
			$start_date = date('Y-m-d H:i:s', strtotime($params['start_date']));
			$this->db->where('timestamp >=', $start_date);
		}
		
		if(isset($params['end_date'])) {
			$end_date = date('Y-m-d H:i:s', strtotime($params['end_date']));
			$this->db->where('timestamp <=', $end_date);
		}
		
		if(isset($params['customer_id'])) {
			$this->db->where('orders.customer_id', $params['customer_id']);
		}
		
		if(isset($params['customer_internal_id'])) {
			$this->db->where('customers.internal_id', $params['customer_internal_id']);
		}
		
		if(isset($params['recurring_only']) && $params['recurring_only'] == 1) {
			$this->db->where('orders.subscription_id <>', 0);
		}
		
		if(isset($params['limit'])) {
			$this->db->limit($params['limit']);
		} else {
			$this->db->limit($this->config->item('query_result_default_limit'));
		}
		
		$this->db->join('customers', 'customers.customer_id = orders.customer_id', 'left');
		$query = $this->db->get('orders');
		if($query->num_rows() > 0) {
			$data['results'] = $query->num_rows();
			$i=0;
			foreach($query->result() as $row) {
				$data['charges']['charge'][$i]['id'] = $row->order_id;
				$data['charges']['charge'][$i]['gateway_id'] = $row->gateway_id;
				$data['charges']['charge'][$i]['date'] = $row->timestamp;
				$data['charges']['charge'][$i]['amount'] = $row->amount;
				$data['charges']['charge'][$i]['card_last_four'] = $row->card_last_four;
				
				if($row->subscription_id != 0) {
					$data['charges']['charge'][$i]['recurring_id'] = $row->subscription_id;
				}
				
				if($row->customer_id != 0) {
					$data['charges']['charge'][$i]['customer']['id'] = $row->customer_id;
					$data['charges']['charge'][$i]['customer']['internal_id'] = $row->internal_id;
					$data['charges']['charge'][$i]['customer']['firstname'] = $row->first_name;
					$data['charges']['charge'][$i]['customer']['lastname'] = $row->last_name;
					$data['charges']['charge'][$i]['customer']['company'] = $row->company;
					$data['charges']['charge'][$i]['customer']['address_1'] = $row->address_1;
					$data['charges']['charge'][$i]['customer']['address_2'] = $row->address_2;
					$data['charges']['charge'][$i]['customer']['city'] = $row->city;
					$data['charges']['charge'][$i]['customer']['state'] = $row->state;
					$data['charges']['charge'][$i]['customer']['postal_code'] = $row->postal_code;
					$data['charges']['charge'][$i]['customer']['email'] = $row->email;
					$data['charges']['charge'][$i]['customer']['phone'] = $row->phone;
				}
				
				$i++;
			}
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
	function GetCharge($client_id, $params)
	{
		// Get the gateway type
		if(!isset($params['charge_id'])) {
			die($this->response->Error(6000));
		}
		
		$this->db->join('order_authorizations', 'order_authorizations.order_id = orders.order_id', 'inner');
		$this->db->where('client_id', $client_id);
		$this->db->where('orders.order_id', $xml->charge_id);
		$this->db->limit(1);
		$query = $this->db->get('orders');
		if($query->num_rows() > 0) {
			$row = $query->row();
			$data['results'] = 1;
			$data['charge']['id'] = $row->order_id;
			$data['charge']['gateway_id'] = $row->gateway_id;
			$data['charge']['date'] = $row->timestamp;
			$data['charge']['amount'] = $row->amount;
			$data['charge']['card_last_four'] = $row->card_last_four;
				
			if($row->subscription_id != 0) {
					$data['charge']['recurring_id'] = $row->subscription_id;
				}
				
			if($row->customer_id != 0) {
				$data['charge']['customer']['id'] = $row->customer_id;
				$data['charge']['customer']['internal_id'] = $row->internal_id;
				$data['charge']['customer']['firstname'] = $row->first_name;
				$data['charge']['customer']['lastname'] = $row->last_name;
				$data['charge']['customer']['company'] = $row->company;
				$data['charge']['customer']['address_1'] = $row->address_1;
				$data['charge']['customer']['address_2'] = $row->address_2;
				$data['charge']['customer']['city'] = $row->city;
				$data['charge']['customer']['state'] = $row->state;
				$data['charge']['customer']['postal_code'] = $row->postal_code;
				$data['charge']['customer']['email'] = $row->email;
				$data['charge']['customer']['phone'] = $row->phone;
			}
				
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
}
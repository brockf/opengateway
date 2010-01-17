<?php
/**
* Order Model 
*
* Contains all the methods used to create and search orders.
*
* @version 1.0
* @author David Ryan
* @package OpenGateway

*/
class Order_model extends Model
{
	function Order_Model()
	{
		parent::Model();
	}
	
	/**
	* Create a new order.
	*
	* Creates a new order.
	*
	* @param int $client_id The client ID of the gateway client.
	* @param int $subscription_id If this order is part of a recurring subscription. Optional.
	* @param string $params['gateway_id'] The gateway ID used for the order.
	* @param string $params['credit_card']['card_num'] The card number used to save the last 4 digits.
	* @param string $params['amount'] The amount of the order.
	* @param string $params['customer_id'] The customer id associated with the order. Optional.
	* @param string $params['customer_ip_address'] Customer's IP address. Optional.
	* 
	* @return int $order_id The new order id
	*/
	function CreateNewOrder($client_id, $params, $subscription_id = 0)
	{
		$timestamp = date('Y-m-d H:i:s');
		$insert_data = array(
							'client_id' 	  => $client_id,
							'gateway_id' 	  => $params['gateway_id'],
							'subscription_id' => $subscription_id,
							'amount'		  => $params['amount'],
							'timestamp'		  => $timestamp
							);	
		
		if(isset($params['credit_card']['card_num'])) {
			$insert_data['card_last_four']  = substr($params['credit_card']['card_num'],-4,4);
		}					
							
		if(isset($params['customer_ip_address'])) {
			$insert_data['customer_ip_address'] = $params['customer_ip_address'];
		}

		if(isset($params['customer_id'])) {
			$insert_data['customer_id'] = $params['customer_id'];
		}
		
							
		$this->db->insert('orders', $insert_data);
		
		return $this->db->insert_id();	
	}
	
	/**
	* Search Orders.
	*
	* Returns an array of results based on submitted search criteria.  All fields are optional.
	*
	* @param int $client_id The client ID.
	* @param int $params['gateway_id'] The gateway ID used for the order. Optional.
	* @param date $params['start_date'] Only orders after or on this date will be returned. Optional.
	* @param date $params['end_date'] Only orders before or on this date will be returned. Optional.
	* @param int $params['customer_id'] The customer id associated with the order. Optional.
	* @param string $params['customer_internal_id'] The customer's internal id associated with the order. Optional.
	* @param int $params['id'] The charge ID.  Optional.
	* @param string $params['amount'] The amount of the charge.  Optional.
	* @param string $params['customer_last_name'] The last name of the customer.  Optional.
	* @param int $params['status'] Set to ok/failed to filter results.  Optional.
	* @param int $params['subscription_id'] Returns only recurring orders that have this subscription ID. Optional.
	* @param boolean $params['recurring_only'] Returns only orders that are part of a recurring subscription. Optional.
	* @param int $params['offset'] Offsets the database query.
	* @param int $params['limit'] Limits the number of results returned. Optional.
	* @param string $params['sort'] Variable used to sort the results.  Possible values are date, customer_first_name, customer_last_name, amount. Optional
	* @param string $params['sort_dir'] Used when a sort param is supplied.  Possible values are asc and desc. Optional.
	* 
	* @return array|bool Charge results or FALSE upon failure
	*/
	
	function GetCharges($client_id, $params)
	{
		// Make sure they only get their own charges
		$this->db->where('orders.client_id', $client_id);
		
		// Check which search paramaters are set
		
		if(isset($params['gateway_id'])) {
			$this->db->where('gateway_id', $params['gateway_id']);
		}
		
		$this->load->library('field_validation');
		
		if(isset($params['start_date'])) {
			$valid_date = $this->field_validation->ValidateDate($params['start_date']);
			if(!$valid_date) {
				die($this->response->Error(5007));
			}
			
			$start_date = date('Y-m-d H:i:s', strtotime($params['start_date']));
			$this->db->where('timestamp >=', $start_date);
		}
		
		if(isset($params['end_date'])) {
			$valid_date = $this->field_validation->ValidateDate($params['start_date']);
			if(!$valid_date) {
				die($this->response->Error(5007));
			}
			
			$end_date = date('Y-m-d H:i:s', strtotime($params['end_date']));
			$this->db->where('timestamp <=', $end_date);
		}
		
		if(isset($params['customer_id'])) {
			$this->db->where('orders.customer_id', $params['customer_id']);
		}
		
		if(isset($params['amount'])) {
			$this->db->where('orders.amount', $params['amount']);
		}
		
		if(isset($params['id'])) {
			$this->db->where('orders.order_id', $params['id']);
		}
		
		if(isset($params['customer_id'])) {
			$this->db->where('orders.customer_id', $params['customer_id']);
		}
		
		if(isset($params['customer_last_name'])) {
			$this->db->like('customers.last_name',$params['customer_last_name']);
		}
		
		if(isset($params['customer_internal_id'])) {
			$this->db->where('customers.internal_id', $params['customer_internal_id']);
		}
		
		if(isset($params['recurring_only']) && $params['recurring_only'] == 1) {
			$this->db->where('orders.subscription_id <>', 0);
		}
		
		if (isset($params['status'])) {
			$status = ($params['status'] == 'ok') ? '1' : '0';
			$this->db->where('orders.status',$status);
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
		
		if(isset($params['sort_dir']) and ($params['sort_dir'] == 'asc' or $params['sort_dir'] == 'desc' )) {
			$sort_dir = $params['sort_dir'];
		}
		else {
			$sort_dir = 'DESC';
		}
		
		$params['sort'] = isset($params['sort']) ? $params['sort'] : '';
		
		switch($params['sort'])
		{
			case 'date':
				$sort = 'timestamp';
				break;
			case 'customer_first_name':
				$sort = 'first_name';
				break;
			case 'customer_last_name':
				$sort = 'last_name';
				break;	
			case 'amount':
				$sort = 'amount';
				break;
			default:
				$sort = 'timestamp';
				break;	
		}
		
		$sort_dir = isset($params['sort_dir']) ? $params['sort_dir'] : 'ASC';
		
		
		$this->db->order_by($sort, $sort_dir);	
		
		$this->db->join('customers', 'customers.customer_id = orders.customer_id', 'left');
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		
		$query = $this->db->get('orders');
		
		// We need to get the gmt_offset to show the time in the local timezone
		$CI =& get_instance();
		$CI->load->model('client_model');
		$gmt_offset = $CI->client_model->GetTimezone($client_id);
		
		$data = array();
		if($query->num_rows() > 0) {
			$i=0;
			foreach($query->result() as $row) {
				$data[$i]['id'] = $row->order_id;
				$data[$i]['gateway_id'] = $row->gateway_id;
				$timestamp = strtotime($row->timestamp);
				$data[$i]['date'] = date('Y-m-d H:i:s', mktime(date('H', $timestamp + $gmt_offset), date('i', $timestamp), date('s', $timestamp), date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)));
				$data[$i]['amount'] = $row->amount;
				$data[$i]['card_last_four'] = $row->card_last_four;
				$data[$i]['status'] = ($row->status == '1') ? 'ok' : 'failed';
				
				if($row->subscription_id != 0) {
					$data[$i]['recurring_id'] = $row->subscription_id;
				}
				
				if($row->customer_id != 0) {
					$data[$i]['customer']['id'] = $row->customer_id;
					$data[$i]['customer']['internal_id'] = $row->internal_id;
					$data[$i]['customer']['first_name'] = $row->first_name;
					$data[$i]['customer']['last_name'] = $row->last_name;
					$data[$i]['customer']['company'] = $row->company;
					$data[$i]['customer']['address_1'] = $row->address_1;
					$data[$i]['customer']['address_2'] = $row->address_2;
					$data[$i]['customer']['city'] = $row->city;
					$data[$i]['customer']['state'] = $row->state;
					$data[$i]['customer']['country'] = $row->iso2;
					$data[$i]['customer']['postal_code'] = $row->postal_code;
					$data[$i]['customer']['email'] = $row->email;
					$data[$i]['customer']['phone'] = $row->phone;
					$data[$i]['customer']['date_created'] = $row->date_created;
					$data[$i]['customer']['status'] = ($row->active == 1) ? 'active' : 'deleted';
				}
				
				$i++;
			}
		} else {
			return FALSE;
		}
		
		return $data;
	}
	
	/**
	* Get Details of a specific order.
	*
	* Returns array of order details for a specific order_id.
	*
	* @param int $client_id The client ID.
	* @param int $charge_id The order id to search for.
	* 
	* @return array|bool Array with charge info, FALSE upon failure.
	*/
	
	function GetCharge($client_id, $charge_id)
	{
		$this->db->join('order_authorizations', 'order_authorizations.order_id = orders.order_id', 'inner');
		$this->db->join('customers', 'customers.customer_id = orders.customer_id', 'left');
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$this->db->where('orders.client_id', $client_id);
		$this->db->where('orders.order_id', $charge_id);
		$this->db->limit(1);
		$query = $this->db->get('orders');
		if($query->num_rows() > 0) {
			$row = $query->row();
			$data['id'] = $row->order_id;
			$data['gateway_id'] = $row->gateway_id;
			$data['date'] = $row->timestamp;
			$data['amount'] = $row->amount;
			$data['card_last_four'] = $row->card_last_four;
			$data['status'] = ($row->status == 1) ? 'ok' : 'failed';
				
			if($row->subscription_id != 0) {
					$data['recurring_id'] = $row->subscription_id;
				}
				
			if($row->customer_id != 0) {
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
				$data['customer']['date_created'] = $row->date_created;
				$data['customer']['status'] = ($row->active == 1) ? 'active' : 'deleted';
			}
				
		} else {
			return FALSE;
		}
		
		return $data;
	}
	
	/**
	* Get Details of the last order for a customer.
	*
	* Returns array of order details for a specific order_id.
	*
	* @param int $client_id The client ID.
	* @param int $customer_id The customer ID.
	* 
	* @return array|bool Array with charge details or FALSE upon failure
	*/
	
	function GetLatestCharge($client_id, $customer_id)
	{	
		$this->db->join('order_authorizations', 'order_authorizations.order_id = orders.order_id', 'inner');
		$this->db->join('customers', 'customers.customer_id = orders.customer_id', 'left');
		$this->db->join('countries', 'countries.country_id = customers.country', 'left');
		$this->db->where('orders.client_id', $client_id);
		$this->db->where('orders.customer_id', $customer_id);
		$this->db->order_by('timestamp', 'DESC');
		$this->db->limit(1);
		$query = $this->db->get('orders');
		if($query->num_rows() > 0) {
			$row = $query->row();
			return $this->GetCharge($client_id, $row->order_id);	
		} else {
			return FALSE;
		}
	}
	

	
	/**
	* Set the status of an order to either 1 or 0
	*
	* @param int $order_id The Order ID
	* @param int $status The status ID.  Default to 0.
	*
	* @return bool TRUE upon success.
	*/
	
	function SetStatus($order_id, $status = 0)
	{
		$update_data['status'] = $status;
		$this->db->where('order_id', $order_id);
		$this->db->update('orders', $update_data);
		
		return TRUE;
	}
	
	/**
	* Get order authorization details
	* 
	* Returns order authorization information.
	*
	* @param int $order_id The Order ID
	*
	* @return mixed Array containg authorization details
	*/
	
	function GetChargeGatewayInfo($order_id)
	{
		$this->db->where('order_id',  $order_id);
		$this->db->join('order_authorizations', 'orders.order_id = order_authorizations.order_id', 'left');
		$query = $this->db->get('orders');
		if($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return FALSE;
		}
	}
}
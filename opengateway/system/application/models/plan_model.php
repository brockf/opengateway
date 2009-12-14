<?php

class Plan_model extends Model
{
	function Plan_Model()
	{
		parent::Model();
	}
	
	function NewPlan($client_id, $params)
	{
		// Get the plan params
		$plan = $params['plan'];
		
		$this->load->library('field_validation');
		
		if(isset($plan['plan_type'])) {
			$plan_type_id = $this->GetPlanTypeId($plan['plan_type']);
			$insert_data['plan_type_id'] = $plan_type_id;
		} else {
			die($this->response->Error(1004));
		}
		
		if($plan['plan_type'] == 'free') {
			$insert_data['amount'] = 0;
		} else {
			if(isset($plan['amount'])) {
				if(!$this->field_validation->ValidateAmount($plan['amount'])) {
					die($this->response->Error(5009));	
				}
				$insert_data['amount'] = $plan['amount'];
			} else {
				die($this->response->Error(1004));
			}
		}
		
		
		if(isset($plan['interval'])) {
			if(!is_numeric($plan['interval']) || $plan['interval'] < 1) {
				die($this->response->Error(5011));
			}	
			$insert_data['interval'] = $plan['interval'];
		} else {
			die($this->response->Error(1004));
		}
		
		if(isset($plan['notification_url'])) {	
			$insert_data['notification_url'] = $plan['notification_url'];
		}
		
		if(isset($plan['name'])) {	
			$insert_data['name'] = $plan['name'];
		} else {
			die($this->response->Error(1004));
		}
		
		if(isset($plan['free_trial'])) {
			if(!is_numeric($plan['free_trial']) || $plan['free_trial'] < 0) {
				die($this->response->Error(7002));
			}	
			$insert_data['free_trial'] = $plan['free_trial'];
		} else {
			die($this->response->Error(1004));
		}
		
		$insert_data['client_id'] = $client_id;
		$insert_data['deleted'] = 0;
							
		$this->db->insert('plans', $insert_data);
		
		$response_array['plan_id'] = $this->db->insert_id(); 
		$response = $this->response->TransactionResponse(500, $response_array);
		
		return $response;
	}
	
	function UpdatePlan($client_id, $params)
	{
		// Get the plan params
		$plan = $params['plan'];
		
		// Get the plan details
		$plan_details = $this->GetPlanDetails($client_id, $params['plan_id']);
		
		$this->load->library('field_validation');
		
		if(isset($plan['plan_type'])) {
			$plan_type_id = $this->GetPlanTypeId($plan['plan_type']);
			$update_data['plan_type_id'] = $plan_type_id;
		}
		
		if($plan['plan_type'] == 'free') {
			$update_data['amount'] = 0;
		} else {
			if(isset($plan['amount'])) {
				if(!$this->field_validation->ValidateAmount($plan['amount'])) {
					die($this->response->Error(5009));	
				}
				$update_data['amount'] = $plan['amount'];
			} else {
				die($this->response->Error(1004));
			}
		}
		
		if(isset($plan['interval'])) {
			if(!is_numeric($plan['interval']) || $plan['interval'] < 1) {
				die($this->response->Error(5011));
			}	
			$update_data['interval'] = $plan['interval'];
		}
		
		if(isset($plan['notification_url'])) {	
			$update_data['notification_url'] = $plan['notification_url'];
		}
		
		if(isset($plan['name'])) {	
			$update_data['name'] = $plan['name'];
		}
		
		if(isset($plan['free_trial'])) {
			if(!is_numeric($plan['free_trial']) || $plan['free_trial'] < 0) {
				die($this->response->Error(7002));
			}	
			$update_data['free_trial'] = $plan['free_trial'];
		}
		
		if(!isset($update_data)) {
			die($this->response->Error(6003));
		}
		
		$this->db->where('plan_id', $plan_details->plan_id);
		$this->db->update('plans', $update_data);
		
		$response = $this->response->TransactionResponse(501, array());
		
		return $response;
	}
	
	function GetPlan($client_id, $params)
	{
		// Get the plan details
		$plan_details = $this->GetPlanDetails($client_id, $params['plan_id']);
		
		$plan_type = $this->GetPlanType($plan_details->plan_type_id);
		
		unset($plan_details->client_id);
		unset($plan_details->plan_type_id);
		
		$plan_details->type = $plan_type;
		
		foreach($plan_details as $key => $value)
		{
			$data['plan'][$key] = $value;
		}
		
		return $data;
	}
	
	function GetPlans($client_id, $params)
	{
		
		if(isset($params['plan_type'])) {
			$plan_type_id = $this->GetPlanTypeId($params['plan_type']);
			$this->db->where('plans.plan_type_id', $plan_type_id);
		}
		
		if(isset($params['amount'])) {
			$this->db->where('amount', $params['amount']);
		}
		
		if(isset($params['interval'])) {
			$this->db->where('interval', $params['interval']);
		}
		
		if(isset($params['notification_url'])) {
			$this->db->where('notification_url', $params['notification_url']);
		}
		
		if(isset($params['name'])) {
			$this->db->where('name', $params['name']);
		}
		
		if(isset($params['free_trial'])) {
			$this->db->where('free_trial', $params['free_trial']);
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
		
		$this->db->join('plan_types', 'plans.plan_type_id = plan_types.plan_type_id', 'inner');
		$this->db->where('client_id', $client_id);
		$this->db->where('deleted', 0);
		$query = $this->db->get('plans');
		if($query->num_rows() > 0) {
			$data['results'] = $query->num_rows();
			$i=0;
			foreach($query->result() as $row)
			{
				$data['plans']['plan'][$i]['id'] = $row->plan_id;
				$data['plans']['plan'][$i]['type'] = $row->type;
				$data['plans']['plan'][$i]['name'] = $row->name;
				$data['plans']['plan'][$i]['amount'] = $row->amount;
				$data['plans']['plan'][$i]['interval'] = $row->interval;
				$data['plans']['plan'][$i]['notification_url'] = $row->notification_url;
				$data['plans']['plan'][$i]['free_trial'] = $row->free_trial;
				$i++;
			}
			
		} else {
			$data['results'] = 0;
		}
		
		return $data;
	}
	
	function DeletePlan($client_id, $params)
	{
		// Get the plan details
		$plan_details = $this->GetPlanDetails($client_id, $params['plan_id']);
		
		$update_data['deleted'] = 1;
		$this->db->where('plan_id', $plan_details->plan_id);
		$this->db->update('plans', $update_data);
		
		$response = $this->response->TransactionResponse(502, array());
		
		return $response;
		
	}
	
	function GetPlanDetails($client_id, $plan_id)
	{
		$this->db->where('client_id', $client_id);
		$this->db->where('plan_id', $plan_id);
		$this->db->where('deleted', 0);
		$query = $this->db->get('plans');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			die($this->response->Error(7001));
		}
	
	}
	
	function GetPlanTypeId($type)
	{
		$this->db->where('type', $type);
		$query = $this->db->get('plan_types');
		if($query->num_rows() > 0) {
			$plan_type_id = $query->row()->plan_type_id;
		} else {
			die($this->response->Error(7000));
		}
		
		return $plan_type_id;
	}
	
	function GetPlanType($plan_type_id)
	{
		$this->db->where('plan_type_id', $plan_type_id);
		$query = $this->db->get('plan_types');
		if($query->num_rows() > 0) {
			$plan_type = $query->row()->type;
		} else {
			die($this->response->Error(7000));
		}
		
		return $plan_type;
	}
}
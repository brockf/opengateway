<?php
/**
* Email Model 
*
* Contains all the methods used to create, update, and delete client Emails.
*
* @version 1.0
* @author David Ryan
* @package OpenGateway

*/
class Email_model extends Model
{
	function Email_model()
	{
		parent::Model();
	}
	
	function GetTriggerId($trigger_name)
	{
		$this->db->where('system_name', $trigger_name);
		$query = $this->db->get('email_triggers');
		if($query->num_rows() > 0) {
			return $query->row()->email_trigger_id;
		} else {
			return FALSE;
		}
	}
	
	function GetTriggers() {
		$query = $this->db->get('email_triggers');
		foreach ($query->result_array() as $row) {
			$return[] = $row;
		}
		
		return $return;
	}
	
	function SaveEmail($client_id, $trigger_id, $params)
	{
		$insert_data['client_id'] = $client_id;
		$insert_data['trigger_id'] = $trigger_id;
		$insert_data['email_subject'] = $params['email_subject'];
		
		$insert_data['from_name'] = $params['from_name'];
		$insert_data['from_email'] = $params['from_email'];
		$insert_data['active'] = 1;
		$insert_data['email_body'] = $params['email_body'];
		
		if(isset($params['plan'])) {
			$insert_data['plan_id'] = $params['plan'];
		} else {
			$insert_data['plan_id'] = '';
		}
		
		if(isset($params['is_html']) and $params['is_html'] == '1') {
			$insert_data['is_html'] = $params['is_html'];
		} else {
			$insert_data['is_html'] = 0;
		}
		
		if(isset($params['to_address'])) {
			$insert_data['to_address'] = $params['to_address'];
		}
		else {
			$insert_data['to_address'] == 'customer';
		}
		
		if(isset($params['bcc_address'])) {
			$insert_data['bcc_address'] = $params['bcc_address'];
		} else {
			$insert_data['bcc_address'] = '';
		}
		
		$this->db->insert('client_emails', $insert_data);
		
		return $this->db->insert_id();
	}
	
	function UpdateEmail($client_id, $email_id, $params, $trigger_id = FALSE)
	{
		
		if($trigger_id) {
			$update_data['trigger_id'] = $trigger_id;
		}
		
		if(isset($params['plan'])) {
			$update_data['plan_id'] = $params['plan'];
		}
		
		if(isset($params['email_subject'])) {
			$update_data['email_subject'] = $params['email_subject'];
		}
	
		if(isset($params['email_body'])) {
			$update_data['email_body'] = $params['email_body'];
		}
		
		if(isset($params['from_name'])) {
			$update_data['from_name'] = $params['from_name'];
		}
		
		if(isset($params['from_email'])) {
			$update_data['from_email'] = $params['from_email'];
		}
		
		if(isset($params['is_html'])) {
			$update_data['is_html'] = $params['is_html'];
		}
		
		if(isset($params['to_address'])) {
			$update_data['to_address'] = $params['to_address'];
		}
		
		if(isset($params['bcc_address'])) {
			$update_data['bcc_address'] = $params['bcc_address'];
		}
		
		$this->db->where('client_email_id', $email_id);
		$this->db->where('client_id', $client_id);
		
		$this->db->update('client_emails', $update_data);

		return TRUE;
	}
	
	function DeleteEmail($client_id, $email_id)
	{
		$update_data['active'] = 0;
		
		$this->db->where('client_id', $client_id);
		$this->db->where('client_email_id', $email_id);
		
		$this->db->update('client_emails', $update_data);
	}
	
	function GetEmailVariables($trigger_id)
	{
		$this->db->select('available_variables');
		$this->db->where('email_trigger_id', $trigger_id);
		$query = $this->db->get('email_triggers');
		if($query->num_rows() > 0) {
			$vars = unserialize($query->row()->available_variables);
			foreach($vars as $var) {
				$result[] = $var;
			}
			return $result;
		} else {
			return FALSE;
		}
	}
	
	function GetEmail($client_id, $email_id)
	{
		$this->db->join('email_triggers', 'email_triggers.email_trigger_id = client_emails.trigger_id', 'inner');
		$this->db->join('plans', 'client_emails.plan_id = plans.plan_id', 'left');
		$this->db->where('client_id', $client_id);
		$this->db->where('client_email_id', $email_id);
		
		$this->db->limit(1);
		$query = $this->db->get('client_emails');
		if($query->num_rows() > 0) {
			$row = $query->row_array();
			
			$array = array(
							'id' => $row['client_email_id'],
							'trigger' => $row['system_name'],
							'email_subject' => $row['email_subject'],
							'email_body' => $row['email_body'],
							'from_name' => $row['from_name'],
							'from_email' => $row['from_email'],
							'is_html' => $row['is_html'],
							'to_address' => $row['to_address'],
							'bcc_address' => $row['bcc_address'],
							'plan_id' => $row['plan_id'],
							);
							
			if (isset($row['plan_name'])) {
				$array['plan_name'] = $row['name'];
			}
							
			return $array;
		} else {
			return FALSE;
		}
	}
	
	function GetEmails($client_id, $params)
	{		
		if(isset($params['deleted']) and $params['deleted'] == '1') {
			$this->db->where('active', '0');
		}
		else {
			$this->db->where('active', '1');
		}
		
		if(isset($params['trigger'])) {
			$trigger_id = (!is_numeric($params['trigger'])) ? $this->GetTriggerId($params['trigger']) : $params['trigger'];
			$this->db->where('trigger', $trigger_id);
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
		
		$this->db->join('email_triggers', 'email_triggers.email_trigger_id = client_emails.trigger_id', 'inner');
		$this->db->join('plans', 'client_emails.plan_id = plans.plan_id', 'left');
		$this->db->where('client_id', $client_id);
		$query = $this->db->get('client_emails');
		$data = array();
		if($query->num_rows() > 0) {
			foreach($query->result_array() as $row)
			{
				$array = array(
								'id' => $row['client_email_id'],
								'trigger' => $row['system_name'],
								'email_subject' => $row['email_subject'],
								'email_body' => $row['email_body'],
								'from_name' => $row['from_name'],
								'from_email' => $row['from_email'],
								'is_html' => $row['is_html'],
								'to_address' => $row['to_address'],
								'bcc_address' => $row['bcc_address'],
								'plan_id' => $row['plan_id'],
								);
								
				if (isset($row['plan_name'])) {
					$array['plan_name'] = $row['name'];
				}
								
				$data[] = $array;
			}
			
		} else {
			return FALSE;
		}
		
		return $data;
	}
	
	function GetEmailsByTrigger($client_id, $trigger_type_id, $plan_id = false)
	{
		$this->db->join('email_triggers', 'email_triggers.email_trigger_id = client_emails.trigger_id', 'inner');
		$this->db->where('client_id', $client_id);
		$this->db->where('trigger_id', $trigger_type_id);
		$this->db->where('active','1');
		
		if ($plan_id != false) {
			// plan ID can be -1 for No plans
			// 				   0 for All plans
			//              or X referring to a specific plan ID X
			
			// must match this specific plan and all plans
			$this->db->where('(`plan_id` = \'' . $plan_id . '\' or `plan_id` = \'0\' or `plan_id` = \'\')',NULL,FALSE);
		}
		else {
			// must match no plans or an empty plan_id
			$this->db->where('(`plan_id` = \'-1\' or `plan_id` = \'\')',NULL,FALSE);
		}
		
		$query = $this->db->get('client_emails');
		
		if($query->num_rows() > 0) {
			$emails = array();
			foreach ($query->result_array() as $row) {
				$emails[] = $row;
			}
			return $emails;
		} else {
			return FALSE;
		}
	}
}
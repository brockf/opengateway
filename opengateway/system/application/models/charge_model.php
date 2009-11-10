<?php

class Charge_model extends Model
{
	function Charge_model()
	{
		parent::Model();
	}
	
	function ProcessCharge($client_id, $params)
	{
		if(!isset($params['gateway_id'])) {
			die($this->response->Error(3001));
		}
		
		$CI =& get_instance();
		
		// Get the gateway info to load the proper library
		$gateway_id = $params['gateway_id'];
		$CI->load->model('gateway_model');
		$gateway = $CI->gateway_model->GetGatewayDetails($client_id, $gateway_id);
		
		// Load the proper library
		$gateway_name = $gateway->name;
		$this->load->library('payment/'.$gateway_name);
		return $this->$gateway_name->Charge($client_id, $gateway, $params);
		
	}
}
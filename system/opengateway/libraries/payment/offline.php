<?php

class offline
{
	var $settings;
	
	function offline() {
		$this->settings = $this->Settings();
	}

	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'Offline, Cheque, &amp; Money Order';
		$settings['class_name'] = 'offline';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = TRUE;
		$settings['description'] = 'Use OpenGateway to record offline payments with this gateway.  One-time charges are simply recorded in the system.  Subscription payments are assumed paid until the subscription is cancelled or expires.';
		$settings['is_preferred'] = 0;
		$settings['setup_fee'] = 'n/a';
		$settings['monthly_fee'] = 'n/a';
		$settings['transaction_fee'] = 'n/a';
		$settings['purchase_link'] = '';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 1;
		$settings['requires_customer_information'] = 0;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled'
										);
										
		$settings['field_details'] = array(
										'enabled' => array(
														'text' => 'Enable this gateway?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'0' => 'Disabled')
														)
											);
		
		return $settings;
	}
	
	function TestConnection ($client_id, $gateway) 
	{
		return TRUE;
	}
	
	function Charge ($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{	
		$CI =& get_instance();
		
		$response_array = array('charge_id' => $order_id);
		$response = $CI->response->TransactionResponse(1, $response_array);
		
		return $response;
	}
	
	function Recur ($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{		
		$CI =& get_instance();
		// if a payment is to be made today, process it.
		if ($charge_today === TRUE) {
			// Create an order for today's payment
			$CI->load->model('charge_model');
			$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);
			
			$CI->charge_model->SetStatus($order_id, 1);
			$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
			$response = $CI->response->TransactionResponse(100, $response_array);
		}
		else {
			$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	function Refund ($client_id, $gateway, $charge, $authorization)
	{	
		return TRUE;
	}
	
	function CancelRecurring($client_id, $subscription)
	{	
		return TRUE;
	}
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		$response = array();
		$response['success'] = TRUE;

		return $response;
	}
	
	function UpdateRecurring()
	{
		return TRUE;
	}
}
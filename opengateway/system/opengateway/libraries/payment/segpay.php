<?php

class segpay
{
	var $settings;
	
	function segpay() {
		$this->settings = $this->Settings();
	}

	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'SegPay';
		$settings['class_name'] = 'segpay';
		$settings['external'] = TRUE;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'Segpay launched in June of 2005 as an EU Internet Payment Service Provider (IPSP) alternative for webmasters.';
		$settings['is_preferred'] = 0;
		$settings['setup_fee'] = 'n/a';
		$settings['monthly_fee'] = 'n/a';
		$settings['transaction_fee'] = 'n/a';
		$settings['purchase_link'] = 'http://www.segpay.com';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 0;
		$settings['requires_customer_information'] = 0;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array('enabled',
											 'mode'
											);
		
		$settings['field_details'] = array(
										'enabled' => array(
														'text' => 'Enable this gateway?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'0' => 'Disabled')
														),
										'mode' => array(
														'text' => 'Mode',
														'type' => 'select',
														'options' => array(
																		'live' => 'Live Mode'
																		)
														)
											);
											
		
		// create settings dynamically for packages
		$CI =& get_instance();
		// get plans
		$CI->load->model('plan_model');
		$plans = $CI->plan_model->GetPlans($CI->user->Get('client_id'));
		
		foreach ($plans as $plan) {
			$settings['required_fields'][] = 'plan_' . $plan['id'] . '_product';
			$settings['required_fields'][] = 'plan_' . $plan['id'] . '_package';
			$settings['field_details']['plan_' . $plan['id'] . '_product'] = array(
													'text' => 'SegPay Product # for Plan #' . $plan['id'],
													'type' => 'text'
												);
												
			$settings['field_details']['plan_' . $plan['id'] . '_package'] = array(
													'text' => 'SegPay Package # for Plan #' . $plan['id'],
													'type' => 'text'
												);
		}
		
		return $settings;
	}
	
	function TestConnection($client_id, $gateway)
	{
		return TRUE;
	}
	
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card, $return_url, $cancel_url)
	{
		return FALSE;	
	}
	
	function Recur($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences, $return_url, $cancel_url)
	{		
		$CI =& get_instance();
		
		$CI =& get_instance();
		$CI->load->model('charge_data_model');
		$CI->load->helper('url');
		$CI->load->model('client_model');
				
		$client = $CI->client_model->GetClient($client_id,$client_id);
		
		// save the return URL
		$CI->charge_data_model->Save('r' . $subscription_id, 'return_url', $return_url);
		
		// get the plan ID
		$subscription = $CI->recurring_model->GetRecurring($client_id, $subscription_id);
		$plan_id = $subscription['plan']['id'];
		
		// do we have a package and product ID for this?
		if (!isset($gateway['plan_' . $plan_id . '_product'])) {
			$response_array = array('reason' => 'You must specify the SegPay Product ID in the settings.');
			$response = $CI->response->TransactionResponse(2, $response_array);
			return $response;
		}
		
		if (!isset($gateway['plan_' . $plan_id . '_package'])) {
			$response_array = array('reason' => 'You must specify the SegPay Package ID in the settings.');
			$response = $CI->response->TransactionResponse(2, $response_array);
			return $response;
		}
		
		$vars = array(
              'x-eticketid'   => $gateway['plan_' . $plan_id . '_product'] . ':' . $gateway['plan_' . $plan_id . '_package'],
              'x-auth-link'   => site_url('callback/paypal/confirm_recur/' . $subscription_id),
              'x-auth-text'   => "Click here to return to " . $client['company'],
              'payment_id'    => $subscription['id']
        );
        
        $url = $this->GetAPIURL($gateway) . '?';
        
        foreach ($vars as $name => $value) {
        	$url .= urlencode($name) . '=' . urlencode($value) . '&';
        }
        
        $url = rtrim($url, '&');
		
		$response_array = array(
						'not_completed' => TRUE, // don't mark charge as complete
						'redirect' => $url, // redirect the user to this address
						'subscription_id' => $subscription_id
					);
		$response = $CI->response->TransactionResponse(100, $response_array);
		
		return $response;
	}
	
	function CancelRecurring($client_id, $subscription, $gateway)
	{
		return TRUE;
	}
	
	function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params)
	{
		return FALSE;
	}
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $params);
	}
	
	function ChargeRecurring($client_id, $gateway, $params)
	{
		return TRUE;
	}
	
	function Callback_confirm ($client_id, $gateway, $charge, $params) {
		return FALSE;
	}
	
	function Callback_confirm_recur ($client_id, $gateway, $subscription, $params) {
		$CI =& get_instance();
		
		$CI->load->model('charge_data_model');
		$data = $CI->charge_data_model->Get('r' . $subscription['id']);
		
		$url = $this->GetAPIUrl($gateway);
	
		$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $subscription['amount'], array(), $subscription['id'], $customer_id);
				
		$CI->load->model('order_authorization_model');
		$CI->order_authorization_model->SaveAuthorization($order_id, 'n/a');			
		$CI->charge_model->SetStatus($order_id, 1);
		
		$CI->recurring_model->SetActive($client_id, $subscription['id']);
				
		// trip it - were golden!
		TriggerTrip('new_recurring', $client_id, $order_id, $subscription['id']);
		
		TriggerTrip('recurring_charge', $client_id, $order_id, $subscription['id']);
		
		// redirect back to user's site
		header('Location: ' . $data['return_url']);
		die();
	}
	
	private function GetAPIURL ($gateway) {
		return 'https://secure2.segpay.com/billing/poset.cgi';
	}
}
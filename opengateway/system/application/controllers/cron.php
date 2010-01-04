<?php

if($_SERVER['SCRIPT_FILENAME'] != 'SubscriptionMaintenance.php') { 
	
	header('location: /');
	exit; 
}

class Cron extends Controller {

	function Cron()
	{
		parent::Controller();	
	}
	
	function SubscriptionMaintenance()
	{
		// get all the subscriptions with a next_charge date of today\
		$today = date('Y-m-d');
		$this->load->model('subscription_model');
		$subscriptions = $this->subscription_model->GetAllSubscriptionsByDate('next_charge', $today);
		
		$charge_success = array();
		$charge_failure = array();
		if($subscriptions) {
			$this->load->model('gateway_model');
		
			foreach($subscriptions as $subscription) {
				// Try and make the charge
				$response = $this->gateway_model->ChargeRecurring($subscription['client_id'], $subscription);
				if($response) {
					$charge_success[] = $subscription['subscription_id'];
				} else {
					$charge_failure[] = $subscription['subscription_id'];
				}
			}
		}
		
		// Cancel subscription if the end date is today
		$subscriptions = $this->subscription_model->GetAllSubscriptionsByDate('end_date', $today);
		if($subscriptions) {
			$this->load->model('gateway_model');
		
			foreach($subscriptions as $subscription) {
				// Try and make the charge
				$response = $this->subscription_model->CancelRecurring($subscription['client_id'], $subscription);
				if($response) {
					$cancelled[] = $subscription['subscription_id'];
				}
			}
		}
		
		
		// Check for emails to send
		// Get all the recurring charge emails to send in one week
		$sent_emails['recurring_autorecur_in_week'] = array();
		$next_week = mktime(0,0,0, date('m'), date('d') + 7, date('Y'));
		$charges = $this->subscription_model->GetChargesByDate($next_week);
		if($charges) {
			foreach($charges as $charge) {
				if(TriggerTrip('recurring_autorecur_in_week', $charge['client_id'], false, $charge['subscription_id'])) {
					$sent_emails['recurring_autorecur_in_week'][] = $charge['subscription_id'];
				}
			}
			
		}
		
		// Get all the recurring charge emails to send in one month
		$sent_emails['recurring_autorecur_in_month'] = array();
		$next_month = mktime(0,0,0, date('m') + 1, date('d'), date('Y'));
		$charges = $this->subscription_model->GetChargesByDate($next_month);
		if($charges) {
			$this->load->library('email');
			foreach($charges as $charge) {
				if(TriggerTrip('recurring_autorecur_in_month', $charge['client_id'])) {
					$sent_emails['recurring_autorecur_in_month'][] = $charge['subscription_id'];
				}
			}
			
		}

		// Get all the recurring expiration emails to send in one week
		$sent_emails['recurring_expiring_in_week'] = array();
		$charges = $this->subscription_model->GetChargesByExpiryDate($next_week);
		if($charges) {
			$this->load->library('email');
			foreach($charges as $charge) {
				if(TriggerTrip('recurring_expiring_in_week', $charge['client_id'])) {
					$sent_emails['recurring_expiring_in_week'][] = $charge['subscription_id'];
				}
			}
			
		}
		
		// Get all the recurring expiration emails to send in one month
		$sent_emails['recurring_expiring_in_month'] = array();
		$charges = $this->subscription_model->GetChargesByExpiryDate($next_month);
		if($charges) {
			$this->load->library('email');
			foreach($charges as $charge) {
				if(TriggerTrip('recurring_expiring_in_month', $charge['client_id'])) {
					$sent_emails['recurring_expiring_in_month'][] = $charge['subscription_id'];
				}
			}
			
		}
		
		$charge_success = count($charge_success);
		$charge_failure = count($charge_failure);
		$cancelled = count($cancelled);
		$autorecur_week = count($sent_emails['recurring_autorecur_in_week']);
		$autorecur_month = count($sent_emails['recurring_autorecur_in_month']);
		$expire_week = count($sent_emails['recurring_expiring_in_week']);
		$expire_month = count($sent_emails['recurring_expiring_in_month']);
		
		$response = $charge_success." Successfull Charges. ";
		$response .= $charge_failure." Failed Charges. ";
		$response .= $cancelled." Expired Subscriptions";
		$response .= $autorecur_week." Weekly Charge Reminders Sent. ";
		$response .= $autorecur_month." Monthly Charge Reminders Sent. ";
		$response .= $expire_week." Weekly Expiration Reminders Sent. ";
		$response .= $expire_month." Monthly Expiration Reminders Sent. ";
		
		echo $response;
	}
}



/* End of file gateway.php */
/* Location: ./system/application/controllers/gateway.php */
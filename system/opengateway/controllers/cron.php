<?php

class Cron extends Controller {

	private $debug = false;

	function __construct()
	{
		parent::__construct();

		// give lots of time for processing
		set_time_limit(0);
		
		// if wget times out, or the user stops requesting, don't end the cron processing
		// http://stackoverflow.com/questions/2291524/does-wget-timeout
		ignore_user_abort(TRUE);
	}

	//--------------------------------------------------------------------

	function RunAll($key)
	{
		if ($this->config->item('cron_key') != $key) {
			echo 'Invalid key.';
			return FALSE;
		}

		$this->SendNotifications($key);
		$this->SubscriptionMaintenance($key);
		die();
	}

	//--------------------------------------------------------------------

	function SendNotifications ($key) {
		if ($this->config->item('cron_key') != $key) {
			echo 'Invalid key.';
			if ($this->debug)
			{
				$this->log_it('Invalid Key for SendNotifications Cronjob: '. $key);
			}
			return FALSE;
		}

		if ($this->debug)
		{
			$this->log_it('Starting SendNotifications Cron');
		}

		$this->load->library('notifications');

		$notified = $this->notifications->ProcessQueue();

		echo $notified . ' notifications sent.';

		$this->save_cron_date($key, 'cron_last_run_notifications');

		if ($this->debug)
		{
			$this->log_it('Finished SendNotifications Cron');
		}

		return true;
	}

	//--------------------------------------------------------------------

	function SubscriptionMaintenance($key)
	{
		if ($this->config->item('cron_key') != $key) {
			echo 'Invalid key.';

			if ($this->debug)
			{
				$this->log_it('Invalid Key for SubscriptionMaintenance cronjob: '. $key);
			}

			return FALSE;
		}

		if ($this->debug)
		{
			$this->log_it('Starting SubscriptionMaintenance Cron');
		}

		$this->load->model('recurring_model');
		$this->load->model('gateway_model');
		$this->load->library('email');

		// Run PayPal fixes
		if ($this->run_paypal_fix())
		{
			$this->load->library('paypal_fix');
			$this->paypal_fix->run_fix();

			$this->db->update('version', array( 'paypal_fix_ran' => date('Y-m-d H:i:s') ) );
		}

		// Expire subscription if the end date is today or before
		$cancelled = array();
		$subscriptions = $this->recurring_model->GetAllSubscriptionsForExpiring();
		if($subscriptions) {
			foreach($subscriptions as $subscription) {
				// cancel the subscription
				$response = $this->recurring_model->CancelRecurring($subscription['client_id'], $subscription['subscription_id'], TRUE);
				if ($response) {
					$trip_trigger = TRUE;
					if (!empty($subscription['renewed'])) {
						// let's verify this subscription is active
						$renewing_sub = $this->recurring_model->GetSubscriptionDetails($subscription['client_id'], $subscription['renewed']);

						if ($renewing_sub['active'] == '1') {
							$trip_trigger = FALSE;
						}
					}

					if ($trip_trigger == TRUE) {
						// not being renewed, send expiration notice
						TriggerTrip('recurring_expire', $subscription['client_id'], FALSE, $subscription['subscription_id']);
					}

					$cancelled[] = $subscription['subscription_id'];
				}
			}
		}

		// get all the subscriptions with a next_charge date of today for the next charge
		$today = date('Y-m-d');
		$subscriptions = $this->recurring_model->GetAllSubscriptionsForCharging($today);

		$charge_success = array();
		$charge_failure = array();
		if($subscriptions) {
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

		// Check for emails to send
		// Get all the recurring charge emails to send in one week
		$sent_emails['recurring_autorecur_in_week'] = array();
		$next_week = mktime(0,0,0, date('m'), date('d') + 7, date('Y'));
		$charges = $this->recurring_model->GetChargesByDate($next_week);
		if($charges) {
			foreach($charges as $charge) {
				if (TriggerTrip('recurring_autorecur_in_week', $charge['client_id'], false, $charge['subscription_id'])) {
					$sent_emails['recurring_autorecur_in_week'][] = $charge['subscription_id'];
				}
			}
		}

		// Get all the recurring charge emails to send in one month
		$sent_emails['recurring_autorecur_in_month'] = array();
		$next_month = mktime(0,0,0, date('m') + 1, date('d'), date('Y'));
		$charges = $this->recurring_model->GetChargesByDate($next_month);
		if($charges) {
			foreach($charges as $charge) {
				if (TriggerTrip('recurring_autorecur_in_month', $charge['client_id'], false, $charge['subscription_id'])) {
					$sent_emails['recurring_autorecur_in_month'][] = $charge['subscription_id'];
				}
			}
		}

		// Get all the recurring expiration emails to send in one week
		$sent_emails['recurring_expiring_in_week'] = array();
		$charges = $this->recurring_model->GetChargesByExpiryDate($next_week);
		if($charges) {
			foreach($charges as $charge) {
				$trip_trigger = TRUE;
				if (!empty($charge['renewed'])) {
					// let's verify this subscription is active
					$renewing_sub = $this->recurring_model->GetSubscriptionDetails($charge['client_id'], $charge['renewed']);

					if ($renewing_sub['active'] == '1') {
						$trip_trigger = FALSE;
					}
				}

				if ($trip_trigger == TRUE) {
					if (TriggerTrip('recurring_expiring_in_week', $charge['client_id'], false, $charge['subscription_id'])) {
						$sent_emails['recurring_expiring_in_week'][] = $charge['subscription_id'];
					}
				}
			}
		}

		// Get all the recurring expiration emails to send in one month
		$sent_emails['recurring_expiring_in_month'] = array();
		$charges = $this->recurring_model->GetChargesByExpiryDate($next_month);
		if($charges) {
			foreach($charges as $charge) {
				$trip_trigger = TRUE;
				if (!empty($charge['renewed'])) {
					// let's verify this subscription is active
					$renewing_sub = $this->recurring_model->GetSubscriptionDetails($charge['client_id'], $charge['renewed']);

					if ($renewing_sub['active'] == '1') {
						$trip_trigger = FALSE;
					}
				}

				if ($trip_trigger == TRUE) {
					if (TriggerTrip('recurring_expiring_in_month', $charge['client_id'], false, $charge['subscription_id'])) {
						$sent_emails['recurring_expiring_in_month'][] = $charge['subscription_id'];
					}
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

		$response = $charge_success." Successful Charges. \n";
		$response .= $charge_failure." Failed Charges. \n";
		$response .= $cancelled." Expired Subscriptions. \n";
		$response .= $autorecur_week." Weekly Charge Reminders Sent. \n";
		$response .= $autorecur_month." Monthly Charge Reminders Sent. \n";
		$response .= $expire_week." Weekly Expiration Reminders Sent. \n";
		$response .= $expire_month." Monthly Expiration Reminders Sent. \n";

		echo $response;

		$this->save_cron_date($key, 'cron_last_run_subs');

		if ($this->debug)
		{
			$this->log_it('Finished SubscriptionMaintenance Cron');
		}

		die();
	}

	//--------------------------------------------------------------------

	function save_cron_date($key, $field)
	{
		if ($this->config->item('cron_key') != $key) {
			echo 'Invalid key.';
			return FALSE;
		}

		if ($this->debug)
		{
			$this->log_it('Saving Cron Date. Key: '. $key .', Field: '. $field);
		}

		// If the cron_last_run field doesn't exist… create it.
		if (!$this->db->field_exists('cron_last_run_notifications', 'version'))
		{
			$this->load->dbforge();

			$col = array(
				'cron_last_run_notifications'	=> array(
					'type' 	=> 'DATETIME',
					'null'	=> false,
					'default'	=> '0000-00-00 00:00:00'
				),
				'cron_last_run_subs'	=> array(
					'type' 	=> 'DATETIME',
					'null'	=> false,
					'default'	=> '0000-00-00 00:00:00'
				)
			);

			$this->dbforge->add_column('version', $col);
		}

		$this->db->update('version', array($field => date('Y-m-d H:i:s')));
	}

	//--------------------------------------------------------------------

	private function run_paypal_fix()
	{
		if ($this->debug)
		{
			$this->log_it('Starting run_paypal_fix Cron');
		}

		// Check the date that paypal_fix was last run.
		if (!$this->db->field_exists('paypal_fix_ran', 'version'))
		{
			// field doesn't exist so add it in.
			$this->load->dbforge();

			$col = array(
				'paypal_fix_ran'	=> array(
					'type' 	=> 'DATETIME',
					'null'	=> false,
					'default'	=> '0000-00-00 00:00:00'
				)
			);

			$this->dbforge->add_column('version', $col);
		}

		$query = $this->db->select('paypal_fix_ran')->get('version');

		$result = $query->row()->paypal_fix_ran;
		$last_ran = date('Y-m-d H:i:s', strtotime($result));
		$today = date('Y-m-d') .' 05:05:00';

		if ($this->debug)
		{
			$this->log_it('Finished run_paypal_fix Cron');
		}

		if (strtotime($last_ran) >= strtotime($today))
		{
			return false;
		}

		return true;
	}

	//--------------------------------------------------------------------

	/*
		Method: log_it()

		Logs the transaction to a file. Helpful with debugging callback
		transactions, since we can't actually see what's going on.

		Parameters:
			$heading	- A string to be placed above the resutls
			$params		- Typically an array to print_r out so that we can inspect it.
	*/
	public function log_it($heading, $params=null)
	{
		$file = FCPATH .'writeable/og_cron_log.txt';

		$content .= "# $heading\n";
		$content .= date('Y-m-d H:i:s') ."\n\n";
		if (!empty($params))
			$content .= print_r($params, true);
		file_put_contents($file, $content, FILE_APPEND);
	}

	//--------------------------------------------------------------------
}


/* End of file cron.php */
/* Location: ./system/opengateway/controllers/cron.php */
<?php

function CPLoader () {
	$CI =& get_instance();
	
	// define active Control Panel
	define("_CONTROLPANEL","1");
	
	// don't load this if OpenGateway isn't installed
	if (!file_exists(APPPATH . 'config/database.php')) {
		return TRUE;
	}
	
	// redirect to SSL?
	if ($CI->config->item('ssl_active') == TRUE and ($_SERVER["SERVER_PORT"] != "443" and (isset($_SERVER['https']) and $_SERVER['HTTPS'] != 'on'))) {
		header('Location: ' . str_replace('http://','https://',$CI->config->item('base_url')));
		die();
	}	
	
	$CI->load->library('session');
	$CI->load->model('cp/user','user');
	$CI->load->helper('url');
	$CI->lang->load('control_panel');
	$CI->load->model('cp/notices','notices');
	$CI->load->helper('get_notices');
	$CI->load->model('cp/navigation','navigation');
	$CI->load->helper('dataset_link');
	
	// confirm login
	if (!$CI->user->LoggedIn() and $CI->router->fetch_method() != 'login' and $CI->router->fetch_method() != 'do_login')
	{
		redirect('/dashboard/login');
		die();
	}
	
	// Check CronJobs
	$query = $CI->db->get('version');
	if ($query->num_rows())
	{
		$result = $query->row();
		
		$check_time = strtotime("-1 day");
		// If the cronjobs have never been run, then go ahead and run them manually.
		if (!isset($result->cron_last_run_notifications) || 
			!isset($result->cron_last_run_subs) ||
			$result->cron_last_run_notifications == '0000-00-00 00:00:00' ||
			$result->cron_last_run_subs == '0000-00-00 00:00:00' )
		{
			$cronkey = $CI->config->item('cron_key');
			
			$url = site_url('cron/RunAll/'. $cronkey);
			
			// Run the cron jobs via curl for max server compatility.
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);   // Verify it belongs to the server.
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);   // Check common exists and matches the server host name
		    $post_response = curl_exec($ch);
		    
		    curl_close($ch);
		}

		// Otherwise, if it has been ran but not lately, throw a warning.
		else if (strtotime($result->cron_last_run_notifications) < $check_time || strtotime($result->cron_last_run_subs) < $check_time)
		{
			$CI->notices->SetError('Warning: Your cronjob is not running properly. <a href="'. site_url('settings/cronjob') .'">Click here for details</a>');
		}
	}
	
	// Build Navigation
	$CI->navigation->Add('dashboard','Dashboard');
	$CI->navigation->Add('transactions','Transactions');
	$CI->navigation->Add('transactions/create','New Charge','transactions');
	$CI->navigation->Add('transactions/all_recurring','Recurring Charges','transactions');
	
	$CI->navigation->Add('coupons', 'Coupons');
	$CI->navigation->Add('coupons/add', 'New Coupon', 'coupons');	
	
	
	$CI->navigation->Add('customers','Customers');
	$CI->navigation->Add('plans','Recurring Plans');
	$CI->navigation->Add('plans/new_plan','New Plan','plans');
	
	if ($CI->user->LoggedIn() and ($CI->user->Get('client_type_id') == 1 or $CI->user->Get('client_type_id') == 3)) {
		$CI->navigation->Add('clients','Clients');
		$CI->navigation->Add('clients/create','New Client','clients');
	}
	
	$CI->navigation->Add('#','Settings',false,true);
	$CI->navigation->Add('settings/emails','Emails','#');
	$CI->navigation->Add('settings/gateways','Gateways','#');
	$CI->navigation->Add('settings/cronjob','Cronjobs','#');
	$CI->navigation->Add('settings/api','API Access','#');
	
	
	// Set default page title
	$CI->navigation->PageTitle('Control Panel');
}

// branding functions
function branded_include ($file) {
	if (file_exists(BASEPATH . '../branding/custom/' . $file)) {
		return site_url('branding/custom/' . $file);
	}
	else {
		return site_url('branding/default/' . $file);
	}
}

function branded_view ($file) {
	if (file_exists(BASEPATH . '../branding/custom/views/' . $file . '.php')) {
		return '../../../branding/custom/views/' . $file;
	}
	else {
		return $file;
	}
}
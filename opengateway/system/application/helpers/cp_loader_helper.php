<?php

function CPLoader () {
	$CI =& get_instance();
	
	// define active Control Panel
	define("_CONTROLPANEL","1");
	
	// redirect to SSL?
	if ($CI->config->item('ssl_active') == TRUE and $_SERVER["REQUEST_PORT"] != "443") {
		header('Location: ' . base_url());
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
	
	// Build Navigation
	$CI->navigation->Add('dashboard','Dashboard');
	$CI->navigation->Add('transactions','Transactions');
	$CI->navigation->Add('transactions/create','New Charge','transactions');
	$CI->navigation->Add('transactions/all_recurring','Recurring Charges','transactions');
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
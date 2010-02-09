<?php

function CPLoader () {
	$CI =& get_instance();
	
	// define active Control Panel
	define("_CONTROLPANEL","1");
	
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
	$CI->navigation->Add('customers','Customers');
	$CI->navigation->Add('plans','Recurring Plans');
	$CI->navigation->Add('plans/new_plan','New Plan','plans');
	
	if ($CI->user->LoggedIn() and ($CI->user->Get('client_type_id') == 1 or $CI->user->Get('client_type_id') == 3)) {
		$CI->navigation->Add('clients','Clients');
		$CI->navigation->Add('clients/create','New Client','clients');
	}
	
	$CI->navigation->Add('settings','Settings');
	$CI->navigation->Add('settings/emails','Emails','settings');
	$CI->navigation->Add('settings/gateways','Gateways','settings');
	$CI->navigation->Add('settings/api','API Access','settings');
	
	// Set default page title
	$CI->navigation->PageTitle('Control Panel');
}
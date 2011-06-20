<?php

function local_time ($client_id, $time) {	
	$CI =& get_instance();
	
	$CI->load->model('client_model');
	$client = $CI->client_model->GetClientDetails($client_id);
	
	$timestamp = (!is_numeric($time)) ? strtotime($time) : $time;
	$timestamp = $timestamp - date("Z");
	
	$timezone = $client->gmt_offset;
	$daylight_saving = (date("I") == 1) ? TRUE : FALSE;
	
	// format
	$format = (defined("_CONTROLPANEL")) ? "M j, Y" : "c";
	if (defined("_CONTROLPANEL") and strstr($time, ' ')) {
		$format = 'M j, Y h:i a';
	}

	$formatted_date = date($format,gmt_to_local($timestamp, $timezone, $daylight_saving));
	
	// if the date is null, we won't return it
	// any date before May 29, 1988 is null
	$is_null = ($timestamp < 580881600) ? TRUE : FALSE;
	
	if ($is_null === TRUE and defined("_CONTROLPANEL")) {
		return 'N/A';
	}
	elseif ($is_null === TRUE) {
		return '0';
	}
	else {
		return $formatted_date;
	}
}

function server_time ($time, $format = "Y-m-d", $today_or_more = false) {
	$time = strtotime($time);
	
	$time = $time - date("Z");
	
	if ($today_or_more == true) {
		if ($time < strtotime(date('Y-m-d'))) {
			$time = strtotime(date('Y-m-d'));
		}
	}
	
	return date($format, $time);
}
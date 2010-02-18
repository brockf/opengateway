<?php

function local_time ($client_id, $time) {
	$CI =& get_instance();
	
	$CI->load->model('client_model');
	$client = $CI->client_model->GetClientDetails($client_id);
	
	$timestamp = strtotime($time);
	$timezone = $client->gmt_offset;
	$daylight_saving = (date("I") == 1) ? TRUE : FALSE;
	
	// format
	$format = (defined("_CONTROLPANEL")) ? "M j, Y" : "c";
	if (defined("_CONTROLPANEL") and strstr($time, ' ')) {
		$format = 'M j, Y h:i a';
	}
	
	if (empty($timestamp) and defined("_CONTROLPANEL")) {
		return 'N/A';
	}
	elseif (empty($timestamp)) {
		return '0';
	}

	return date($format,gmt_to_local($timestamp, $timezone, $daylight_saving));
}

function server_time ($time, $format = "Y-m-d", $today_or_more = false) {
	$time = strtotime($time);
	
	$time = $time + date("Z");
	
	if ($today_or_more == true) {
		if ($time < strtotime(date('Y-m-d'))) {
			$time = strtotime(date('Y-m-d'));
		}
	}
	
	return date($format, $time);
}
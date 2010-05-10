<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// Charge & Recur now come through the API like everything else
		
$sql[] = 'UPDATE `request_types` SET `model`=\'\' WHERE `model`=\'gateway_model\'';

foreach ($sql as $query) {
	$CI->db->query($query);
}
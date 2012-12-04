<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$result = $CI->db->where('name','stripe_gw')->get('external_apis');

if ($result->num_rows() == 0) {
	// Provide a default value to default_gateway_id on the clients table
	$sql[] = 'ALTER TABLE  `clients` CHANGE  `default_gateway_id`  `default_gateway_id` INT( 11 ) NOT NULL DEFAULT  0';
	
	foreach ($sql as $query) {
		$CI->db->query($query);
	}
}
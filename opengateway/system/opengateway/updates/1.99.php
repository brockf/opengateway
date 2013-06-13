<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// Provide a default value to default_gateway_id on the clients table
$sql[] = 'ALTER TABLE  `order_data` CHANGE  `order_data_value`  `order_data_value` TEXT NOT NULL';

foreach ($sql as $query) {
	$CI->db->query($query);
}
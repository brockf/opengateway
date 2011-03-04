<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// create 'recurring_fail' trigger
$sql[] = 'INSERT INTO `email_triggers` (`system_name`, `human_name`, `description`, `available_variables`, `active`) VALUES (\'recurring_fail\', \'Recurring Fail\', \'A subscription fails due to a problem charging the credit card on a repeat billing.\', \'a:18:{i:0;s:6:"amount";i:1;s:4:"date";i:2;s:12:"recurring_id";i:3;s:7:"plan_id";i:4;s:9:"plan_name";i:5;s:11:"customer_id";i:6;s:20:"customer_internal_id";i:7;s:19:"customer_first_name";i:8;s:18:"customer_last_name";i:9;s:16:"customer_company";i:10;s:18:"customer_address_1";i:11;s:18:"customer_address_2";i:12;s:13:"customer_city";i:13;s:14:"customer_state";i:14;s:20:"customer_postal_code";i:15;s:16:"customer_country";i:16;s:14:"customer_phone";i:17;s:14:"customer_email";}\', 1);';

foreach ($sql as $query) {
	$CI->db->query($query);
}
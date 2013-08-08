<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// Provide a default value to default_gateway_id on the clients table
$sql[] = 'ALTER TABLE  `orders` ADD INDEX `customer_id` (customer_id);';
$sql[] = 'ALTER TABLE  `orders` ADD INDEX `client_id` (client_id);';
$sql[] = 'ALTER TABLE  `orders` ADD INDEX `timestamp` (timestamp);';
$sql[] = 'ALTER TABLE  `subscriptions` ADD INDEX `plan_id` (plan_id);';
$sql[] = 'ALTER TABLE  `subscriptions` ADD INDEX `customer_id` (customer_id);';
$sql[] = 'ALTER TABLE  `subscriptions` ADD INDEX `coupon_id` (coupon_id);';

foreach ($sql as $query) {
	$CI->db->query($query);
}
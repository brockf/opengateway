<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add refund column to orders
$sql[] = 'ALTER TABLE `orders` ADD COLUMN `refunded` TINYINT(3) NOT NULL AFTER `timestamp`';
$sql[] = 'ALTER TABLE `orders` ADD COLUMN `refund_date` DATETIME AFTER `refunded`';

// add refund API method
$sql[] = 'INSERT INTO `request_types` VALUES (\'45\', \'Refund\', \'\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
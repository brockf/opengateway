<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$sql[] = 'ALTER TABLE `subscriptions` ADD COLUMN `coupon_id` INT(11) AFTER `updated`';
$sql[] = 'ALTER TABLE `orders` ADD COLUMN `coupon_id` INT(11) AFTER `amount`';
$sql[] = 'TRUNCATE TABLE `coupon_types`';

$sql[] = "INSERT INTO `coupon_types` VALUES(1, 'Charge - Price Reduction');";

$sql[] = "INSERT INTO `coupon_types` VALUES(2, 'Recur - Total Price Reduction');";

$sql[] = "INSERT INTO `coupon_types` VALUES(3, 'Recur - Recurring Price Reduction');";

$sql[] = "INSERT INTO `coupon_types` VALUES(4, 'Recur - Initial Charge Price Reduction');";

$sql[] = "INSERT INTO `coupon_types` VALUES(5, 'Recur - Free Trial');";

foreach ($sql as $query) {
	$CI->db->query($query);
}
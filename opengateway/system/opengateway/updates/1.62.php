<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$sql[] = 'CREATE TABLE `coupons` (
  `coupon_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(11) unsigned NOT NULL,
  `coupon_type_id` int(11) unsigned NOT NULL,
  `coupon_name` varchar(60) CHARACTER SET utf8 NOT NULL,
  `coupon_code` varchar(20) CHARACTER SET utf8 NOT NULL,
  `coupon_start_date` date NOT NULL,
  `coupon_end_date` date NOT NULL,
  `coupon_max_uses` int(11) unsigned NOT NULL,
  `coupon_customer_limit` tinyint(1) NOT NULL,
  `coupon_reduction_type` tinyint(1) NOT NULL,
  `coupon_reduction_amt` int(9) NOT NULL,
  `coupon_trial_length` int(4) NOT NULL,
  `coupon_deleted` tinyint(1) unsigned NOT NULL,
  `created_on` datetime NOT NULL,
  `modified_on` datetime DEFAULT NULL,
  PRIMARY KEY (`coupon_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;';

$sql[] = "CREATE TABLE `coupons_plans` (
  `coupon_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  INDEX (  `coupon_id` ,  `plan_id` )
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$sql[] = "CREATE TABLE `coupon_types` (
  `coupon_type_id` int(3) NOT NULL,
  `coupon_type_name` varchar(255) NOT NULL,
  KEY `coupon_type_id` (`coupon_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";


$sql[] = "INSERT INTO `coupon_types` VALUES(1, 'Total Price Reduction');";

$sql[] = "INSERT INTO `coupon_types` VALUES(2, 'Recurring Price Reduction');";

$sql[] = "INSERT INTO `coupon_types` VALUES(3, 'Initial Charge Price Reduction');";

$sql[] = "INSERT INTO `coupon_types` VALUES(4, 'Free Trial');";

foreach ($sql as $query) {
	$CI->db->query($query);
}
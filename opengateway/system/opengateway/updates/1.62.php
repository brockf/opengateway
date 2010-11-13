<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// update eWAY gateway info
$sql[] = <<<EOS
CREATE TABLE `coupons` (
  `coupon_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(11) unsigned NOT NULL,
  `coupon_type_id` int(11) unsigned NOT NULL,
  `coupon_name` varchar(60) CHARACTER SET utf8 NOT NULL,
  `coupon_code` varchar(20) CHARACTER SET utf8 NOT NULL,
  `coupon_start_date` date NOT NULL,
  `coupon_end_date` date NOT NULL,
  `coupon_max_uses` int(11) unsigned NOT NULL,
  `coupon_customer_limit` tinyint(1) unsigned NOT NULL,
  `coupon_reduction_type` tinyint(1) unsigned DEFAULT NULL COMMENT '0=%, 1=fixed amount',
  `coupon_reduction_amt` int(9) unsigned DEFAULT NULL,
  `coupon_trial_length` int(4) unsigned DEFAULT NULL COMMENT 'in days',
  `coupon_min_cart_amt` int(9) unsigned DEFAULT NULL COMMENT 'in cents',
  `coupon_deleted` tinyint(1) unsigned NOT NULL,
  `created_on` datetime NOT NULL,
  `modified_on` datetime DEFAULT NULL,
  PRIMARY KEY (`coupon_id`)
) ENGINE=MyISAM  DEFAULT;
EOS;
$sql[] = "CREATE TABLE `coupons_subscriptions` (
  `coupon_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  KEY `coupon_id` (`coupon_id`,`subscription_id`)
) ENGINE=MyISAM DEFAULT;";
$sql[] = "CREATE TABLE `coupon_types` (
  `coupon_type_id` int(3) NOT NULL,
  `coupon_type_name` varchar(255) NOT NULL,
  KEY `coupon_type_id` (`coupon_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$sql[] = "INSERT INTO `coupon_types` VALUES(1, 'Recurring Price Reduction');";
$sql[] = "INSERT INTO `coupon_types` VALUES(2, 'Initial Charge Price Reduction');";
$sql[] = "INSERT INTO `coupon_types` VALUES(3, 'Total Price Reduction');";
$sql[] = "INSERT INTO `coupon_types` VALUES(4, 'Free Trial');";

foreach ($sql as $query) {
	$CI->db->query($query);
}
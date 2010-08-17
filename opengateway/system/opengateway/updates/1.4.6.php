<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add PayPal Standard payment method
$sql[] = 'INSERT INTO `external_apis` (`external_api_id`, `name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES (6, \'paypal_standard\', \'PayPal Express Checkout\', \'https://api-3t.paypal.com/nvp\', \'https://api-3t.sandbox.paypal.com/nvp\', \'\', \'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout\', \'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout\', \'\');';

// create order_data table
$sql[] = 'CREATE TABLE `order_data` (
  `order_data_id` int(11) NOT NULL auto_increment,
  `order_id` varchar(250) NOT NULL,
  `order_data_key` varchar(25) NOT NULL,
  `order_data_value` varchar(250) NOT NULL,
  PRIMARY KEY  (`order_data_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

foreach ($sql as $query) {
	$CI->db->query($query);
}
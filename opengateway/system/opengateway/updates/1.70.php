<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// create twocheckout gateway
$sql[] = 'DELETE FROM `external_apis` WHERE `name`=\'twocheckout\'';
$sql[] = 'INSERT INTO `external_apis` (`name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES  (\'twocheckout\', \'2Checkout\', \'https://www.2checkout.com/checkout/purchase\', \'https://www.2checkout.com/checkout/purchase\', \'https://www.2checkout.com/checkout/purchase\', \'\', \'\', \'\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
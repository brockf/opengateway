<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add Offline payment gateway
$sql[] = 'INSERT INTO `external_apis` (`external_api_id`, `name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES (9, \'offline\', \'Offline, Cheque, &amp; Money Order\', \'\', \'\', \'\', \'\', \'\', \'\');';

// add Edgil payment gateway
$sql[] = 'INSERT INTO `external_apis` (`external_api_id`, `name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES (8, \'edgil\', \'Edgil (JAVA) Gateway\', \'\', \'\', \'\', \'\', \'\', \'\');';

// add FreshBooks payment gateway
$sql[] = 'INSERT INTO `external_apis` (`external_api_id`, `name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES (10, \'freshbooks\', \'FreshBooks\', \'\', \'\', \'\', \'\', \'\', \'\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
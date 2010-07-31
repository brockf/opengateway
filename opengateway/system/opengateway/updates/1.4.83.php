<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add ChangeRecurringPlan payment method
//$sql[] = 'INSERT INTO `external_apis` (`name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES (\'payleap\', \'PayLeap\', \'\', \'\', \'\', \'\', \'\', \'\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
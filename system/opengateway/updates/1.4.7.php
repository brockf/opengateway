<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add Wirecard payment method
$sql[] = 'INSERT INTO `external_apis` (`external_api_id`, `name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES (7, \'pacnet\', \'Pacnet\', \'https://raven.pacnetservices.com/realtime\', \'\', \'\', \'\', \'\', \'\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
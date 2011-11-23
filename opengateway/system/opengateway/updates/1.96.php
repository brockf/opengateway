<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$result = $CI->db->where('name','stripe_gw')->get('external_apis');

if ($result->num_rows() == 0) {
	// create gateway
	$sql[] = 'INSERT INTO `external_apis` (`name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES  (\'stripe_gw\', \'Stripe\', \'\', \'\', \'\', \'\', \'\', \'\');';
	
	foreach ($sql as $query) {
		$CI->db->query($query);
	}
}
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$result = $CI->db->where('name','stripe_gw')->get('external_apis');

if ($result->num_rows() == 0) {
	// create gateway
	$sql[] = 'INSERT INTO `external_apis` (`name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES  (\'gate2shop\', \'Gate2Shop\', \'https://secure.Gate2Shop.com/ppp/purchase.do\', \' https://ppp-test.Gate2Shop.com/ppp/purchase.do\', \' https://ppp-test.Gate2Shop.com/ppp/purchase.do\', \'\', \'\', \'\');';
	
	foreach ($sql as $query) {
		$CI->db->query($query);
	}
}
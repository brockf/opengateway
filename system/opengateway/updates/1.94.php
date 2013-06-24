<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$result = $CI->db->where('name','jetpayi5')->get('external_apis');

if ($result->num_rows() == 0) {
	// create jetpayi5 gateway
	$sql[] = 'INSERT INTO `external_apis` (`name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES  (\'jetpayi5\', \'JetPayi5\', \'\', \'\', \'\', \'\', \'\', \'\');';
	
	foreach ($sql as $query) {
		$CI->db->query($query);
	}
}
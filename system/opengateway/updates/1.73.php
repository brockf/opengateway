<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// create twocheckout gateway
$sql[] = 'INSERT INTO `states` (`name_long`, `name_short`) VALUES (\'District of Columbia\', \'DC\')';

foreach ($sql as $query) {
	$CI->db->query($query);
}
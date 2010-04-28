<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// Delete the gateway logs
		
$sql[] = 'DROP TABLE `authnet_log`';
$sql[] = 'DROP TABLE `exact_log`';

foreach ($sql as $query) {
	$CI->db->query($query);
}
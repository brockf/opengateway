<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// ease requirements of NewClient
$sql[] = 'TRUNCATE TABLE `request_log`';

foreach ($sql as $query) {
	$CI->db->query($query);
}
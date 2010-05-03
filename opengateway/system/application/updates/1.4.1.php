<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// ease requirements of NewClient
$sql[] = 'DELETE FROM `clients` WHERE `required_field_id` > 3 and `required_field_id` < 10';

foreach ($sql as $query) {
	$CI->db->query($query);
}
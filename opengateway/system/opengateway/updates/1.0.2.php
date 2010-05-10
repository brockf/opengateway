<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();
		
$sql[] = 'UPDATE `client_types` SET `description`=\'End User\' WHERE `client_type_id`=\'2\'';

$sql[] = 'UPDATE `client_types` SET `description`=\'Service Provider\' WHERE `client_type_id`=\'1\'';

foreach ($sql as $query) {
	$CI->db->query($query);
}
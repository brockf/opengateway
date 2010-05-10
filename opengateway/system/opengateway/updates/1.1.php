<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// Charge & Recur now come through the API like everything else
		
$sql[] = 'UPDATE `request_types` SET `model`=\'\' WHERE `model`=\'gateway_model\'';

// Insert required fields for Recur

$sql[] = 'INSERT INTO `required_fields` VALUES (\'15\', \'9\', \'recur\');';

$sql[] = 'INSERT INTO `required_fields` VALUES (\'16\', \'9\', \'credit_card\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
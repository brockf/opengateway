<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add `renewed` field to `subscriptions`
$sql[] = 'ALTER TABLE `subscriptions` ADD COLUMN `renewed` INT(11) AFTER `active`';

foreach ($sql as $query) {
	$CI->db->query($query);
}
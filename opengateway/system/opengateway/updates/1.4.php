<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$sql[] = 'ALTER TABLE `plans` MODIFY COLUMN `name` VARCHAR(200) NOT NULL';
$sql[] = 'ALTER TABLE `version` MODIFY COLUMN `db_version` VARCHAR(15) NOT NULL';

foreach ($sql as $query) {
	$CI->db->query($query);
}
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// ease requirements of NewClient
$sql[] = 'CREATE TABLE IF NOT EXISTS `client_log` (
`client_log_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`client_id` INT NOT NULL ,
`trigger_id` INT NOT NULL ,
`client_log_date` DATETIME NOT NULL ,
`variables` TEXT NOT NULL
) ENGINE = MYISAM CHARSET=utf8;';

foreach ($sql as $query) {
	$CI->db->query($query);
}
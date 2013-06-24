<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `version` (
		  `db_version` float NOT NULL ,
		  PRIMARY KEY  (`db_version`)
		) ENGINE=MyISAM CHARSET=utf8 ;';
		
$sql[] = 'INSERT INTO `version` VALUES (\'1.0\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
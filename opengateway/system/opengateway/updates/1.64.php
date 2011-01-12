<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// update eWAY gateway info
$sql[] = 'DELETE FROM `external_apis` WHERE `name`=\'payleap\'';
$sql[] = 'INSERT INTO `external_apis` VALUES(12, \'payleap\', \'PayLeap\', \'http://www.payleap.com/SmartPayments/transact.asmx\', \'http://test.payleap.com/SmartPayments/transact.asmx\', \'http://test.payleap.com/SmartPayments/transact.asmx\', \'https://www.payleap.com/admin/ws/recurring.asmx\', \'http://test.payleap.com/admin/ws/recurring.asmx\', \'http://test.payleap.com/admin/ws/recurring.asmx\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
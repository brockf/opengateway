<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// update eWAY gateway info
$sql[] = 'DELETE FROM `external_apis` WHERE `name`=\'eway\'';
$sql[] = 'INSERT INTO `external_apis` VALUES(11, \'eway\', \'eWay\', \'https://www.eway.com.au/gateway_cvn/xmlpayment.asp\', \'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp\', \'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp\', \'http://www.eway.com.au/gateway/rebill/manageRebill\', \'https://www.eway.com.au/gateway/rebill/test/managerebill_test.asmx\', \'https://www.eway.com.au/gateway/rebill/test/managerebill_test.asmx\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
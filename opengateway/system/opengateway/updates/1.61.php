<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// update eWAY gateway info
$sql[] = 'DELETE FROM `external_apis` WHERE `name`=\'eway\'';
$sql[] = 'INSERT INTO `external_apis` VALUES(11, \'eway\', \'eWay\', \'https://www.eway.com.au/gateway_cvn/xmlpayment.asp\', \'https://www.eway.com.au/gateway/ManagedPaymentService/test/managedcreditcardpayment.asmx\', \'https://www.eway.com.au/gateway/ManagedPaymentService/test/managedcreditcardpayment.asmx\', \'http://www.eway.com.au/gateway/managedpayment\', \'https://www.eway.com.au/gateway/ManagedPaymentService/test/managedCreditCardPayment.asmx\', \'https://www.eway.com.au/gateway/ManagedPaymentService/test/managedcreditcardpayment.asmx\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
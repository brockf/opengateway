<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add eWay payment gateway
$sql[] = 'INSERT INTO `external_apis` VALUES (\'11\', \'eway\', \'eWay\', \'https://www.eway.com.au/gateway_cvn/xmlpayment.asp\', \'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp\', \'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp\', \'https://www.eway.com.au/gateway/rebill/upload.aspx\', \'https://www.eway.com.au/gateway/rebill/test/Upload_test.aspx\', \'https://www.eway.com.au/gateway/rebill/test/Upload_test.aspx\');';

foreach ($sql as $query) {
	$CI->db->query($query);
}
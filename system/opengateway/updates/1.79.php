<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// fix coupon value issue
$sql[] = 'ALTER TABLE `coupons` CHANGE `coupon_reduction_amt` `coupon_reduction_amt` FLOAT';

foreach ($sql as $query) {
	$CI->db->query($query);
}
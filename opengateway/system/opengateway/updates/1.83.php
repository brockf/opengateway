<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();
$CI->load->helper('url');

// we set the version here so as not to start an infinite loop
$this->db->update('version', array('db_version' => '1.83'));

// trigger PayPal fix
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, site_url('paypal_fix'));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
$response = curl_exec($ch);

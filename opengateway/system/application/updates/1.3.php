<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// Delete the gateway logs
		
$sql[] = 'INSERT INTO `external_apis` (`external_api_id`, `name`, `display_name`, `prod_url`, `test_url`, `dev_url`, `arb_prod_url`, `arb_test_url`, `arb_dev_url`) VALUES (4, \'sagepay\', \'SagePay\', \'https://live.sagepay.com/gateway/service/vspdirect-register.vsp\', \'https://test.sagepay.com/gateway/service/vspdirect-register.vsp\', \'https://test.sagepay.com/Simulator/VSPDirectGateway.asp\', \'https://live.sagepay.com/gateway/service/repeat.vsp\', \'https://test.sagepay.com/gateway/service/repeat.vsp\', \'https://test.sagepay.com/Simulator/VSPServerGateway.asp?Service=VendorRepeatTx\');';
$sql[] = 'ALTER TABLE `order_authorizations` MODIFY COLUMN `authorization_code` VARCHAR(200) NOT NULL';
$sql[] = 'ALTER TABLE `order_authorizations` ADD COLUMN `security_key` VARCHAR(200) NOT NULL AFTER `authorization_code`';
$sql[] = 'ALTER TABLE `order_authorizations` MODIFY COLUMN `order_id` VARCHAR(200) NOT NULL';
$sql[] = 'ALTER TABLE `client_gateways` ADD COLUMN `alias` VARCHAR(200) NOT NULL AFTER `external_api_id`';

foreach ($sql as $query) {
	$CI->db->query($query);
}
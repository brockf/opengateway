<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();

$sql = array();

// add ChangeRecurringPlan payment method
$sql[] = 'INSERT INTO `request_types` (`request_type_id`, `name`, `model`) VALUES (\'47\', \'UpdateCreditCard\', \'\');';

// add `card_last_four` to `subscriptions` because we may be updated the CC now
$sql[] = 'ALTER TABLE `subscriptions` ADD COLUMN `card_last_four` VARCHAR(4) NOT NULL AFTER `amount`';

foreach ($sql as $query) {
	$CI->db->query($query);
}
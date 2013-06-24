<?php

/**
* PayPal Fix
*
* Query PayPal to update all next_charge_dates for PayPal subscriptions
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway
*/

class Paypal_fix extends Controller {
	private $gateways;

	function __construct ()
	{
		define('_CONTROLPANEL','1');

		parent::__construct();
	}

	function _remap ()
	{
		set_time_limit(0);

		$this->db->select('*');
		$this->db->from('subscriptions');
		$this->db->join('client_gateways','client_gateways.client_gateway_id = subscriptions.gateway_id','inner');
		$this->db->join('external_apis','external_apis.external_api_id = client_gateways.external_api_id','inner');
		$this->db->where('(`external_apis`.`name` = \'paypal\' or `external_apis`.`name` = \'paypal_standard\')',NULL,FALSE);
		$this->db->where('subscriptions.active','1');
		$this->db->where('subscriptions.last_charge <','2011-07-30');
		$result = $this->db->get();

		$updated = 0;

		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $sub) {
				$profile = $this->_get_profile($sub['gateway_id'], $sub['api_customer_reference']);


				if (isset($profile['NEXTBILLINGDATE']) and !empty($profile['NEXTBILLINGDATE'])) {
					$paypal_date = $profile['NEXTBILLINGDATE'];
					$local_date = $sub['next_charge'];
					$formatted_paypal_date = date('Y-m-d', strtotime($profile['NEXTBILLINGDATE']));

					// update local
					$this->db->update('subscriptions', array('next_charge' => $formatted_paypal_date), array('subscription_id' => $sub['subscription_id']));

					$updated++;
				}
			}
		}

		echo 'Updated ' . $updated . ' active subscriptions to proper billing dates.<br /><br />';

		// update credit card last fours
		$this->db->select('*');
		$this->db->from('subscriptions');
		$result = $this->db->get();

		$cc_updated = 0;

		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $sub) {
				if (empty($sub['card_last_four'])) {
					$this->db->select('card_last_four')
							 ->from('orders')
							 ->where('card_last_four !=','')
							 ->where('card_last_four !=','0')
							 ->where('subscription_id',$sub['subscription_id']);
					$order = $this->db->get();

					$card_last_four = $order->row()->card_last_four;

					$this->db->update('subscriptions', array('card_last_four' => $card_last_four), array('subscription_id' => $sub['subscription_id']));
				}
				else {
					$card_last_four = $sub['card_last_four'];
				}

				$this->db->update('orders', array('card_last_four' => $card_last_four), array('subscription_id' => $sub['subscription_id']));

				$cc_updated++;
			}
		}

		echo 'Updated ' . $cc_updated . ' card last four numbers.';
	}

	function _get_profile ($gateway_id, $profile_id)
	{
		if (isset($this->gateways[$gateway_id])) {
			$gateway = $this->gateways[$gateway_id];
		}
		else {
			$gateway = $this->_get_gateway_details($gateway_id);
			$this->gateways[$gateway_id] = $gateway;
		}

		$post_url = $this->_get_api_url($gateway);

		$post = array();
		$post['version'] = '60';
		$post['method'] = 'GetRecurringPaymentsProfileDetails';
		$post['user'] = $gateway['user'];
		$post['pwd'] = $gateway['pwd'];
		$post['signature'] = $gateway['signature'];
		$post['profileid'] = $profile_id;

		$post_response = $this->_process($post_url, $post);

		if ($post_response['ACK'] == 'Success') {
			return $post_response;
		} else {
			return FALSE;
		}
	}

	private function _get_gateway_details ($gateway_id) {
		$this->load->library('encrypt');

		$this->db->where('client_gateway_id',$gateway_id);
		$result = $this->db->get('client_gateway_params');

		$data = array();
		foreach ($result->result_array() as $item) {
			$data[$item['field']] = $this->encrypt->decode($item['value']);
		}

		$this->db->where('client_gateway_id', $gateway_id);
		$result = $this->db->get('client_gateways');

		$gateway = $result->row_array();

		$this->db->where('external_api_id', $gateway['external_api_id']);
		$result = $this->db->get('external_apis');

		$external_api = $result->row_array();

		foreach ($external_api as $item => $value) {
			$data[$item] = $value;
		}

		return $data;
	}

	private function _get_api_url ($gateway) {
		if ($gateway['mode'] == 'test') {
			return $gateway['test_url'];
		}
		else {
			return $gateway['prod_url'];
		}
	}

	private function _process ($url, $post_data)
	{
		$data = '';

		foreach ($post_data as $key => $value) {
			if (!empty($value)) {
				$data .= strtoupper($key) . '=' . urlencode(trim($value)) . '&';
			}
		}

		// remove the extra ampersand
		$data = substr($data, 0, strlen($data) - 1);

		// setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		// turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);

		// setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		// getting response from server
		$response = curl_exec($ch);

		// Throw an error if we can't continue. Will help in debugging.
		if (curl_error($ch)) {
			show_error(curl_error($ch));
		}

		$response = $this->_response_to_array($response);

		return $response;
	}

	private function _response_to_array($string)
	{
		$string = urldecode($string);
		$pairs = explode('&', $string);
		$values = array();

		foreach($pairs as $pair)
		{
			list($key, $value) = explode('=', $pair);
			$values[$key] = $value;
		}

		return $values;
	}
}
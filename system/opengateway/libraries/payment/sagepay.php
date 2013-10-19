<?php
// Remember to set the return URLs below - this manual
// Also remember on opengateway you need to change DB table order_data to set the 3rd and 4th columns to text from varchar
// use 3dsecuretest.sh to test it
class sagepay
{
	var $settings;

	private $debug = false;

	private $ci;

	//--------------------------------------------------------------------

	function sagepay() {
		$this->settings = $this->Settings();

		$this->ci =& get_instance();
	}

	//--------------------------------------------------------------------

	function Settings()
	{
		$settings = array();

		$settings['name'] = 'SagePay';
		$settings['class_name'] = 'sagepay';
		$settings['external'] = true;
		$settings['no_credit_card'] = FALSE;
		$settings['description'] = 'SagePay is the premier merchant account provider for the United Kingdom.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '&pound;0';
		$settings['monthly_fee'] = '&pound;20';
		$settings['transaction_fee'] = '10p';
		$settings['purchase_link'] = 'https://support.protx.com/apply/default.aspx?PartnerID=D16D4B72-87D5-4E97-A743-B45078E146CB';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 0;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled',
										'mode',
										'vendor',
										'currency',
										'_3dsecure',
										'accept_visa',
										'accept_mc',
										'accept_discover',
										'accept_dc',
										'accept_amex'
										);

		$settings['field_details'] = array(
										'enabled' => array(
														'text' => 'Enable this gateway?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'0' => 'Disabled')
														),
										'_3dsecure' => array(
														'text' => 'Use 3D Secure?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'2' => 'Disabled')
														),
										'mode' => array(
														'text' => 'Mode',
														'type' => 'select',
														'options' => array(
																		'live' => 'Live Mode',
																		'test' => 'Test Mode',
																		'simulator' => 'Simulator'
																		)
														),
										'vendor' => array(
														'text' => 'Vendor',
														'type' => 'text'
														),
										'currency' => array(
														'text' => 'Currency',
														'type' => 'select',
														'options' => array(
																		'GBP' => 'GBP - Pound Sterling',
																		'EUR' => 'EUR - Euro',
																		'USD' => 'USD - US Dollar',
																		'AUD' => 'AUD - Australian Dollar',
																		'CAD' => 'CAD - Canadian Dollar',
																		'CHF' => 'CHF - Swiss Franc',
																		'DKK' => 'DKK - Danish Krone',
																		'HKD' => 'HKD - Hong Kong Dollar',
																		'IDR' => 'IDR - Rupiah',
																		'JPY' => 'JPY - Yen',
																		'LUF' => 'LUF - Luxembourg Franc',
																		'NOK' => 'NOK - Norwegian Krone',
																		'NZD' => 'NZD - New Zealand Dollar',
																		'SEK' => 'SEK - Swedish Krona',
																		'SGD' => 'SGD - Singapore Dollar',
																		'TRL' => 'TRL - Turkish Lira'
																	)
														),
										'accept_visa' => array(
														'text' => 'Accept VISA?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_mc' => array(
														'text' => 'Accept MasterCard?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_discover' => array(
														'text' => 'Accept Discover?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_dc' => array(
														'text' => 'Accept Diner\'s Club?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_amex' => array(
														'text' => 'Accept American Express?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														)
											);

		return $settings;
	}

	//--------------------------------------------------------------------

	function TestConnection($client_id, $gateway)
	{
		// There's no way to test the connection at this point
		return TRUE;
	}

	//--------------------------------------------------------------------

	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card, $return_url, $cancel_url, $custom=array(), $txtype = 'PAYMENT')
	{
		$post_url = $this->GetAPIUrl($gateway);

		// get card type in proper format
		switch($credit_card['card_type']) {
			case 'visa';
				$card_type = 'VISA';
			break;
			case 'mc';
				$card_type = 'MC';
			break;
			case 'discover';
				$card_type = 'DC';
			break;
			case 'amex';
				$card_type = 'AMEX';
			break;
		}

		$post_values = array(
			"VPSProtocol" => "2.23",
			"TxType" => $txtype,
			"Vendor" => $gateway['vendor'],
			"VendorTxCode" => 'opengateway-' . $order_id,
			"Amount" => number_format($amount, 2),
			"Currency" => $gateway['currency'],
			"Description" => "API Payment at " . date('Y-m-d H:i:s') . " via " . $this->ci->config->item('server_name'),
			"CardHolder" => $credit_card['name'],
			"CardNumber" => $credit_card['card_num'],
			"ExpiryDate" => str_pad($credit_card['exp_month'], 2, "0", STR_PAD_LEFT) . substr($credit_card['exp_year'],-2,2),
			"CardType" => $card_type,
			"Apply3DSecure" => "2" // No 3DSecure checks, ever
		);

		/*
			3D Secure
		*/
		if ($gateway['_3dsecure'] == 1)
		{
			$post_values['Apply3DSecure'] = 0;
		}

		if(isset($credit_card['cvv'])) {
			$post_values['CV2'] = $credit_card['cvv'];
		}

		if (isset($customer['customer_id'])) {
			$post_values['BillingFirstNames'] = $customer['first_name'];
			$post_values['BillingSurname'] = $customer['last_name'];
			$post_values['BillingAddress1'] = $customer['address_1'];
			if (isset($customer['address_2']) and !empty($customer['address_2'])) {
				$post_values['BillingAddress2'] = ' - '.$customer['address_2'];
			}
			$post_values['BillingCity'] = $customer['city'];
			if (!empty($customer['country']) and ($customer['country'] == 'US')) {
				// only for North American customers
				$post_values['BillingState'] = $customer['state'];
			}
			$post_values['BillingPostCode'] = $customer['postal_code'];
			$post_values['BillingCountry'] = $customer['country'];
			if (!empty($customer['phone'])) {
				$post_values['BillingPhone'] = $customer['phone'];
			}

			if (!empty($customer['email'])) {
				$post_values['CustomerEMail'] = $customer['email'];
			}

			if (!empty($customer['ip_address'])) {
				$post_values['ClientIPAddress'] = $customer['ip_address'];
			}

			// duplicate for delivery
			$post_values['DeliveryFirstNames'] = $customer['first_name'];
			$post_values['DeliverySurname'] = $customer['last_name'];
			$post_values['DeliveryAddress1'] = $customer['address_1'];
			if (isset($customer['address_2']) and !empty($customer['address_2'])) {
				$post_values['DeliveryAddress2'] = ' - '.$customer['address_2'];
			}
			$post_values['DeliveryCity'] = $customer['city'];
			if (!empty($customer['country']) and ($customer['country'] == 'US')) {
				// only for North American customers
				$post_values['DeliveryState'] = $customer['state'];
			}
			$post_values['DeliveryPostCode'] = $customer['postal_code'];
			$post_values['DeliveryCountry'] = $customer['country'];
			if (!empty($customer['phone'])) {
				$post_values['DeliveryPhone'] = $customer['phone'];
			}
		}

		$response = $this->Process($order_id, $post_url, $post_values);

		if ($this->debug)
		{
			$this->log_it('SagePay Charge Request: ', $post_values);
			$this->log_it('SagePay Charge Response: ', $response);
		}

		/*
			If the response is a 3DAUTH, then we need to forward our
			customers on to the 3DAuth page to verify themselves.

			Otherwise, we can treat it as a normal charge.
		*/
		if (isset($response['Status']) && $response['Status'] == '3DAUTH')
		{
			// Save some info about this charge so that we can bring it back
			// up during the redirect
			$this->ci->load->helper('url');
			$this->ci->load->model('charge_data_model');

			if ($this->debug)
                	{
			$this->log_it('THE URL IS:  ', $response['ACSURL']);
			}

			$this->ci->charge_data_model->Save($order_id, 'MD', $response['MD']);
			$this->ci->charge_data_model->Save($order_id, 'PAReq', $response['PAReq']);
			$this->ci->charge_data_model->Save($order_id, 'ACSURL', $response['ACSURL']);

			$this->ci->charge_data_model->Save($order_id, 'return_url', $return_url);
			$this->ci->charge_data_model->Save($order_id, 'cancel_url', $cancel_url);

			//$this->ci->charge_data_model->Save($order_id, 'return_url', "https://www.edapt.org.uk/payment/complete");
			//$this->ci->charge_data_model->Save($order_id, 'cancel_url', "https://www.edapt.org.uk/payment/failure");

			$url = site_url('callback/sagepay/form_redirect/'. $order_id);

			$response_array = array(
				'not_completed' => TRUE, // don't mark charge as complete
				'redirect' 		=> $url, // redirect the user to this address
				'charge_id' 	=> $order_id,
				'type'			=> '3DAUTH'
			);
			$response = $this->ci->response->TransactionResponse(100, $response_array);
		}

		// A successful transaction
		else if ($response['success'] == TRUE)
		{
			$response_array = array('charge_id' => $order_id);
			$response = $this->ci->response->TransactionResponse(1, $response_array);
		}

		// There was an error
		else
		{
			$response_array = array('reason' => $response['reason']);
			$response = $this->ci->response->TransactionResponse(2, $response_array);
		}

		return $response;
	}

	//--------------------------------------------------------------------

	function Recur ($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE, $return_url = '', $cancel_url = '')
	{
		$CI =& get_instance();

		// if a payment is to be made today, process it.
		if ($charge_today === TRUE) {
			// Create an order for today's payment
			$CI->load->model('charge_model');
			$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);

			$response = $this->Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card, $return_url, $cancel_url);

			/*
				If this is a 3D Secure response, we need to redirect the
				user off to the 3DSecure validation service. At this point,
				the data has already been saved, so let's just save something
				so that we know this is a reucrring item.
			*/
			if (isset($response['type']) && $response['type'] == '3DAUTH')
			{
				$CI->load->model('charge_data_model');
				$CI->charge_data_model->Save('r'. $subscription_id, 'charge_id', $response['charge_id']);

				$response_array = array(
					'not_completed' => TRUE, // don't mark charge as complete
					'redirect' 		=> $url = site_url('callback/sagepay/form_redirect/'. $response['charge_id']), // redirect the user to this address
					'recurring_id' 	=> $subscription_id,
					'charge_id'		=> $response['charge_id'],
					'type'			=> '3DAUTH'
				);

				$response = $this->ci->response->TransactionResponse(100, $response_array);

				return $response;
			}

			// Otherwise a successful response
			else if ($response['response_code'] == '1') {
				$CI->charge_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			}

			// Not a successful charge
			else
			{
				// Make the subscription inactive
				$CI->recurring_model->MakeInactive($subscription_id);

				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			// we need to process an initial AUTHENTICATE transaction in order to send REPEATs later

			// generate a fake random Order ID - this isn't a true order
			// If 3DAuth is added, then we have to create a real order
			// but we'll do it with a $0 value.
			if ($gateway['_3dsecure'] == '1')
			{
				$CI->load->model('charge_model');
				$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);
			}
			else
			{
				$order_id = rand(100000,1000000);
			}
			$response = $this->Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card, $return_url, $cancel_url, array(), 'AUTHENTICATE');

			/*
				If this is a 3D Secure response, we need to redirect the
				user off to the 3DSecure validation service. At this point,
				the data has already been saved, so let's just save something
				so that we know this is a reucrring item.
			*/
			if (isset($response['type']) && $response['type'] == '3DAUTH')
			{
				$CI->load->model('charge_data_model');
				$CI->charge_data_model->Save('r'. $subscription_id, 'charge_id', $response['charge_id']);

				$response_array = array(
					'not_completed' => TRUE, // don't mark charge as complete
					'redirect' 		=> $url = site_url('callback/sagepay/form_redirect/'. $response['charge_id']), // redirect the user to this address
					'recurring_id' 	=> $subscription_id,
					'charge_id'		=> $response['charge_id'],
					'type'			=> '3DAUTH'
				);

				$response = $this->ci->response->TransactionResponse(100, $response_array);

				return $response;
			}

			if ($response['response_code'] == '1') {
				$response_array = array('recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->recurring_model->MakeInactive($subscription_id);

				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		}

		// let's save the transaction details for future REPEATs

		// for SagePay:
		//		api_customer_reference = VPSTxId
		//		api_payment_reference = VendorTxCode|VendorTxAuthNo
		//		api_auth_number = SecurityKey

		// these authorizations were saved during $this->Process()
		if ($response['response_code'] != '2') {
			$authorizations = $CI->order_authorization_model->GetAuthorization($order_id);

			$CI->recurring_model->SaveApiCustomerReference($subscription_id, $authorizations->tran_id);
			$CI->recurring_model->SaveApiPaymentReference($subscription_id, $authorizations->order_id . '|' . $authorizations->authorization_code);
			$CI->recurring_model->SaveApiAuthNumber($subscription_id, $authorizations->security_key);
		}

		return $response;
	}

	//--------------------------------------------------------------------

	function CancelRecurring($client_id, $subscription)
	{
		return TRUE;
	}

	//--------------------------------------------------------------------

	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		if ($this->debug)
		{
			$this->log_it('AutoRecurringCharge Params', $params);
		}

		return $this->ChargeRecurring($client_id, $gateway, $order_id, $params['api_customer_reference'], $params['api_payment_reference'], $params['api_auth_number'], $params['amount']);
	}

	//--------------------------------------------------------------------

	function ChargeRecurring($client_id, $gateway, $order_id, $VPSTxId, $VendorTxCodeVendorTxAuthNo, $SecurityKey, $amount)
	{
		$CI =& get_instance();

		list($VendorTxCode,$VendorTxAuthNo) = explode('|',$VendorTxCodeVendorTxAuthNo);

		$post_url = $this->GetAPIUrl($gateway, 'repeat');

		$post_values = array(
			"VPSProtocol" => "2.23",
			"TxType" => 'REPEAT',
			"Vendor" => $gateway['vendor'],
			"VendorTxCode" => 'opengateway-' . $order_id,
			"Amount" => number_format($amount, 2),
			"Currency" => $gateway['currency'],
			"Description" => "API Payment at " . date('Y-m-d H:i:s') . " via " . $CI->config->item('server_name'),
			"RelatedVPSTxId" => $VPSTxId,
			"RelatedVendorTxCode" => 'opengateway-' . $VendorTxCode,
			"RelatedTxAuthNo" => $VendorTxAuthNo,
			"RelatedSecurityKey" => $SecurityKey,
			"AccountType" => "C"
		);

		$response = $this->Process($order_id, $post_url, $post_values);

		if ($this->debug)
		{
			$this->log_it('ChargeRecurring Post', $post_values);
			$this->log_it('ChargeRecurring Response', $response);
		}

		if ($response['success'] == TRUE){
			return $response;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = $response['reason'];

			return $response;
		}
	}

	//--------------------------------------------------------------------

	function UpdateRecurring()
	{
		return TRUE;
	}

	//--------------------------------------------------------------------
	// !Callbacks
	//--------------------------------------------------------------------

	/*
		Method: callback_form_redirect()

		When using a 3DSecure connection, the user is directed here
		after making a charge. This echoes out a small form that should
		automatically redirect the user to the 3DSecure terminal.

		We also clean up data that we shouldn't be storing since we're
		done with it.
	*/
	public function Callback_form_redirect($client_id, $gateway, $charge, $params)
	{
		if ($this->debug)
		{
			$this->log_it('In Callback_form_redirect', null);
		}

		// PUll out the information for our form
		$this->ci->load->model('charge_data_model');

		$data = $this->ci->charge_data_model->Get($charge['id']);

		if ($this->debug)
                {
		$this->log_it('Charge Response now: ', $data);
		}

		$this->ci->load->helper('url');
		$return_url = site_url('callback/sagepay/confirm/'. $charge['id']);

		$pareqy=strlen($data['PAReq']);

		if ($this->debug)
                {
		$this->log_it('Length of pareq string: ', $pareqy);
		}

		while ($pareqy % 4 != 0) $pareqy++;

		if ($this->debug)
                {
		$this->log_it('base64 update to string: ', $pareqy);
		}

		$form = "<!doctype html><html lang='en'>
		<head>
			<title>3D Secure Verification</title>
			<script type='text/javascript'>
				function OnLoadEvent()
				{
					document.form.submit();
				}
			</script>
		</head>
		<body onload='OnLoadEvent()'>
			<form name='form' action='" . $data['ACSURL'] . "' method='post'>
				<input type='hidden' name='PaReq' value='" . str_pad($data['PAReq'], $pareqy, "=") . "' />
				<input type='hidden' name='TermUrl' value='{$return_url}' />
				<input type='hidden' name='MD' value='{$data['MD']}' />

				<NOSCRIPT>
					<center><p>Please click the button below to Authenticate your card.<br/>
					<input type='submit' value='Go' /></p></center>
				</NOSCRIPT>
			</form>
		</body>
		</html>";

		if ($this->debug)
		{
			$this->log_it('Form Redirect URL: ', $return_url);
			$this->log_it('Form Redirect Form', $form);
		}

		// Clean up the data that we shouldn't keep on hand.
		$this->ci->db->where('order_id', $charge['id'])->where('order_data_key', 'MD')->or_where('order_data_key', 'PAReq')->or_where('order_data_key', 'ACSURL')->delete('order_data');

		$this->ci->output->set_output($form);
	}

	//--------------------------------------------------------------------

	public function Callback_confirm($client_id, $gateway, $charge, $params)
	{
		$post = array(
			'MD'		=> $params['MD'],
			'PARes'		=> $params['PaRes']
		);

		$post_url = $this->GetAPIUrl($gateway, '3d');

		$response = $this->Process($charge['id'], $post_url, $post);

		$VPSTxId = isset($response['VPSTxId']) ? $response['VPSTxId'] : false;
		$TxAuthNo = isset($response['TxAuthNo']) ? $response['TxAuthNo'] : false;
		$SecurityKey = isset($response['SecurityKey']) ? $response['SecurityKey'] : false;

		$this->ci->load->model('charge_data_model');
		$data = $this->ci->charge_data_model->Get($charge['id']);

		if ($this->debug)
		{
			$this->log_it('Callback_confirm data', $data);
			$this->log_it('Callback_confirm charge', $charge);
			$this->log_it('Callback_confirm Params', $params);
			$this->log_it('Callback_Confirm Response', $response);
		}

		// A successful transaction
		if ($response['Status'] == 'OK' || ($response['Status'] == 'AUTHENTICATED' && $response['3DSecureStatus'] == 'OK'))
		{
			/*
				Recurring Charge
			*/
			if ((isset($charge['type']) && $charge['type'] == 'recurring_charge') || (isset($charge['type']) && $charge['type'] == 'recurring_repeat'))
			{
				// Do we have a recurring id passed in the $charge var?
				$subscription_id = $charge['recurring_id'];

				// We need to find our subscription id by backtracing based
				// on the charge id.
				$query = $this->ci->db->where('order_data_value', $charge['id'])->get('order_data');

				if (isset($subscription_id) || $query->num_rows() > 0)
				{
					if (empty($subscription_id))
						$subscription_id = str_replace('r', '', $query->row()->order_id);

					$this->ci->charge_model->SetStatus($charge['id'], 1);

					// Since we didn't get to save these for REPEATS earlier, we'll save them now
					// these authorizations were saved during $this->Process()
					$this->ci->load->model('recurring_model');
					$this->ci->recurring_model->SaveApiCustomerReference($subscription_id, $VPSTxId);
					$this->ci->recurring_model->SaveApiPaymentReference($subscription_id, $charge['id'] . '|' . $TxAuthNo);
					$this->ci->recurring_model->SaveApiAuthNumber($subscription_id, $SecurityKey);

					// Set the subscription to active
					$this->ci->load->model('recurring_model');
					$this->ci->recurring_model->SetActive($client_id, $subscription_id);

					$response_array = array('charge_id' => $charge['id'], 'recurring_id' => $subscription_id);
					$response = $this->ci->response->TransactionResponse(100, $response_array);
				}
			}

			/*
				Single Charge
			*/
			// save authorization (transaction id #)
			$this->ci->load->model('order_authorization_model');
			$this->ci->order_authorization_model->SaveAuthorization($charge['id'], $VPSTxId, $TxAuthNo);

			$this->ci->charge_model->SetStatus($charge['id'], 1);
			TriggerTrip('charge', $client_id, $charge['id']);

			// Save our SecurityKey so that we can do refunds...
			if (isset($SecurityKey) && !empty($SecurityKey))
			{
				$this->ci->charge_data_model->Save($charge['id'], 'token', $params['Token']);
			}

			$coupon_id = (isset($charge['coupon']) and isset($charge['coupon']['coupon_id'])) ? $charge['coupon']['coupon_id'] : null;

			if (!empty($coupon_id)) {
				// track coupon
				$this->ci->load->model('coupon_model');
				$this->ci->coupon_model->add_usage($coupon_id, FALSE, $charge['id'], $customer_id);
			}

			// redirect back to user's site
			header('Location: ' . $data['return_url']);
			die();
		}

		// There was an error
		else
		{
			$this->ci->load->model('charge_model');
			$this->ci->charge_model->SetStatus($charge['id'], 0);

			// Make sure to show an error here since we won't know anywhere else.
			$this->ci->load->library('session');
			$this->ci->load->model('cp/notices');
			$this->ci->notices->SetError('Charge failed: '. $response['StatusDetail']);

			// redirect back to user's site
			header('Location: ' . $data['cancel_url']);
			die();
		}


	}

	//--------------------------------------------------------------------

	//--------------------------------------------------------------------

	function Process($order_id, $post_url, $post_values)
	{
		$CI =& get_instance();
		$CI->load->model('charge_model');

		// build NVP post string
		$post_string = "";
		foreach($post_values as $key => $value) {
			$post_string .= "$key=" . urlencode( $value ) . "&";
		}
		$post_string = rtrim($post_string, "& ");

		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, TRUE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response

		if ($this->debug)
		{
			$this->log_it('Process CURL Error:', curl_error($request));
		}

		curl_close ($request); // close curl object

		$response_lines = explode("\r\n",$post_response);

		if (!is_array($response_lines)) {
			// we didn't receive back a series of newlines like we thought we would
			$response = array();
			$response['success'] = FALSE;

			return $response;
		}

		// put into array
		$response = array();
		foreach ($response_lines as $line) {
			if (!empty($line)) {
				$this->log_it('line is:', $line);
				list($name,$value) = explode('=',$line,2);
				$response[$name] = $value;
			}
		}

		// the OK message changes depending on the type
		if ($post_values['TxType'] == 'PAYMENT') {
			$ok_message = 'OK';
		}
		elseif ($post_values['TxType'] == 'REPEAT') {
			$ok_message = 'OK';
		}
		elseif ($post_values['TxType'] == 'AUTHENTICATE') {
			$ok_message = 'REGISTERED';
		}

		// did it process properly?
		if($response['Status'] == $ok_message) {
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['VPSTxId'], $response['TxAuthNo'], $response['SecurityKey']);
			$CI->charge_model->SetStatus($order_id, 1);

			$response['success'] = TRUE;
		} else {
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);

			$response['success'] = FALSE;
			$response['reason'] = $response['StatusDetail'];
		}

		return $response;
	}

	//--------------------------------------------------------------------

	function GetAPIUrl($gateway, $mode = FALSE) {
		if ($mode == FALSE) {
			switch($gateway['mode']) {
				case 'live':
					$post_url = $gateway['url_live'];
				break;
				case 'test':
					$post_url = $gateway['url_test'];
				break;
				case 'simulator':
					$post_url = $gateway['url_dev'];
				break;
			}
		}
		elseif ($mode == 'repeat') {
			switch($gateway['mode']) {
				case 'live':
					$post_url = $gateway['arb_url_live'];
				break;
				case 'test':
					$post_url = $gateway['arb_url_test'];
				break;
				case 'simulator':
					$post_url = $gateway['arb_url_dev'];
				break;
			}
		}
		else if ($mode == '3d')
		{
			switch ($gateway['mode'])
			{
				case 'live':
					$post_url = 'https://live.sagepay.com/gateway/service/direct3dcallback.vsp';
					break;
				case 'test':
					$post_url = 'https://test.sagepay.com/gateway/service/direct3dcallback.vsp';
					break;
				case 'simulator':
					$post_url = 'https://test.sagepay.com/Simulator/VSPDirectCallback.asp';
					break;
			}
		}

		return $post_url;
	}

	//--------------------------------------------------------------------

	/*
		Method: log_it()

		Logs the transaction to a file. Helpful with debugging callback
		transactions, since we can't actually see what's going on.

		Parameters:
			$heading	- A string to be placed above the resutls
			$params		- Typically an array to print_r out so that we can inspect it.
	*/
	public function log_it($heading, $params)
	{
		$content = '';

		$file = FCPATH .'writeable/gateway_log.txt';

		$content .= "# $heading\n";
		$content .= date('Y-m-d H:i:s') ."\n\n";
		$content .= print_r($params, true);
		file_put_contents($file, $content, FILE_APPEND);
	}

	//--------------------------------------------------------------------
}


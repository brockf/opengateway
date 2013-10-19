<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
	Stripe Gateway

	Developer Portal: https://stripe.com

	The Stripe gateway was created by ex-PayPal developers and aims to create
	the simplest payment processor around. This integration uses their
	PHP API and the card tokens in place of their recurring subscriptions, since
	their implementation only allows for months or years as the time frame
	and we need to be able to specify days. In many ways, this simplifies things
	for us, though.

	Note: Minimum payment is $0.50.

	All calls must be made over SSL. Any http calls will fail.
*/

class Stripe_gw {

	protected $settings;

	/*
		Can be used to display debugging information.
		To be used by the developer during the creation
		and testing process.
	*/
	private $debug = false;

	private $currency = 'USD';

	//--------------------------------------------------------------------

	public function __construct()
	{
		$this->settings = $this->Settings();

		// Load the Stripe lib.
		require_once(APPPATH .'libraries/stripe/Stripe.php');
	}

	//--------------------------------------------------------------------

	/*
		Method: Settings()

		Provides a single place to specify the default settings for the gateway.
	*/
	public function Settings()
	{
		$settings = array();

		$settings['name'] = 'Stripe';
		$settings['class_name'] = 'stripe_gw';
		$settings['external'] = FALSE;
		$settings['no_credit_card'] = TRUE;
		$settings['description'] = 'Stripe makes it easy to start accepting credit cards on the web today. Requires a SSL connection. US only.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$0';
		$settings['monthly_fee'] = '$0';
		$settings['transaction_fee'] = '2.9% + $0.30';
		$settings['purchase_link'] = 'https://stripe.com';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 1;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 1;
		$settings['required_fields'] = array(
										'enabled',
										'mode',
										'accept_visa',
										'accept_mc',
										'accept_discover',
										'accept_amex',
										'test_api_key',
										'live_api_key'
										);

		$settings['field_details'] = array(
										'enabled' => array(
														'text' => 'Enable this gateway?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'0' => 'Disabled')
														),
										'mode' => array(
														'text' => 'Mode',
														'type' => 'select',
														'options' => array(
																		'live' => 'Live Mode',
																		'test'	=> 'Testing'
																		)
														),
										'test_api_key' => array(
														'text' => 'Test API Key',
														'type' => 'text'
														),
										'live_api_key' => array(
														'text' => 'Live API Key',
														'type' => 'text'
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

	/*
		Method: TestConnection()

		Tests that the user provided gateway information is correct.
		To test, we attempt to create a new customer. If successful,
		the customer is deleted immediately.

		Parameters:
			$client_id	- the ID of the client in OG.
			$gateway	- An array of gateway information.

		Returns:
			True/False
	*/
	public function TestConnection($client_id, $gateway)
	{
		$key = $gateway[$gateway['mode'] .'_api_key'];

		Stripe::setApiKey($key);

		$data = array(
			'email' => 'test1@gmail.com'
		);

		try{
			$customer = Stripe_Customer::create($data);

			$customer->delete();

			return TRUE;
		}
		catch (Exception $e)
		{
			return FALSE;
		}

		return false;
	}

	//--------------------------------------------------------------------

	/*
		Method: Charge()

		Performs a one-time charge.

		Parameters:
			$client_id		- The ID of the OpenGateway client
			$order_id		- The internal OpenGateway order id.
			$gateway		- An array of gateway information
			$customer		- An array with customer information
			$amount			- The amount of the charge
			$credit_card	- An array of credit card information

		Returns:
			$response	- A TransactionResponse object.
	*/
	public function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card, $return_url=null, $cancel_url=null, $custom=array())
	{

		$CI =& get_instance();

		$key = $gateway[$gateway['mode'] .'_api_key'];
		Stripe::setApiKey($key);

		$amount = $amount * 100;	// We need it in cents

		$data = array(
			'amount'	=> $amount,
			'currency'	=> $this->currency,
			'card'		=> array(
				'number'			=> $credit_card['card_num'],
				'exp_month'			=> $credit_card['exp_month'],
				'exp_year'			=> $credit_card['exp_year'],
				'cvc'				=> $credit_card['cvv'],
				'name'				=> $credit_card['name'],
				'address_line1'		=> isset($customer['address1']) ? $customer['address1'] : null,
				'address_line2'		=> isset($customer['address2']) ? $customer['address2'] : null,
				'address_zip'		=> isset($customer['postal_code']) ? $customer['postal_code'] : null,
				'address_state'		=> isset($customer['state']) ? $customer['state'] : null,
				'address_country'	=> isset($customer['country']) ? $customer['country'] : null,
			),
			'description'	=> 'Charge for '. $customer['email']
		);

		try {
			// Successful transaction
			$response = Stripe_Charge::create($data);

			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response->id, $response->id);
			$CI->charge_model->SetStatus($order_id, 1);

			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		}
		catch (Exception $e)
		{
			// Failed Transaction
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);

			$response_array = array('reason' => $e->getMessage());
			$response = $CI->response->TransactionResponse(2, $response_array);
		}

		return $response;
	}

	//--------------------------------------------------------------------

	/*
		Method: Refund()

		Refunds the full amount of a charge.

		Parameters:
			$client_id	- An INT with the client's id
			$gateway	- An array of the gateway information
			$charge		- An array of charge information
			$authorization	-
	*/
	public function Refund($client_id, $gateway, $charge, $authorization)
	{
		$CI =& get_instance();

		$key = $gateway[$gateway['mode'] .'_api_key'];
		Stripe::setApiKey($key);

		try {
			$charge = Stripe_Charge::retrieve($authorization->tran_id);
			$charge->Refund();

			return TRUE;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
	}

	//--------------------------------------------------------------------

	/*
		Method: Recur()

		Called when an initial Recur charge comes through to create a subscription.

		Parameters:
			$client_id		- An in with internal client ID.
			$gateway		- An array of gateway information
			$customer		- An array of customer information
			$amount			- A float with the amount to charge
			$charge_today	- Boolean
			$start_date		- The day the subscription should start
			$end_date		- The day the subscription should end
			$interval		- The number of days between subscription charges
			$credit_card	- An array with the credit card information
			$subscription_id	- An int with the internal subscription id
			$total_occurences	- The total number of charges, or FALSE.
	*/
	public function Recur($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{
		$CI =& get_instance();

		// set customer ID
		$customer['customer_id'] = (isset($customer['customer_id'])) ? $customer['customer_id'] : FALSE;

		// Create the recurring seed - Done this way for token based API's.
		$response = $this->CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id);

		if ($response['success'] == true) {
			// Process today's payment
			if ($charge_today === TRUE) {
				// Create an order for today's payment
				$CI->load->model('charge_model');
				$order_id = $CI->charge_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);

				$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $response['customer_id'], $amount);

				if ($response['success'] === TRUE){
					$CI->charge_model->SetStatus($order_id, 1);
					$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
					$response = $CI->response->TransactionResponse(100, $response_array);
				} else {
					// Make the subscription inactive
					$CI->recurring_model->MakeInactive($subscription_id);
					$CI->charge_model->SetStatus($order_id, 0);

					$response_array = array('reason' => $response['ResponseSummary']['StatusDescription']);
					$response = $CI->response->TransactionResponse(2, $response_array);
				}
			} else {
				$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
			}
		}
		else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}

		return $response;
	}

	//--------------------------------------------------------------------

	/*
		Method: CreateProfile()

		Creates a new customer on at Stripe and attaches a credit card to their account.

		Parameters:
			$client_id		- An int with the internal client id.
			$gateway		- An array of gateway information.
			$customer		- An array of customer information.
			$credit_card	- An array of credit card information.
			$subscription_id	- An INT with the internal ID of the subscription.
			$amount			- A float with the amount of the charge.
			$order_id		- An INT with the internal id of the order.
	*/
	public function CreateProfile($client_id, $gateway, $customer, $credit_card, $subscription_id, $amount, $order_id)
	{
		$CI =& get_instance();

		$key = $gateway[$gateway['mode'] .'_api_key'];
		Stripe::setApiKey($key);

		$data = array(
			'email'	=> $customer['email'],
			'description'	=> isset($customer['first_name']) ? $customer['first_name'] .' '. $customer['last_name'] : '',
			'card'	=> array(
				'number'			=> $credit_card['card_num'],
				'exp_month'			=> $credit_card['exp_month'],
				'exp_year'			=> $credit_card['exp_year'],
				'cvc'				=> $credit_card['cvv'],
				'name'				=> $credit_card['name'],
				'address_line1'		=> isset($customer['address1']) ? $customer['address1'] : null,
				'address_line2'		=> isset($customer['address2']) ? $customer['address2'] : null,
				'address_zip'		=> isset($customer['postal_code']) ? $customer['postal_code'] : null,
				'address_state'		=> isset($customer['state']) ? $customer['state'] : null,
				'address_country'	=> isset($customer['country']) ? $customer['country'] : null,
			)
		);

		$response = array();

		try {
			// Successful Creation
			$customer = Stripe_Customer::create($data);

			$response['success'] = true;
			$response['customer_id'] = $customer->id;

			// Save the Auth information
			$CI->load->model('recurring_model');
			$CI->recurring_model->SaveApiCustomerReference($subscription_id, $customer->id);
		}
		catch (Exception $e)
		{
			// Failed creation
			$response['success'] = false;
			$response['reason'] = $e->getMessage();
		}

		return $response;
	}

	//--------------------------------------------------------------------

	/*
		Method: ChargeRecurring

		This method handles the actual charging of a recurring payment,
		both for the first-time time (from Recur) and remaining payments
		(from AutoRecurringCharge).

		Parameters:
			$client_id		- An INT with the internal client ID.
			$gateway		- An array of gateway information.
			$order_id		- An INT with the internal order_id.
			$customer_id	- An INT with the internal customer id.
			$amount			- A float with the amount of the charge.
			$occurrences	- The total number of payments.
	*/
	public function ChargeRecurring($client_id, $gateway, $order_id, $customer_id, $amount, $occurences=0)
	{
		$CI =& get_instance();

		$key = $gateway[$gateway['mode'] .'_api_key'];
		Stripe::setApiKey($key);

		$data = array(
			'amount'	=> $amount * 100,
			'currency'	=> $this->currency,
			'customer'	=> $customer_id
		);

		$response = array();

		try {
			$charge = Stripe_Charge::create($data);

			$response['success']			= TRUE;
			$response['transaction_num']	= $charge->id;
			$response['auth_code']			= $charge->id;

			// Save the Auth information
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response['transaction_num'], $response['auth_code']);
		} catch (Exception $e)
		{
			$response['success'] 	= FALSE;
			$response['reason']		= $e->getMessage();
		}

		return $response;
	}

	//--------------------------------------------------------------------

	/*
		Method: AutoRecurringCharge()

		Handles the normal recurring charge, after the first one.

		Parameters:
			$client_id	- An INT of the internal client ID.
			$order_id	- An INT with the internal order id.
			$gateway	- An array of the gateway information.
			$params
	*/
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $order_id, $params['api_customer_reference'], $params['amount']);
	}

	//--------------------------------------------------------------------

	/*
		Method: UpdateRecurring()

		Updates the customer information for a subscription.
		Not all gateways support this.

		Parameters:
			$client_id	- An INT with the internal client id.
			$gateway	- An array of the the gateway's information.
			$subscription	- An array of the subscription information.
			$customer		- An array with the customer's information.
			$params			- Extra information.
	*/
	public function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params)
	{
		/*
			If nothing needs to be done at the gateway,
			then simply return TRUE here. At this point
			the user information will be already be updated
			in OpenGateway.
		*/
		return TRUE;
	}

	//--------------------------------------------------------------------

	/*
		Method: CancelRecurring()

		Cancels a recurring subscription at the gateway. Many gateways
		don't support this ability.

		Parameters:
			$client_id		- An INT with the internal client id.
			$subscription	- An array of subscription information.
	*/
	public function CancelRecurring($client_id, $subscription)
	{
		return TRUE;
	}

	//--------------------------------------------------------------------

	//--------------------------------------------------------------------
	// !PROCESSORS
	//--------------------------------------------------------------------

	/*
		Method: Process()
	*/
	private function Process()
	{
		$CI =& get_instance();

		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_VERBOSE, 1);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);   // Verify it belongs to the server.
	    curl_setopt($request, CURLOPT_SSL_VERIFYHOST, FALSE);   // Check common exists and matches the server host name
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		$post_response = curl_exec($request); // execute curl post and store results in $post_response

		curl_close ($request); // close curl object

		$response = explode('|',$post_response);

		if(!isset($response[1])) {
			$response['success'] = FALSE;
			return $response;
		}

		if ($test) {
			if($response[0] == 1) {
				$response['success'] = TRUE;
			} else {
				$response['success'] = FALSE;
			}

			return $response;
		}
		// Get the response.  1 for the first part meant that it was successful.  Anything else and it failed
		if ($response[0] == 1) {
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response[6], $response[4]);
			$CI->charge_model->SetStatus($order_id, 1);

			$response['success'] = TRUE;
		} else {
			$CI->load->model('charge_model');
			$CI->charge_model->SetStatus($order_id, 0);

			$response['success'] = FALSE;
			$response['reason'] = $response[3];
		}

		return $response;
	}

	//--------------------------------------------------------------------


	//--------------------------------------------------------------------
	// !UTILITY METHODS
	//--------------------------------------------------------------------

	/*
		Method: GetAPIUrl()

		A convenience method to return the gateway's URL based on
		whether it's for a one-time charge or recurring and the gateway
		mode (live, test, or dev).

		Parameters
			$gateway	- An array of gateway information.
			$mode		- If false, will return the single-charge URL.
						  If 'rebill', returns the recurring charge URL.
	*/
	private function GetAPIUrl ($gateway, $mode = FALSE) {

		if ($mode == FALSE) {
			// Get the proper URL
			switch($gateway['mode'])
			{
				case 'live':
					$post_url = $gateway['url_live'];
				break;
				case 'test':
					$post_url = $gateway['url_test'];
				break;
				case 'dev':
					$post_url = $gateway['url_dev'];
				break;
			}
		}
		elseif ($mode == 'rebill') {
			// Get the proper URL
			switch($gateway['mode'])
			{
				case 'live':
					$post_url = $gateway['arb_url_live'];
				break;
				case 'test':
					$post_url = $gateway['arb_url_test'];
				break;
				case 'dev':
					$post_url = $gateway['arb_url_dev'];
				break;
			}
		}

		return $post_url;
	}

	//---------------------------------------------------------------
}
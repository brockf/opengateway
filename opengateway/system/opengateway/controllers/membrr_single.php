<?php

class Membrr_single extends Controller {
	// CONFIGURATION
	
	// set a random key which will be used to authorize requests
	private $secret_key = 'ty98stygriuhfvkr32y77yg5hut76b6yig892h4faofwew409trmujomq2';
	
	// this gateway_id is in the `client_gateways` table and corresponds to your active
	// Authorize.net gateway
	private $gateway_id = 20;
	
	// this is your OpenGateway client ID
	private $client_id = 1001;
	
	// END CONFIGURATIOn
	
	private $request; // array of request
	
	function __construct() {
		parent::__construct();
	
		// take request
    	$request = $_POST;
    	
    	if (empty($request)) {
    		die($this->_error('Empty request.'));
    	}
    	
    	// check key
    	if ($request['key'] != $this->secret_key) {
    		die($this->_error('Unauthorized API request.'));
    	}
    	
    	$this->request = (array)$request;
    }
    
    function _remap () {
		$method = $this->request['method'];
		
		if (!method_exists($this,$method)) {
			die($this->_error('Invalid API method.'));
		}
		
		return $this->$method($this->request);
    }
    
    private function _error ($string) {
    	return $this->_respond(array('error' => $string, 'success' => FALSE));
    }
    
    private function _respond ($response = array()) {
    	echo json_encode($response);
    }
    
    function charge ($request) {
    	// get customer record for this EE member_id
    	$result = $this->db->select('customer_id')
    					   ->where('internal_id', $request['member_id'])
    					   ->get('customers');
    					   
		if ($result->num_rows() != 1) {
			return $this->_error('No customer record.');
		}    					   
		
		$customer = $result->row_array();
    	
    	// look for active profile
    	$this->db->select('api_customer_reference');
    	$this->db->select('api_payment_reference');
		$this->db->join('client_gateways', 'subscriptions.gateway_id = client_gateways.client_gateway_id', 'inner');
		$this->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$this->db->where('api_customer_reference !=','');
		$this->db->where('subscriptions.gateway_id',$this->gateway_id);
		$this->db->where('subscriptions.active', 1);
		$this->db->where('subscriptions.customer_id',$customer['customer_id']);
		$result = $this->db->get('subscriptions');
		
		if ($result->num_rows() == 0) {
			return $this->_error('No active payment profile.');
		}
		
		$current_profile = $result->row_array();
		
		$profile_id = $current_profile['api_customer_reference'];
		$payment_profile_id = $current_profile['api_payment_reference'];
		
		// create order in database
		$this->load->model('charge_model');
		$order_id = $this->charge_model->CreateNewOrder($this->client_id, $this->gateway_id, $request['price'], array(), 0, $customer['customer_id']);
		
    	// POST charge request to Authorize.net
    	
    	$post_url = $this->_get_api_url();
    	$gateway = $this->_get_gateway_details();
		
		$content =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		"<createCustomerProfileTransactionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
		 "<merchantAuthentication>
	        <name>".$gateway['login_id']."</name>
	        <transactionKey>" . $gateway['transaction_key'] . "</transactionKey>
	    </merchantAuthentication>".
		"<transaction>".
		"<profileTransAuthCapture>".
		"<amount>" . $request['price'] . "</amount>". 
		"<customerProfileId>" . $profile_id . "</customerProfileId>".
		"<customerPaymentProfileId>" . $payment_profile_id . "</customerPaymentProfileId>".
		"<order>".
		"<invoiceNumber>".$order_id."</invoiceNumber>".
		"</order>".
		"</profileTransAuthCapture>".
		"</transaction>
		</createCustomerProfileTransactionRequest>";
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		$post_response = @simplexml_load_string($post_response);
		
		$success = FALSE;
		
		if (isset($post_response->messages->resultCode) and $post_response->messages->resultCode == 'Ok') {
			// Get the auth code
			$post_response = explode(',', $post_response->directResponse);
			$this->load->model('order_authorization_model');
			$this->order_authorization_model->SaveAuthorization($order_id, $post_response[6], $post_response[4]);
			
			$this->charge_model->SetStatus($order_id, 1);
			
			$success = TRUE;
		}
		
    	// respond

		if ($success == FALSE) {
			return $this->_error('Payment request rejected.');
		}
		else {
	    	return $this->_respond(array('success' => TRUE, 'order_id' => $order_id));	
	    }
    } 
    
    function _get_api_url () {
		$gateway = $this->_get_gateway_details();
		
		switch($gateway['mode']) {
			case 'live':
				$post_url = $gateway['arb_prod_url'];
			break;
			case 'test':
				$post_url = $gateway['arb_test_url'];
			break;
			case 'dev':
				$post_url = $gateway['arb_dev_url'];
			break;
		}
		
		return $post_url;
	}
	
	function _get_gateway_details () {
		$this->load->library('encrypt');
	
		$this->db->where('client_gateway_id',$this->gateway_id);
		$result = $this->db->get('client_gateway_params');
		
		$data = array();
		foreach ($result->result_array() as $item) {
			$data[$item['field']] = $this->encrypt->decode($item['value']);
		}
		
		$this->db->where('client_gateway_id', $this->gateway_id);
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
}

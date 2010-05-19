<?php
/**
* Callback Controller 
*
* Works with external payment API's to complete payment
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Callback extends Controller {

	function Callback()
	{
		parent::Controller();
	}

	function process() {
		// get gateway
		$gateway = $this->uri->segment(2);
		
		// get action
		$action = $this->uri->segment(3);
		
		// get charge_id
		$charge_id = $this->uri->segment(4);
		
		// compile all GET and POST parameters
		$params = array();
		
		// fancy tricks to get at $_GET
		$query_string = explode('?',$_SERVER['REQUEST_URI']);
		if (isset($query_string[1])) {
			parse_str($query_string[1], $params);
		}
		foreach ($_POST as $key => $value) {
			$params[$key] = $value;
		}
		
		// get client ID
		$this->db->select('client_id');
		$this->db->where('order_id',$charge_id);
		$result = $this->db->get('orders');
		$client = $result->row_array();
		$client_id = $client['client_id'];
		
		// get charge
		$this->load->model('charge_model');
		$charge = $this->charge_model->GetCharge($client_id, $charge_id);
		
		// get gateway
		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($client_id, $charge['gateway_id']);
		
		// is gateway enabled?
		if (!$gateway or $gateway['enabled'] == '0') {
			die($this->response->Error(5017));
		}
		
		// load the gateway
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		$gateway_settings = $this->$gateway_name->Settings();
		
		if ($gateway_settings['external'] == FALSE) {
			die('This gateway is not an external gateway.  Callbacks are futile.');
		}
		
		// pass to gateway
		$function = 'Callback_' . $action;
		
		if (!method_exists($this->$gateway_name,$function)) {
			die('Method doesn\'t exist in gateway library.');
		}
		
		// e.g., $this->Paypal_standard->Callback_confirm(1000, 345, array(charge), array(params));
		$this->$gateway_name->$function($client_id, $gateway, $charge, $params);
	}
}	
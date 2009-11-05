<?php

class Gateway extends Controller {

	function gateway()
	{
		parent::Controller();	
	}
	
	function index()
	{
		// grab the request
		$request = $this->input->post('request');
		
		// find out if the request is valid XML
		$xml = simplexml_load_string($request);
		
		// if it is not valid XML...
		if(!$xml) {
			die($this->response->Error(1000));
		}
		
		// get the api ID and secret key
		$api_id = $xml->authentication->apiID;
		$secret_key = $xml->authentication->secretKey;
		
		// authenticate the api ID
		$this->load->model('authentication_model', 'auth');
		
		if(!$this->auth->Authenticate($api_id, $secret_key)) {
			die($this->response->Error(1001));
		}	
	}
}

/* End of file gateway.php */
/* Location: ./system/application/controllers/gateway.php */
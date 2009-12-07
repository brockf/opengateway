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
		
		// Log the request
		$this->log_model->LogRequest($request);
		
		// find out if the request is valid XML
		$xml = simplexml_load_string($request);
		
		// if it is not valid XML...
		if(!$xml) {
			die($this->response->Error(1000));
		}
		
		// Make an array out of the XML
		$this->load->library('arraytoxml');
		$params = $this->arraytoxml->toArray($xml);
		
		// get the api ID and secret key
		$api_id = $params['authentication']['api_id'];
		$secret_key = $params['authentication']['secret_key'];
		
		// authenticate the api ID
		$this->load->model('authentication_model', 'auth');
		
		$client = $this->auth->Authenticate($api_id, $secret_key);
		$client_id = $client->client_id;
		
		if(!$client_id) {
			die($this->response->Error(1001));
		}	
		
		// Get the request type
		if(!isset($params['type'])) {
			die($this->response->Error(1002));
		}
		$request_type = $params['type'];
		
		// Make sure the first letter is capitalized
		$request_type = ucfirst($request_type);
		
		// validate the request type
		$this->load->model('request_type_model', 'request_type');
		$request_type_model = $this->request_type->ValidateRequestType($request_type);
		
		if(!$request_type_model) {
			die($this->response->Error(1002));
		}
		
		// Load the correct model and method
		$this->load->model($request_type_model);
		$response = $this->$request_type_model->$request_type($client_id, $params);
		
		// Make sure a proper format was passed
		if(isset($params['format'])) {
			$format = $params['format'];
			if(!in_array($format, array('xml', 'json', 'php'))) {
				die($this->response->Error(1006));
			}
		} else {
			$format = 'xml';
		}
		
		// Echo the response
		echo $this->response->FormatResponse($response, $format);		
	}
}

/* End of file gateway.php */
/* Location: ./system/application/controllers/gateway.php */
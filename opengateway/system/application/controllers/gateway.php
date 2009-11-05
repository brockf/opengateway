<?php

class Gateway extends Controller {

	function gateway()
	{
		parent::Controller();	
	}
	
	function index()
	{
		//Grab the request
		$request = $this->input->post('request');
		
		//Find out if the request is valid XML
		$xml = simplexml_load_string($request);
		
		//If it is not valid XML...
		if(!$xml)
		{
			//Use the reponse library to format the XML response
			$response = array('error' => 'Invalid Request',
							  'errorNum' => '1000'
							  );
			echo $this->response->format_response($response);
			exit;
		}
		
		//Get the api ID and secret key
		$api_id = $xml->authentication->apiID;
		$secret_key = $xml->authentication->secretKey;
		
		//authenticate the api ID
		$this->load->model('authentication_model', 'auth');
		$auth_ok = $this->auth->authenticate($api_id, $secret_key);
		
		if(!$auth_ok)
		{
			//Use the reponse library to format the XML response
			$response = array('error' => 'Invalid API ID or Secret Key',
							  'errorNum' => '1001'
							  );
			echo $this->response->format_response($response);
			exit;
		}
		
		
	}
	
	
}

/* End of file gateway.php */
/* Location: ./system/application/controllers/gateway.php */
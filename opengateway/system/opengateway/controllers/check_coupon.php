<?php

class Check_coupon extends Controller {
	$this->api_id = '';
	$this->secret_key = '';
	
	function index()
	{
		$this->load->model('authentication_model', 'auth');
		$client = $this->auth->Authenticate($this->api_id, $this->secret_key);
		
		
	}
}
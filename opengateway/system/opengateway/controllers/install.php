<?php
/**
* Install Controller 
*
* Installs OpenGateway by 1) generating config details and setting up the DB file and, 2) Creating the first admin account
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Install extends Controller {

	function Install()
	{
		parent::Controller();
	}
	
	function index() {
		$this->load->view(branded_view('install/configuration.php'));
	}
}
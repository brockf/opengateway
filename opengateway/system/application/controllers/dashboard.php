<?php
/**
* Dashboard Controller 
*
* Login to the dashboard, get an overview of the account
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Dashboard extends Controller {

	function Dashboard()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();
	}
	
	function index()
	{		
		$this->load->view('cp/dashboard');
	}

	/**
	* Show login screen
	*/
	function login() {
		$this->load->view('cp/login');
	}
	
	/**
	* Do Login
	*
	* Take a login post and process it
	* 
	* @return bool After redirect to dashboard or login screen, returns TRUE or FALSE
	*/
	function do_login() {
		if ($this->user->Login($this->input->post('username'),$this->input->post('password'))) {
			$this->notices->SetNotice($this->lang->line('notice_login_ok'));
			redirect('/dashboard');
			return true;
		}
		else {
			$this->notices->SetError($this->lang->line('error_login_incorrect'));
			redirect('/dashboard/login');
			return false;
		}
	}
}
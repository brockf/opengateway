<?php

class Dashboard extends Controller {

	function Dashboard()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();	
	}
	
	function index()
	{		
		if ($this->user->LoggedIn()) {
			return $this->show_dashboard();
		}
		else {
			redirect('/dashboard/login');
			return true;
		}
	}
	
	function login() {
		$this->load->view('cp/login');
	}
	
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
	
	function show_dashboard() {
		$this->load->view('cp/dashboard');
	}
}
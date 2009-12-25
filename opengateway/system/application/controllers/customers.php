<?php

class Customers extends Controller {

	function Customers()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();
	}
	
	function index()
	{	
		$this->navigation->PageTitle('Customers');
		
		$this->load->model('cp/dataset','dataset');
		
		$columns = array(
						array(
							'name' => 'ID #',
							'sort_column' => 'id',
							'type' => 'id',
							'width' => '10%',
							'filter' => 'id'),
						array(
							'name' => 'First Name',
							'sort_column' => 'customers.first_name',
							'type' => 'text',
							'width' => '25%',
							'filter' => 'first_name'),
						array(
							'name' => 'Last Name',
							'sort_column' => 'customers.last_name',
							'type' => 'text',
							'width' => '25%',
							'filter' => 'last_name'),
						array(
							'name' => 'Email Address',
							'sort_column' => 'email',
							'type' => 'text',
							'width' => '20%',
							'filter' => 'email')
					);
					
		// handle recurring plans if they exist
		$this->load->model('plan_model');
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'),array());
		
		if ($plans) {
			// build $options
			$options = array();
			while (list(,$plan) = each($plans)) {
				$options[$plan['id']] = $plan['name'];
			}
			
			$columns[] = array(
							'name' => 'Active Plans',
							'type' => 'select',
							'options' => $options,
							'filter' => 'plan_id',
							'width' => '20%'
							);
		}
		
		$this->dataset->Initialize('customer_model','GetCustomers',$columns);
		
		$this->load->view('cp/customers.php');
	}
}
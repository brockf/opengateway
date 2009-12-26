<?php
/**
* Customers Controller 
*
* Manage customers
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Customers extends Controller {

	function Customers()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();
	}
	
	/**
	* Manage customers
	*
	* Lists all active customers, with optional filters
	*/
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
							'filter' => 'customer_id'),
						array(
							'name' => 'First Name',
							'sort_column' => 'customers.first_name',
							'type' => 'text',
							'width' => '20%',
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
		else {
			$columns[] = array(
				'name' => 'Plan Link',
				'width' => '20%'
				);
		}
		
		$this->dataset->Initialize('customer_model','GetCustomers',$columns);
		
		// add actions
		$this->dataset->Action('Delete','customers/delete');
		
		$this->load->view('cp/customers.php');
	}
	
	/**
	* Delete Customers
	*
	* Delete customers as passed from the dataset
	*
	* @param string Hex'd, base64_encoded, serialized array of customer ID's
	* @param string Return URL for Dataset
	*
	* @return bool Redirects to dataset
	*/
	function delete ($customers, $return_url) {
		$this->load->model('customer_model');
		$this->load->library('asciihex');
		
		$customers = unserialize(base64_decode($this->asciihex->HexToAscii($customers)));
		$return_url = base64_decode($this->asciihex->HexToAscii($return_url));
		
		foreach ($customers as $customer) {
			$this->customer_model->DeleteCustomer($this->user->Get('client_id'),$customer);
		}
		
		$this->notices->SetNotice($this->lang->line('customers_deleted'));
		
		redirect($return_url);
		return true;
	}
}
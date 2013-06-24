<?php
/**
* Customers Controller
*
* Manage customers
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Customers extends Controller {

	function __construct()
	{
		parent::__construct();

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
							'width' => '15%',
							'filter' => 'first_name'),
						array(
							'name' => 'Last Name',
							'sort_column' => 'customers.last_name',
							'type' => 'text',
							'width' => '20%',
							'filter' => 'last_name'),
						array(
							'name' => 'Email Address',
							'sort_column' => 'email',
							'type' => 'text',
							'width' => '25%',
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
				'width' => '15%'
				);
		}

		$columns[] = array(
				'name' => '',
				'width' => '10%'
				);

		// set total rows by hand to reduce database load
		$result = $this->db->select('COUNT(customer_id) AS total_rows',FALSE)->where('active','1')->from('customers')->get();
		$this->dataset->total_rows((int)$result->row()->total_rows);

		$this->dataset->Initialize('customer_model','GetCustomers',$columns);

		// add actions
		$this->dataset->Action('Delete','customers/delete');

		$this->load->view(branded_view('cp/customers.php'));
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

	/**
	* Edit customer
	*
	* Edit customer address, etc.
	*
	* @return string view
	*/
	function edit($id) {
		$this->navigation->PageTitle('Edit Customer');

		$this->load->model('states_model');
		$countries = $this->states_model->GetCountries();
		$states = $this->states_model->GetStates();

		$this->load->model('customer_model');

		$customer = $this->customer_model->GetCustomer($this->user->Get('client_id'),$id);

		$data = array(
					'form_title' => 'Edit Customer',
					'form_action' => 'customers/post_edit/' . $id,
					'states' => $states,
					'countries' => $countries,
					'form' => $customer
					);

		$this->load->view(branded_view('cp/customer_form.php'),$data);
	}

	/**
	* Post Customer Update
	*
	* Handles an update customer post
	*
	* @return string view
	*/
	function post_edit ($id) {
		$this->load->library('field_validation');

		if ($this->input->post('country') != '' and !$this->field_validation->ValidateCountry($this->input->post('country'))) {
			$this->notices->SetError('Your country is in an improper format.');
			$error = true;
		}
		if ($this->input->post('email') != '' and !$this->field_validation->ValidateEmailAddress($this->input->post('email'))) {
			$this->notices->SetError('Email is in an improper format.');
			$error = true;
		}

		if (isset($error)) {
			redirect('customers/edit/' . $id);
			return false;
		}

		$params = array(
						'first_name' => $this->input->post('first_name'),
						'last_name' => $this->input->post('last_name'),
						'company' => $this->input->post('company'),
						'address_1' => $this->input->post('address_1'),
						'address_2' => $this->input->post('address_2'),
						'city' => $this->input->post('city'),
						'state' => ($this->input->post('country') == 'US' or $this->input->post('country') == 'CA') ? $this->input->post('state_select') : $this->input->post('state'),
						'country' => $this->input->post('country'),
						'postal_code' => $this->input->post('postal_code'),
						'phone' => $this->input->post('phone'),
						'email' => $this->input->post('email'),
						'internal_id' => $this->input->post('internal_id')
						);

		$this->load->model('customer_model');

		$this->customer_model->UpdateCustomer($this->user->Get('client_id'), $id, $params);
		$this->notices->SetNotice($this->lang->line('customer_updated'));

		redirect('customers');

		return true;
	}
}
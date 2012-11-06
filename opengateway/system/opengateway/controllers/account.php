<?php
/**
* Account Controller
*
* Update account details, logout
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Account extends Controller {

	function __construct()
	{
		parent::__construct();

		// perform control-panel specific loads
		CPLoader();
	}

	/**
	* Update Account
	*
	* Update your account details, password, etc.
	*
	* @return string view
	*/
	function index() {
		$this->navigation->PageTitle('Update My Account');

		$this->load->model('states_model');
		$countries = $this->states_model->GetCountries();
		$states = $this->states_model->GetStates();

		$client = $this->client_model->GetClient($this->user->Get('client_id'),$this->user->Get('client_id'));
		$client['gmt_offset'] = $client['timezone'];

		$data = array(
					'form_title' => 'Update My Account',
					'form_action' => 'account/post',
					'states' => $states,
					'countries' => $countries,
					'form' => $client
					);

		$this->load->view(branded_view('cp/account_form.php'),$data);
	}

	/**
	* Post Account Update
	*
	* Handles an update account post
	*
	* @return string view
	*/
	function post () {
		$this->load->library('field_validation');

		if ($this->input->post('first_name') == '') {
			$this->notices->SetError('First Name is a required field.');
			$error = true;
		}
		if ($this->input->post('last_name') == '') {
			$this->notices->SetError('Last Name is a required field.');
			$error = true;
		}
		if (!$this->field_validation->ValidateCountry($this->input->post('country'))) {
			$this->notices->SetError('Your country is in an improper format.');
			$error = true;
		}
		if (!$this->field_validation->ValidateEmailAddress($this->input->post('email'))) {
			$this->notices->SetError('Email is in an improper format.');
			$error = true;
		}

		$password = $this->input->post('password'); // put password in variable for later functions

		if (!empty($password) and $this->input->post('password') != $this->input->post('password2')) {
			$this->notices->SetError('Your passwords do not match.');
			$error = true;
		}
		elseif (!empty($password) and strlen($this->input->post('password')) < 6) {
			$this->notices->SetError('You must supply a password of at least 6 characters to change the password.');
			$error = true;
		}

		if (isset($error)) {
			redirect('account/');
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
						'timezone' => $this->input->post('timezones')
						);

		if (!empty($password)) {
			$params['password'] = $this->input->post('password');
		}

		$this->client_model->UpdateClient($this->user->Get('client_id'), $this->user->Get('client_id'), $params);
		$this->notices->SetNotice($this->lang->line('account_updated'));

		redirect('account');

		return true;
	}

	/**
	* Logout
	*
	* Logout the user, return to login page
	*
	* @return bool True, with redirect
	*/
	function logout() {
		$this->user->Logout();

		redirect('dashboard/login');
		return true;
	}
}
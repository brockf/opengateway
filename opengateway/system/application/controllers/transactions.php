<?php
/**
* Transactions Controller 
*
* Manage transactions, create new transactions
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Transactions extends Controller {

	function Transactions()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();
	}
	
	function index()
	{	
		$this->navigation->PageTitle('Transactions');
		
		$this->load->model('cp/dataset','dataset');
		
		$columns = array(
						array(
							'name' => 'ID #',
							'sort_column' => 'id',
							'type' => 'id',
							'width' => '10%',
							'filter' => 'id'),
						array(
							'name' => 'Status',
							'sort_column' => 'status',
							'type' => 'select',
							'options' => array('1' => 'ok','0' => 'failed'),
							'width' => '10%',
							'filter' => 'status'),
						array(
							'name' => 'Date',
							'sort_column' => 'timestamp',
							'type' => 'date',
							'width' => '20%',
							'filter' => 'timestamp',
							'field_start_date' => 'start_date',
							'field_end_date' => 'end_date'),
						array(
							'name' => 'Amount',
							'sort_column' => 'amount',
							'type' => 'text',
							'width' => '10%',
							'filter' => 'amount'),
						array(
							'name' => 'Customer Name',
							'sort_column' => 'customers.last_name',
							'type' => 'text',
							'width' => '20%',
							'filter' => 'customer_last_name'),
						array(
							'name' => 'Credit Card',
							'sort_column' => 'card_last_four',
							'type' => 'text',
							'width' => '12%',
							'filter' => 'card_last_four'),
						array(
							'name' => 'Recurring',
							'width' => '12%'
							),
						array(
							'name' => '',
							'width' => '16%'
							)
					);
		
		$this->dataset->Initialize('order_model','GetCharges',$columns);
		
		// sidebar
		$this->navigation->SidebarButton('New Charge','transactions/create');
		
		$this->load->view('cp/transactions.php');
	}
	
	/**
	* New Charge
	*
	* Creates a new one-time or recurring-charge
	*
	* @return string viewe
	*/
	function create() {
		$this->navigation->PageTitle('New Transaction');
	
		$this->load->model('states_model');
		$countries = $this->states_model->GetCountries();
		$states = $this->states_model->GetStates();
		
		// load plans if they exist
		$this->load->model('plan_model');
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'),array());
		
		// load existing customers
		$this->load->model('customer_model');
		$customers = $this->customer_model->GetCustomers($this->user->Get('client_id'),array());
		
		// load existing gateways
		$this->load->model('gateway_model');
		$gateways = $this->gateway_model->GetGateways($this->user->Get('client_id'),array());
		
		$data = array(
					'states' => $states,
					'countries' => $countries,
					'plans' => $plans,
					'customers' => $customers,
					'gateways' => $gateways
					);
					
		$this->load->view('cp/new_transaction.php', $data);
		return true;
	}
	
	/**
	* Post Charge
	*/
	function post() {
		$this->load->library('opengateway');
		
		if ($this->input->post('recurring') == '0') {
			$charge = new Charge;
		}
		else {
			$charge = new Recur;
		}
		
		$api_url = site_url('api');
		$api_url = ($this->config->item('ssl_active') == TRUE) ? str_replace('http://','https://',$api_url) : $api_url;
		
		$charge->Authenticate(
						$this->user->Get('api_id'),
						$this->user->Get('secret_key'),
						site_url('api')
					);
		
		$charge->Amount($this->input->post('amount'));
		
		$charge->CreditCard(
						$this->input->post('cc_name'),
						$this->input->post('cc_number'),
						$this->input->post('cc_expiry_month'),
						$this->input->post('cc_expiry_year'),
						$this->input->post('cc_security')
					);
					
		if ($this->input->post('recurring') == '1') {
			$charge->UsePlan($this->input->post('recurring_plan'));
		}		
		elseif ($this->input->post('recurring') == '2') {
			$free_trial = $this->input->post('free_trial');
			$free_trial = empty($free_trial) ? NULL : $free_trial;
			
			$occurrences = ($this->input->post('recurring_end') == 'occurrences') ? $this->input->post('occurrences') : NULL;
			
			$start_date = $this->input->post('start_date_year') . '-' . $this->input->post('start_date_month') . '-' . $this->input->post('start_date_day');
			$end_date = $this->input->post('end_date_year') . '-' . $this->input->post('end_date_month') . '-' . $this->input->post('end_date_day');
			
			$end_date = ($this->input->post('recurring_end') == 'date') ? $end_date : NULL;
			
			$charge->Schedule(
						$this->input->post('interval'),
						$free_trial,
						$occurrences,
						$start_date,
						$end_date
					);
		}
		
		if ($this->input->post('customer_id') != '') {
			$charge->UseCustomer($this->input->post('customer_id'));
		}
		else {
			$first_name = ($this->input->post('first_name') == 'First Name') ? '' : $this->input->post('first_name');
			$last_name = ($this->input->post('last_name') == 'Last Name') ? '' : $this->input->post('last_name');
			$email = ($this->input->post('email') == 'email@example.com') ? '' : $this->input->post('email');
			$state = ($this->input->post('country') == 'US' or $this->input->post('country') == 'CA') ? $this->input->post('state_select') : $this->input->post('state');
			
			if (!empty($first_name) and !empty($last_name)) {
				$charge->Customer(
								$first_name,
								$last_name,
								$this->input->post('company'),
								$this->input->post('address_1'),
								$this->input->post('address_2'),
								$this->input->post('city'),
								$state,
								$this->input->post('country'),
								$this->input->post('postal_code'),
								$this->input->post('phone'),
								$email
						);	
			}	
		}
		
		if ($this->input->post('gateway_type') == 'specify') {
			$charge->UseGateway($this->input->post('gateway'));
		}
		
		$response = $charge->Charge();
		
		if (isset($response['response_code']) and ($response['response_code'] == '1' or $response['response_code'] == '100')) {
			$this->notices->SetNotice($this->lang->line('transaction_ok'));
		}
		else {
			$this->notices->SetError($this->lang->line('transaction_error') . $response['error_text'] . ' (#' . $response['error'] . ')');
		}
		
		if ($response['recurring_id']) {
			$redirect = site_url('transactions/recurring/' . $response['recurring_id']);
		}
		elseif ($response['charge_id']) {
			$redirect = site_url('transactions/charge/' . $response['charge_id']);
		}
		else {
			$redirect = site_url('transactions/create');
		}
		
		redirect($redirect);
		
		return true;
	}
	
	/**
	* View Individual Charge
	*
	*/
	function charge ($id) {
		$this->load->model('order_model');
		$charge = $this->order_model->GetCharge($this->user->Get('client_id'),$id);
		
		$data = $charge;
		
		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($this->user->Get('client_id'),$charge['gateway_id']);
		
		$data['gateway'] = $gateway;
		
		$details = $this->order_model->GetChargeGatewayInfo($id);
		
		$data['details'] = $details;
		 
		$this->load->view('cp/charge', $data);
		
		return true;
	}
	
	/**
	* View Recurring Charge
	*/
	function recurring ($id) {
		$this->load->model('subscription_model');
		$recurring = $this->subscription_model->GetRecurring($this->user->Get('client_id'),$id);
		
		$data = $recurring;
		
		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($this->user->Get('client_id'),$recurring['gateway_id']);
		
		$data['gateway'] = $gateway;
		
		$this->load->view('cp/recurring', $data);
	}
	
	/**
	* Cancel Recurring
	*/
	function cancel_recurring ($id) {
		$this->load->model('subscription_model');
		$this->subscription_model->CancelRecurring($this->user->Get('client_id'),$id);
		
		$this->notices->SetNotice('Recurring charge #' . $id . ' cancelled');
		
		redirect(site_url('transactions/recurring/' . $id));
	}
}
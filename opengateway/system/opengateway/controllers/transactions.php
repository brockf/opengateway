<?php
/**
* Transactions Controller
*
* Manage transactions, create new transactions
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Transactions extends Controller {

	function __construct()
	{
		parent::__construct();

		// perform control-panel specific loads
		CPLoader();
	}

	function index()
	{
		$this->navigation->PageTitle('Transactions');

		$this->load->model('cp/dataset','dataset');

		// prepare gateway select
		$this->load->model('gateway_model');
		$gateways = $this->gateway_model->GetGateways($this->user->Get('client_id'));

		// we're gonna short gateway names
		$this->load->helper('shorten');

		$gateway_options = array();
		$gateway_values = array();
		if (is_array($gateways) && count($gateways))
		{
			foreach ($gateways as $gateway) {
				$gateway_options[$gateway['id']] = $gateway['gateway'];
				$gateway_values[$gateway['id']] = shorten($gateway['gateway'], 15);
			}
		}

		// now get deleted gateways and add them in so that, when viewing the
		// transactions table, we have names for each gateway in this array
		$gateways = $this->gateway_model->GetGateways($this->user->Get('client_id'), array('deleted' => '1'));

		if (!empty($gateways)) {
			foreach ($gateways as $gateway) {
				$gateway['gateway_short'] = shorten($gateway['gateway'], 15);

				$gateway_values[$gateway['id']] = $gateway['gateway_short'];
			}
		}

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
							'options' => array('1' => 'ok', '2' => 'refunded', '0' => 'failed', '3' => 'failed-repeat'),
							'width' => '4%',
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
							'name' => 'Card',
							'sort_column' => 'card_last_four',
							'type' => 'text',
							'width' => '10%',
							'filter' => 'card_last_four'),
						array(
							'name' => 'Gateway',
							'type' => 'select',
							'width' => '10%',
							'options' => $gateway_options,
							'filter' => 'gateway_id'),
						array(
							'name' => 'Recurring',
							'width' => '10%',
							'type' => 'text',
							'filter' => 'recurring_id'
							)
					);

		// set total rows by hand to reduce database load
		$result = $this->db->select('COUNT(order_id) AS total_rows',FALSE)
						   ->from('orders')
						   ->where('client_id', $this->user->Get('client_id'))
						   ->get();
		$this->dataset->total_rows((int)$result->row()->total_rows);

		if ($this->dataset->total_rows > 5000) {
			// we're going to have a memory error with this much data
			$this->dataset->use_total_rows();
		}

		$this->dataset->Initialize('charge_model','GetCharges',$columns);

		$this->load->model('charge_model');

		// get total charges
		$total_amount = $this->charge_model->GetTotalAmount($this->user->Get('client_id'),$this->dataset->params);

		// sidebar
		$this->navigation->SidebarButton('Recurring Charges','transactions/all_recurring');
		$this->navigation->SidebarButton('New Charge','transactions/create');

		$data = array(
					'total_amount' => $total_amount,
					'gateways' => $gateway_values
					);

		$this->load->view(branded_view('cp/transactions.php'), $data);
	}

	function all_recurring ()
	{
		$this->navigation->PageTitle('Recurring Charges');

		$this->load->model('cp/dataset','dataset');

		$columns = array(
						array(
							'name' => 'ID #',
							'sort_column' => 'id',
							'type' => 'id',
							'width' => '7%',
							'filter' => 'id'),
						array(
							'name' => 'Status',
							'sort_column' => 'active',
							'type' => 'select',
							'options' => array('1' => 'active','0' => 'inactive'),
							'width' => '10%',
							'filter' => 'active'),
						array(
							'name' => 'Date Created',
							'width' => '13%'),
						array(
							'name' => 'Last Charge',
							'width' => '13%'
							),
						array(
							'name' => 'Next Charge',
							'width' => '12%'
							),
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
							'width' => '15%',
							'filter' => 'customer_last_name')
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
							'name' => 'Plan',
							'type' => 'select',
							'options' => $options,
							'filter' => 'plan_id',
							'width' => '15%'
							);
		}
		else {
			$columns[] = array(
				'name' => 'Plan',
				'width' => '15%'
				);
		}

		$columns[] = array(
							'name' => '',
							'width' => '10%'
							);

		// set total rows by hand to reduce database load
		$result = $this->db->select('COUNT(subscription_id) AS total_rows',FALSE)
						   ->where('active','1')
						   ->where('client_id', $this->user->Get('client_id'))
						   ->from('subscriptions')
						   ->get();
		$this->dataset->total_rows((int)$result->row()->total_rows);

		$this->dataset->Initialize('recurring_model','GetRecurrings',$columns);

		// sidebar
		$this->navigation->SidebarButton('Charge Records','transactions');
		$this->navigation->SidebarButton('New Charge','transactions/create');

		$this->load->view(branded_view('cp/recurrings.php'));
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

		if (!empty($gateways)) {
			foreach ($gateways as $key => $gateway) {
				$gateway_name = $gateway['library_name'];
				$this->load->library('payment/' . $gateway_name);
				$gateways[$key]['settings'] = $this->$gateway_name->Settings();
			}

			// get default gateway settings
			$default_gateway = $this->gateway_model->GetGatewayDetails($this->user->Get('client_id'));
			$default_gateway_name = $default_gateway['name'];
			$default_gateway_settings = $this->$default_gateway_name->Settings();
		}
		else {
			$default_gateway_settings = FALSE;
			$gateways = array();
		}

		// coupons
		$result = $this->db->select('COUNT(coupon_id) AS total_rows',FALSE)
						   ->from('coupons')
						   ->where('coupon_deleted','0')
						   ->where('client_id', $this->user->Get('client_id'))
						   ->get();
		$coupon_count = (int)$result->row()->total_rows;

		if ($coupon_count > 0 and $coupon_count < 100) {
			$this->load->model('coupon_model');
			$coupons = $this->coupon_model->get_coupons();
		}
		elseif ($coupon_count >= 100) {
			$coupons = TRUE;
		}
		else {
			$coupons == TRUE;
		}

		$data = array(
					'states' => $states,
					'countries' => $countries,
					'plans' => $plans,
					'customers' => $customers,
					'gateways' => $gateways,
					'default_gateway_settings' => $default_gateway_settings,
					'coupons' => $coupons
					);

		$this->load->view(branded_view('cp/new_transaction.php'), $data);
		return TRUE;
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

		// coupon
		if ($this->input->post('coupon')) {
			$charge->Coupon($this->input->post('coupon'));
		}

		if ($this->input->post('cc_number')) {
			$charge->CreditCard(
							$this->input->post('cc_name'),
							$this->input->post('cc_number'),
							$this->input->post('cc_expiry_month'),
							$this->input->post('cc_expiry_year'),
							$this->input->post('cc_security')
						);
		}

		if ($this->input->post('recurring') == '1') {
			$charge->UsePlan($this->input->post('recurring_plan'));
		}
		elseif ($this->input->post('recurring') == '2') {
			$free_trial = $this->input->post('free_trial');
			$free_trial = empty($free_trial) ? FALSE : $free_trial;

			$occurrences = ($this->input->post('recurring_end') == 'occurrences') ? $this->input->post('occurrences') : FALSE;

			$start_date = $this->input->post('start_date_year') . '-' . $this->input->post('start_date_month') . '-' . $this->input->post('start_date_day');
			$end_date = $this->input->post('end_date_year') . '-' . $this->input->post('end_date_month') . '-' . $this->input->post('end_date_day');

			$end_date = ($this->input->post('recurring_end') == 'date') ? $end_date : FALSE;

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

		// set return URL
		if ($this->input->post('recurring') == '0') {
			$charge->Param('return_url',site_url('transactions/latest_charge'));
		}
		else {
			$charge->Param('return_url',site_url('transactions/latest_recur'));
		}
		$charge->Param('cancel_url',site_url('transactions/create'));

		$response = $charge->Charge();

		if (!is_array($response) or isset($response['error'])) {
			$this->notices->SetError($this->lang->line('transaction_error') . $response['error_text'] . ' (#' . $response['error'] . ')');
		}
		elseif (isset($response['response_code']) and $response['response_code'] == '2') {
			$this->notices->SetError($this->lang->line('transaction_error') . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')');
		}
		else {
			$this->notices->SetNotice($this->lang->line('transaction_ok'));
		}

		if (isset($response['recurring_id'])) {
			$redirect = site_url('transactions/recurring/' . $response['recurring_id']);
		}
		elseif (isset($response['charge_id'])) {
			$redirect = site_url('transactions/charge/' . $response['charge_id']);
		}
		else {
			$redirect = site_url('transactions/create');
		}

		redirect($redirect);

		return TRUE;
	}

	/**
	* View Latest Charge (from a PayPal Express Redirect)
	*/
	function latest_charge () {
		$this->load->model('charge_model');
		$charge = $this->charge_model->GetCharges($this->user->Get('client_id'), array());

		$charge = $charge[0];

		return redirect('transactions/charge/' . $charge['id']);
	}

	/**
	* View Latest Recur (from a PayPal Express Redirect)
	*/
	function latest_recur () {
		$this->load->model('recurring_model');
		$charge = $this->recurring_model->GetRecurrings($this->user->Get('client_id'), array());

		$charge = $charge[0];

		return redirect('transactions/recurring/' . $charge['id']);
	}

	/**
	* View Individual Charge
	*
	*/
	function charge ($id) {
		$this->navigation->PageTitle('Charge #' . $id);

		$this->load->model('charge_model');
		$charge = $this->charge_model->GetCharge($this->user->Get('client_id'),$id);

		$data = $charge;

		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($this->user->Get('client_id'),$charge['gateway_id'],TRUE);

		$data['gateway'] = $gateway;

		$details = $this->charge_model->GetChargeGatewayInfo($id);

		$data['details'] = $details;

		$this->load->view(branded_view('cp/charge'), $data);

		return TRUE;
	}

	/**
	* View Recurring Charge
	*/
	function recurring ($id = FALSE) {
		if (!$id) {
			// pass to recurring index
			return $this->all_recurring();
		}

		$this->navigation->PageTitle('Recurring Charge #' . $id);

		$this->load->model('recurring_model');
		$recurring = $this->recurring_model->GetRecurring($this->user->Get('client_id'),$id);

		$data = $recurring;

		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($this->user->Get('client_id'),$recurring['gateway_id'],TRUE);

		$data['gateway'] = $gateway;

		// they may need to change plans
		if (isset($data['plan'])) {
			// load plans
			$this->load->model('plan_model');

			$plans = $this->plan_model->GetPlans($this->user->Get('client_id'),array());

			$data['plans'] = $plans;
		}

		$this->load->view(branded_view('cp/recurring'), $data);
	}

	/**
	* Update Credit Card
	*/
	function update_cc ($id) {
		$this->load->model('recurring_model');
		$recurring = $this->recurring_model->GetRecurring($this->user->Get('client_id'), $id);

		// load existing gateways
		$this->load->model('gateway_model');
		$gateways = $this->gateway_model->GetGateways($this->user->Get('client_id'),array());

		// load plans if they exist
		$this->load->model('plan_model');
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'),array());

		$data = array(
					'recurring' => $recurring,
					'gateways' => $gateways,
					'plans' => $plans
				);
		$this->load->view(branded_view('cp/update_cc'), $data);
	}

	/**
	* Post Update Credit Card
	*/
	function post_update_cc () {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('cc_number','Credit Card Number','required|is_natural');
		$this->form_validation->set_rules('cc_name','Credit Card Name','required');
		$this->form_validation->set_rules('recurring_id','Recurring ID # (hidden)','required|is_natural');

		if ($this->form_validation->run() === FALSE) {
			$this->notices->SetError(validation_errors());
		}

		// passed validation
		$recurring_id = $this->input->post('recurring_id');
		$credit_card = array(
						'card_num' => $this->input->post('cc_number'),
						'card_name' => $this->input->post('card_name'),
						'exp_month' => $this->input->post('cc_expiry_month'),
						'exp_year' => $this->input->post('cc_expiry_year')
						);
		$gateway_id = $this->input->post('gateway');

		$new_plan_id = $this->input->post('recurring_plan');

		$this->load->model('gateway_model');
		$response = $this->gateway_model->UpdateCreditCard($this->user->Get('client_id'), $recurring_id, $credit_card, $gateway_id, $new_plan_id);

		if (!is_array($response) or isset($response['error'])) {
			$this->notices->SetError($this->lang->line('transaction_error') . $response['error_text'] . ' (#' . $response['error'] . ')');
		}
		elseif (isset($response['response_code']) and $response['response_code'] == '2') {
			$this->notices->SetError($this->lang->line('transaction_error') . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')');
		}
		else {
			$this->notices->SetNotice($this->lang->line('transaction_ok'));
		}

		if (isset($response['recurring_id'])) {
			$redirect = site_url('transactions/recurring/' . $response['recurring_id']);
		}
		else {
			$redirect = site_url('transactions/update_cc/' . $recurring_id);
		}

		redirect($redirect);

		return TRUE;
	}

	/**
	* Change Recurring Plan
	*/
	function change_plan ($id) {
		$this->load->model('recurring_model');
		if ($this->recurring_model->ChangeRecurringPlan($this->user->Get('client_id'),$id,$this->input->post('plan'))) {
			$this->notices->SetNotice('Recurring charge #' . $id . ' updated to a new plan successfully.');
		}
		else {
			$this->notices->SetError('Recurring charge #' . $id . ' could not be updated.');
		}

		redirect(site_url('transactions/recurring/' . $id));
	}

	/**
	* Cancel Recurring
	*/
	function cancel_recurring ($id) {
		$this->load->model('recurring_model');
		$this->recurring_model->CancelRecurring($this->user->Get('client_id'),$id);

		$this->notices->SetNotice('Recurring charge #' . $id . ' cancelled');

		redirect(site_url('transactions/recurring/' . $id));
	}

	/**
	* Refund a Charge
	*/
	function refund ($charge_id) {
		$this->load->model('gateway_model');
		if ($this->gateway_model->Refund($this->user->Get('client_id'),$charge_id)) {
			$this->notices->SetNotice('Charge #' . $charge_id . ' refunded.');
		}
		else {
			$this->notices->SetError('Charge #' . $charge_id . ' could not be refunded.');
		}

		redirect(site_url('transactions/charge/' . $charge_id));
	}
}
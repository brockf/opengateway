<?php
/**
* Plans Controller
*
* Manage plans, create new plans, edit plans
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Plans extends Controller {

	function __construct()
	{
		parent::__construct();

		// perform control-panel specific loads
		CPLoader();
	}

	function index()
	{
		$this->navigation->PageTitle('Plans');

		$this->load->model('cp/dataset','dataset');

		$columns = array(
						array(
							'name' => 'ID #',
							'sort_column' => 'id',
							'type' => 'id',
							'width' => '10%',
							'filter' => 'plan_id'),
						array(
							'name' => 'Name',
							'sort_column' => 'plans.name',
							'type' => 'text',
							'width' => '20%',
							'filter' => 'name'),
						array(
							'name' => 'Fee',
							'sort_column' => 'plans.amount',
							'type' => 'text',
							'width' => '15%',
							'filter' => 'amount'),
						array(
							'name' => 'Interval',
							'sort_column' => 'plans.interval',
							'type' => 'text',
							'width' => '14%',
							'filter' => 'interval'),
						array(
							'name' => 'Trial Period',
							'sort_column' => 'plans.free_trial',
							'type' => 'text',
							'width' => '15%',
							'filter' => 'free_trial'),
						array(
							'name' => 'Customers',
							'sort_column' => 'plans.customer_count',
							'width' => '15%'),
						array(
							'name' => '',
							'width' => '6%'
							)
					);

		$this->dataset->Initialize('plan_model','GetPlans',$columns);

		// add actions
		$this->dataset->Action('Delete','plans/delete');

		// sidebar
		$this->navigation->SidebarButton('Create a plan','plans/new_plan');

		$this->load->view(branded_view('cp/plans.php'));
	}

	/**
	* Delete Plans
	*
	* Delete plans as passed from the dataset
	*
	* @param string Hex'd, base64_encoded, serialized array of plan ID's
	* @param string Return URL for Dataset
	*
	* @return bool Redirects to dataset
	*/
	function delete ($plans, $return_url) {
		$this->load->model('plan_model');
		$this->load->library('asciihex');

		$plans = unserialize(base64_decode($this->asciihex->HexToAscii($plans)));
		$return_url = base64_decode($this->asciihex->HexToAscii($return_url));

		foreach ($plans as $plan) {
			$this->plan_model->DeletePlan($this->user->Get('client_id'),$plan);
		}

		$this->notices->SetNotice($this->lang->line('plans_deleted'));

		redirect($return_url);
		return true;
	}

	/**
	* New Plan
	*
	* Create a new plan
	*
	* @return true Passes to view
	*/
	function new_plan ()
	{
		$this->navigation->PageTitle('New Plan');

		$this->load->model('email_model');

		$triggers = $this->email_model->GetTriggers();

		$data = array(
					'triggers' => $triggers,
					'form_title' => 'Create New Plan',
					'form_action' => 'plans/post/new'
					);

		$this->load->view(branded_view('cp/plan_form.php'),$data);
	}

	/**
	* Handle New/Edit Plan Post
	*/
	function post ($action = 'new', $id = false) {
		$this->load->library('field_validation');

		if ($this->input->post('name') == '') {
			$this->notices->SetError('Plan Name is a required field.');
			$error = true;
		}
		elseif ($this->input->post('interval') < 1 or !is_numeric($this->input->post('interval'))) {
			$this->notices->SetError('Charge Interval must be a number greater than 1.');
			$error = true;
		}
		elseif ($this->input->post('plan_type') != 'free' and !$this->field_validation->ValidateAmount($this->input->post('amount'))) {
			$this->notices->SetError('Charge Amount is in an improper format.');
			$error = true;
		}
		elseif ($this->input->post('occurrences_radio') != '0' and !is_numeric($this->input->post('occurrences'))) {
			$this->notices->SetError('Occurrences is in an improper format.');
			$error = true;
		}
		elseif ($this->input->post('free_trial_radio') != '0' and !is_numeric($this->input->post('free_trial'))) {
			$this->notices->SetError('Free Trial is in an improper format.');
			$error = true;
		}

		// check uniqueness of plan name
		$this->load->model('plan_model');
		if ($action == 'new') {
			$plans = $this->plan_model->GetPlans($this->user->Get('client_id'),array('name' => $this->input->post('name',true)));

			if (is_array($plans)) {
				$this->notices->SetError('Plan name is not unique.');
				$error = true;
			}
		}

		if (isset($error)) {
			if ($action == 'new') {
				redirect('plans/new_plan');
				return false;
			}
			else {
				redirect('plans/edit/' . $id);
				return false;
			}
		}

		$params = array(
						'name' => $this->input->post('name',true),
						'plan_type' => ($this->input->post('plan_type') == 'free') ? 'free' : 'paid',
						'amount' => ($this->input->post('plan_type') == 'free') ? '0' : $this->input->post('amount',true),
						'interval' => $this->input->post('interval',true),
						'notification_url' => ($this->input->post('notification_url') == 'http://') ? '' : $this->input->post('notification_url',true),
						'occurrences' => ($this->input->post('occurrences_radio') == '0') ? '0' : $this->input->post('occurrences',true),
						'free_trial' => ($this->input->post('free_trial_radio') == '0') ? '0' : $this->input->post('free_trial',true),
					);

		if ($action == 'new') {
			$email_id = $this->plan_model->NewPlan($this->user->Get('client_id'), $params);
			$this->notices->SetNotice($this->lang->line('plan_added'));
		}
		else {
			$this->plan_model->UpdatePlan($this->user->Get('client_id'), $id, $params);
			$this->notices->SetNotice($this->lang->line('plan_updated'));
		}

		redirect('plans');

		return true;
	}

	/**
	* Edit Plan
	*
	* Show the plan form, preloaded with variables
	*
	* @param int $id the ID of the plan
	*
	* @return string The plan form view
	*/
	function edit($id) {
		$this->navigation->PageTitle('Edit Plan');

		$this->load->model('plan_model');
		$this->load->model('email_model');

		$triggers = $this->email_model->GetTriggers();

		$plan = $this->plan_model->GetPlan($this->user->Get('client_id'),$id);

		$form = array(
				'name' => $plan['name'],
				'type' => $plan['type'],
				'amount' => ($plan['type'] != 'free') ? $plan['amount'] : '',
				'interval' => $plan['interval'],
				'notification_url' => ($plan['notification_url'] == '') ? 'http://' : $plan['notification_url'],
				'occurrences' => $plan['occurrences'],
				'free_trial' => $plan['free_trial']
				);

		$data = array(
					'form_title' => 'Edit Plan',
					'form_action' => 'plans/post/edit/' . $plan['id'],
					'form' => $form,
					'triggers' => $triggers
				);

		$this->load->view(branded_view('cp/plan_form.php'),$data);
	}
}
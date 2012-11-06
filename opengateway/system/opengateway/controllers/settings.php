<?php
/**
* Settings Controller
*
* Manage emails, gateway, API key
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Settings extends Controller {

	function __construct()
	{
		parent::__construct();

		// perform control-panel specific loads
		CPLoader();
	}

	function index() {

	}

	/**
	* Manage emails
	*
	* Lists active emails for managing
	*/
	function emails()
	{
		$this->navigation->PageTitle('Manage Emails');

		$this->load->model('cp/dataset','dataset');

		$columns = array(
						array(
							'name' => 'ID #',
							'sort_column' => 'id',
							'type' => 'id',
							'width' => '5%',
							'filter' => 'id'),
						array(
							'name' => 'Trigger',
							'sort_column' => 'emails.trigger',
							'type' => 'text',
							'width' => '20%',
							'filter' => 'trigger'),
						array(
							'name' => 'To:',
							'width' => '15%',
							'sort_column' => 'emails.to_address',
							'filter' => 'to_address',
							'type' => 'text'),
						array(
							'name' => 'Email Subject',
							'sort_column' => 'emails.email_subject',
							'type' => 'text',
							'width' => '25%',
							'filter' => 'email_subject'),
						array(
							'name' => 'Format',
							'width' => '5%')
					);

		// handle recurring plans if they exist
		$this->load->model('plan_model');
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'),array());

		$options = array();
		if ($plans) {
			// build $options
			$options['-1'] = 'No plans.';
			$options['0'] = 'All plans.';
			while (list(,$plan) = each($plans)) {
				$options[$plan['id']] = $plan['name'];
			}

			$columns[] = array(
							'name' => 'Plan Link',
							'type' => 'select',
							'options' => $options,
							'filter' => 'plan_id',
							'width' => '14%'
							);
		}
		else {
			$columns[] = array(
				'name' => 'Plan Link',
				'width' => '14%'
				);
		}

		$columns[] = array(
						'name' => '',
						'width' => '6%'
				);

		$this->dataset->Initialize('email_model','GetEmails',$columns);

		// add actions
		$this->dataset->Action('Delete','settings/delete_emails');

		// sidebar
		$this->navigation->SidebarButton('New Email','settings/new_email');

		$this->load->view(branded_view('cp/emails.php'), array('plans' => $options));
	}

	/**
	* Delete Emails
	*
	* Delete emails as passed from the dataset
	*
	* @param string Hex'd, base64_encoded, serialized array of email ID's
	* @param string Return URL for Dataset
	*
	* @return bool Redirects to dataset
	*/
	function delete_emails ($emails, $return_url) {
		$this->load->model('email_model');
		$this->load->library('asciihex');

		$emails = unserialize(base64_decode($this->asciihex->HexToAscii($emails)));
		$return_url = base64_decode($this->asciihex->HexToAscii($return_url));

		foreach ($emails as $email) {
			$this->email_model->DeleteEmail($this->user->Get('client_id'),$email);
		}

		$this->notices->SetNotice($this->lang->line('emails_deleted'));

		redirect($return_url);
		return true;
	}

	/**
	* New Email
	*
	* Create a new email
	*
	* @return true Passes to view
	*/
	function new_email ()
	{
		$this->navigation->PageTitle('New Email');

		$this->load->model('email_model');
		$this->load->model('plan_model');

		$triggers = $this->email_model->GetTriggers();
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'));

		$data = array(
					'triggers' => $triggers,
					'plans' => $plans,
					'form_title' => 'Create New Email',
					'form_action' => 'settings/post_email/new'
					);

		$this->load->view(branded_view('cp/email_form.php'),$data);
	}

	/**
	* Handle New/Edit Email Post
	*/
	function post_email ($action, $id = false) {

		if ($this->input->post('email_body') == '') {
			$this->notices->SetError('Email Body is a required field.');
			$error = true;
		}
		elseif ($this->input->post('email_subject') == '') {
			$this->notices->SetError('Email Subject is a required field.');
			$error = true;
		}
		elseif ($this->input->post('from_name') == '') {
			$this->notices->SetError('From Name is a required field.');
			$error = true;
		}
		elseif ($this->input->post('from_email') == '') {
			$this->notices->SetError('From Email is a required field.');
			$error = true;
		}

		if (isset($error)) {
			if ($action == 'new') {
				redirect('settings/new_email');
				return false;
			}
			else {
				redirect('settings/edit_email/' . $id);
			}
		}

		$params = array(
						'email_subject' => $this->input->post('email_subject',true),
						'email_body' => $this->input->post('email_body',false),
						'from_name' => $this->input->post('from_name',true),
						'from_email' => $this->input->post('from_email',true),
						'plan' => $this->input->post('plan',true),
						'is_html' => $this->input->post('is_html',true),
						'to_address' => ($this->input->post('to_address') == 'email') ? $this->input->post('to_address_email') : 'customer',
						'bcc_address' => ($this->input->post('bcc_address') == 'client' or $this->input->post('bcc_address') == '') ? $this->input->post('bcc_address',true) : $this->input->post('bcc_address_email')
					);

		if ($params['bcc_address'] == 'email@example.com') {
			$params['bcc_address'] = '';
		}

		$this->load->model('email_model');

		if ($action == 'new') {
			$email_id = $this->email_model->SaveEmail($this->user->Get('client_id'),$this->input->post('trigger',TRUE), $params);
			$this->notices->SetNotice($this->lang->line('email_added'));
		}
		else {
			$this->email_model->UpdateEmail($this->user->Get('client_id'),$id, $params, $this->input->post('trigger',TRUE));
			$this->notices->SetNotice($this->lang->line('email_updated'));
		}

		redirect('settings/emails');

		return true;
	}

	/**
	* Edit Email
	*
	* Show the email form, preloaded with variables
	*
	* @param int $id the ID of the email
	*
	* @return string The email form view
	*/
	function edit_email($id) {
		$this->navigation->PageTitle('Edit Email');

		$this->load->model('email_model');
		$this->load->model('plan_model');

		$triggers = $this->email_model->GetTriggers();
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'));

		// preload form variables
		$email = $this->email_model->GetEmail($this->user->Get('client_id'),$id);

		$data = array(
					'triggers' => $triggers,
					'plans' => $plans,
					'form' => $email,
					'form_title' => 'Edit Email',
					'form_action' => 'settings/post_email/edit/' . $email['id']
					);

		$this->load->view(branded_view('cp/email_form.php'),$data);
	}

	/**
	* Show Available Variables
	*
	* Show the available variables for a trigger
	*
	* @param int $trigger_id The ID of the trigger
	*
	* @return string An unordered HTML list of available variables
	*/
	function show_variables ($trigger_id) {
		$this->load->model('email_model');

		$variables = $this->email_model->GetEmailVariables($trigger_id);

		$return = '<p><b>Available Variables for this Trigger Type</b></p>
				   <ul class="notes">
				   		<li>Not all values are available for each event.  For example, <span class="var">[[CUSTOMER_ADDRESS_1]]</span> cannot be replaced if the customer
				  	    does not have an address registered in the system.</li>
				   		<li>Usage Example: <span class="var">[[AMOUNT]]</span> will be replaced by a value like "34.95" in the email.</li>
				   		<li>To format dates, you can include a parameter in the variable such as, <span class="var">[[DATE|"M d, Y"]]</span> (output example: Aug 19, 2010).  You can
				   		specify any date format using either of PHP\'s <a href="http://www.php.net/date">date()</a> and <a href="http://www.php.net/strftime">strftime()</a>
				   		formatting styles.</li>
				   </ul>
				   <ul class="variables">';
		foreach ($variables as $variable) {
			$return .= '<li>[[' . strtoupper($variable) . ']]</li>';
		}

		$return .= '</ul><div style="clear:both"></div>';

		echo $return;
		return true;
	}

	/**
	* Manage gateways
	*
	* Lists active gateways for managing
	*/
	function gateways()
	{
		$this->navigation->PageTitle('Manage Gateways');

		$this->load->model('cp/dataset','dataset');

		$columns = array(
						array(
							'name' => 'ID #',
							'sort_column' => 'id',
							'type' => 'id',
							'width' => '5%',
							'filter' => 'id'),
						array(
							'name' => 'Gateway',
							'type' => 'text',
							'width' => '40%'),
						array(
							'name' => 'Date Created',
							'width' => '25%',
							'type' => 'date'),
						array(
							'name' => '',
							'width' => '25%'
							)
					);

		$this->dataset->Initialize('gateway_model','GetGateways',$columns);

		// add actions
		$this->dataset->Action('Delete','settings/delete_gateways');

		// sidebar
		$this->navigation->SidebarButton('Setup New Gateway','settings/new_gateway');

		$this->load->view(branded_view('cp/gateways.php'));
	}

	/**
	* Delete Gateways
	*
	* Delete gateways as passed from the dataset
	*
	* @param string Hex'd, base64_encoded, serialized array of gateway ID's
	* @param string Return URL for Dataset
	*
	* @return bool Redirects to dataset
	*/
	function delete_gateways ($gateways, $return_url) {
		$this->load->model('gateway_model');
		$this->load->library('asciihex');

		$gateways = unserialize(base64_decode($this->asciihex->HexToAscii($gateways)));
		$return_url = base64_decode($this->asciihex->HexToAscii($return_url));

		foreach ($gateways as $gateway) {
			$this->gateway_model->DeleteGateway($this->user->Get('client_id'),$gateway);
		}

		$this->notices->SetNotice($this->lang->line('gateways_deleted'));

		redirect($return_url);
		return true;
	}

	/**
	* New Gateway
	*
	* Create a new gateway
	*
	* @return true Passes to view
	*/
	function new_gateway ()
	{
		$this->navigation->PageTitle('New Gateway');

		$this->load->model('gateway_model');
		$gateways = $this->gateway_model->GetExternalAPIs();

		$data = array(
					'gateways' => $gateways
					);

		$this->load->view(branded_view('cp/new_gateway_type.php'),$data);
	}

	/**
	* New Gateway Step 2
	*
	* Create a new gateway
	*
	* @return true Passes to view
	*/
	function new_gateway_details ()
	{
		if ($this->input->post('external_api') == '') {
			redirect('settings/new_gateway');
			return false;
		}
		else {
			$this->load->library('payment/' . $this->input->post('external_api'), $this->input->post('external_api'));
			$class = $this->input->post('external_api');
			$settings = $this->$class->Settings();
		}
		$this->navigation->PageTitle($settings['name'] . ': Details');

		$data = array(
					'form_title' => $settings['name'] . ': Details',
					'form_action' => site_url('settings/post_gateway/new'),
					'external_api' => $this->input->post('external_api'),
					'name' => $settings['name'],
					'fields' => $settings['field_details']
					);

		$this->load->view(branded_view('cp/gateway_details.php'),$data);
	}

	/**
	* Handle New/Edit Gateway Post
	*/
	function post_gateway ($action, $id = false) {
		if ($this->input->post('external_api') == '') {
			$this->notices->SetError('No external API ID in form posting.');
			$error = true;
		}
		else {
			$this->load->library('payment/' . $this->input->post('external_api'), $this->input->post('external_api'));
			$class = $this->input->post('external_api');
			$settings = $this->$class->Settings();
		}

		$gateway = array();

		foreach ($settings['field_details'] as $name => $details) {
			$gateway[$name] = $this->input->post($name);

			if ($this->input->post($name) == '') {
				$this->notices->SetError('Required field missing: ' . $details['text']);
				$error = true;
			}
		}
		reset($settings['field_details']);

		if (isset($error)) {
			if ($action == 'new') {
				redirect('settings/new_gateway');
				return false;
			}
			else {
				redirect('settings/edit_gateway/' . $id);
			}
		}

		$params = array(
						'gateway_type' => $this->input->post('external_api'),
						'alias' => $this->input->post('alias')
					);

		foreach ($settings['field_details'] as $name => $details) {
			$params[$name] = $this->input->post($name);
		}

		$this->load->model('gateway_model');

		if ($action == 'new') {
			$gateway_id = $this->gateway_model->NewGateway($this->user->Get('client_id'), $params);

			$gateway = $this->gateway_model->GetGatewayDetails($this->user->Get('client_id'), $gateway_id);

			// test gateway
			$test = $this->$class->TestConnection($this->user->Get('client_id'),$gateway);

			if (!$test) {
				$this->gateway_model->DeleteGateway($this->user->Get('client_id'),$gateway_id,TRUE);

				$this->notices->SetError('Unable to establish a test connection.  Your details may be incorrect.');

				if ($action == 'new') {
					redirect('settings/new_gateway');
					return false;
				}
				else {
					redirect('settings/edit_gateway/' . $id);
				}
			}

			$this->notices->SetNotice($this->lang->line('gateway_added'));
		}
		else {
			$params['gateway_id'] = $id;

			$this->gateway_model->UpdateGateway($this->user->Get('client_id'), $params);
			$this->notices->SetNotice($this->lang->line('gateway_updated'));
		}

		redirect('settings/gateways');

		return true;
	}

	/**
	* Edit Gateway
	*
	* Show the gateway form, preloaded with variables
	*
	* @param int $id the ID of the gateway
	*
	* @return string The email form view
	*/
	function edit_gateway($id) {
		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($this->user->Get('client_id'), $id);

		$this->load->library('payment/' . $gateway['name'], $gateway['name']);
		$settings = $this->$gateway['name']->Settings();

		$this->navigation->PageTitle($settings['name'] . ': Details');

		$data = array(
					'form_title' => $settings['name'] . ': Details',
					'form_action' => site_url('settings/post_gateway/edit/' . $id),
					'external_api' => $gateway['name'],
					'name' => $gateway['alias'],
					'fields' => $settings['field_details'],
					'values' => $gateway
					);

		$this->load->view(branded_view('cp/gateway_details.php'),$data);
	}

	/**
	* Make Default Gateway
	*/
	function make_default_gateway ($id) {
		$this->load->model('gateway_model');
		$this->gateway_model->MakeDefaultGateway($this->user->Get('client_id'),$id);

		$this->notices->SetNotice($this->lang->line('default_gateway_changed'));

		redirect(site_url('settings/gateways'));
	}

	/**
	* API Access
	*
	* Display the current API login ID and Secret Key.
	*
	*/
	function api () {
		$data = array(
					'api_id' => $this->user->Get('api_id'),
					'secret_key' => $this->user->Get('secret_key')
					);

		$this->load->view(branded_view('cp/api_key.php'),$data);
	}

	/**
	* Generate new access info
	*
	*/
	function regenerate_api () {
		$this->load->model('client_model');
		$this->client_model->GenerateNewAccessKeys($this->user->Get('client_id'));

		redirect('settings/api');
		return true;
	}

	//--------------------------------------------------------------------

	/**
	 * Checks to see if the cronjob has been run and provides
	 * advice for setting it up.
	 */
	function cronjob()
	{
		$data = array();

		$data['cron_key'] = $this->config->item('cron_key');
		$data['cp_link'] = base_url();

		// Cron dates
		$query = $this->db->get('version');

		if ($query->num_rows()) {
			$data['dates'] = $query->result();
			$data['dates'] = $data['dates'][0];
		}

		$this->navigation->SidebarButton('Run Manually','cron/runall/'. $data['cron_key']);
		$this->load->view('cp/cronjobs', $data);
	}

	//--------------------------------------------------------------------

}
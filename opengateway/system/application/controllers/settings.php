<?php
/**
* Settings Controller 
*
* Manage emails, gateway, API key
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Settings extends Controller {

	function Settings()
	{
		parent::Controller();
		
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
							'width' => '10%',
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
							'width' => '15%')
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
							'name' => 'Plan Link',
							'type' => 'select',
							'options' => $options,
							'filter' => 'emails.plan_id',
							'width' => '20%'
							);
		}
		else {
			$columns[] = array(
				'name' => 'Plan Link',
				'width' => '20%'
				);
		}
		
		$this->dataset->Initialize('email_model','GetEmails',$columns);
		
		// add actions
		$this->dataset->Action('Delete','settings/delete_emails');
		
		// sidebar
		$this->navigation->SidebarButton('New Email','settings/new_email');
		
		$this->load->view('cp/emails.php', array('plans' => $options));
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
	function delete ($emails, $return_url) {
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
}
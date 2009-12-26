<?php
/**
* Plans Controller 
*
* Manage plans, create new plans, edit plans
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Plans extends Controller {

	function Plans()
	{
		parent::Controller();
		
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
							'width' => '15%',
							'filter' => 'first_name'),
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
							'width' => '15%',
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
							'width' => '25%')
					);
		
		$this->dataset->Initialize('plan_model','GetPlans',$columns);
		
		// add actions
		$this->dataset->Action('Delete','plans/delete');
		
		$this->load->view('cp/plans.php');
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
		
		redirect($return_url);
		return true;
	}
}
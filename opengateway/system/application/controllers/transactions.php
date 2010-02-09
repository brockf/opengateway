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
							'width' => '30%',
							'filter' => 'customer_last_name'),
						array(
							'name' => 'Credit Card',
							'sort_column' => 'card_last_four',
							'type' => 'text',
							'width' => '30%',
							'filter' => 'card_last_four'),
					);
		
		$this->dataset->Initialize('order_model','GetCharges',$columns);
		
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
		
		$data = array(
					'states' => $states,
					'countries' => $countries
					);
					
		$this->load->view('cp/new_transaction.php', $data);
		return true;
	}
}
<?php
/**
* Dashboard Controller 
*
* Login to the dashboard, get an overview of the account
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Dashboard extends Controller {

	function Dashboard()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();
	}
	
	function index()
	{		
		$this->load->model('order_model');
		
		die($this->order_model->GetRevenueByDay($this->user->Get('client_id')));
		
		$this->load->library('pchart/pData.class');
		$this->load->library('pchart/pChart.class');      
		  
		// Dataset definition   
		$DataSet = new pData;  
		$DataSet->AddPoint(array(1,4,3,2,3,3,2,1,0,7,4,3,2,3,3,5,1,0,7));  
		$DataSet->AddSerie();  
		$DataSet->SetSerieName("Revenue","Serie1");  
		  
		// Initialise the graph  
		$Test = new pChart(700,230);  
		$Test->setFontProperties("Fonts/tahoma.ttf",10);  
		$Test->setGraphArea(40,30,680,200);  
		$Test->drawGraphArea(252,252,252);  
		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2);  
		$Test->drawGrid(4,TRUE,230,230,230,255);  
		  
		// Draw the line graph  
		$Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());  
		$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);  
		  
		// Finish the graph  
		$Test->setFontProperties("Fonts/tahoma.ttf",8);  
		$Test->drawLegend(45,35,$DataSet->GetDataDescription(),255,255,255);  
		$Test->setFontProperties("Fonts/tahoma.ttf",10);  
		$Test->drawTitle(60,22,"My pretty graph",50,50,50,585);  
		$Test->Render("Naked.png");  
		
		$this->load->view('cp/dashboard');
	}

	/**
	* Show login screen
	*/
	function login() {
		$this->load->view('cp/login');
	}
	
	/**
	* Do Login
	*
	* Take a login post and process it
	* 
	* @return bool After redirect to dashboard or login screen, returns TRUE or FALSE
	*/
	function do_login() {
		if ($this->user->Login($this->input->post('username'),$this->input->post('password'))) {
			$this->notices->SetNotice($this->lang->line('notice_login_ok'));
			redirect('/dashboard');
			return true;
		}
		else {
			$this->notices->SetError($this->lang->line('error_login_incorrect'));
			redirect('/dashboard/login');
			return false;
		}
	}
}
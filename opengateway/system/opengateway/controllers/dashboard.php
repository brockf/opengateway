<?php
/**
* Dashboard Controller
*
* Login to the dashboard, get an overview of the account
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Dashboard extends Controller {

	function __construct()
	{
		parent::__construct();

		// perform control-panel specific loads
		CPLoader();
	}

	function index()
	{
		$this->load->model('charge_model');

		$revenue = $this->charge_model->GetRevenueByDay($this->user->Get('client_id'));

		$data = array();

		if ($this->config->item('show_dashboard_chart') !== 'no' and !empty($revenue) and count($revenue) > 1) {
			$series = array();
			foreach ($revenue as $day) {
				$series[] = $day['revenue'];
				$series2[] = date("M j",strtotime($day['day']));
			}

			include(APPPATH . 'libraries/pchart/pData.class');
			include(APPPATH . 'libraries/pchart/pChart.class');

			// Dataset definition
			$DataSet = new pData;
			$DataSet->AddPoint($series, "Revenue");
			$DataSet->AddPoint($series2, "Serie2");
			$DataSet->AddAllSeries();
			$DataSet->SetAbsciseLabelSerie("Serie2");

			$DataSet->RemoveSerie("Serie2");

			$DataSet->SetXAxisName('Date');
			$DataSet->SetYAxisName('Revenue');
			//$DataSet->SetXAxisFormat('date');

			// Initialise the graph
			$Test = new pChart(1000,260);
			$Test->setFontProperties(APPPATH . 'libraries/pchart/Arial.ttf',10);
			$Test->setGraphArea(90,30,960,200);
			$Test->drawGraphArea(252,252,252);
			$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2);
			$Test->drawGrid(4,TRUE,230,230,230,255);

			// Draw the line graph
			$Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());
			$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);

			// Finish the graph
			$Test->setFontProperties(APPPATH . 'libraries/pchart/Arial.ttf',8);
			$Test->drawLegend(45,35,$DataSet->GetDataDescription(),255,255,255);
			$Test->setFontProperties(APPPATH . 'libraries/pchart/Arial.ttf',10);
			//$Test->drawTitle(60,22,"Last 30 Days",50,50,50,585);
			$Test->Render(BASEPATH . '../writeable/rev_chart_' . $this->user->Get('client_id') . '.png');
		}
		else {
			$data['no_chart'] = 'true';
		}

		// get log
		$this->load->model('log_model');
		$log = $this->log_model->GetClientLog($this->user->Get('client_id'));

		$data['log'] = $log;

		$this->load->view(branded_view('cp/dashboard'), $data);
	}

	/**
	* Show login screen
	*/
	function login() {
		$this->load->view(branded_view('cp/login'));
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
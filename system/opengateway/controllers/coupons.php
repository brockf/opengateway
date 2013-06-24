<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Coupons extends Controller {

	public function __construct()
	{
		parent::__construct();

		// perform control-panel specific loads
		CPLoader();

		$this->load->model('coupon_model');
	}

	//--------------------------------------------------------------------

	public function index()
	{
		$this->navigation->PageTitle('Coupons');

		$this->load->model('cp/dataset','dataset');

		// Get coupon types
		$coupon_types = $this->coupon_model->get_coupon_types();
		foreach ($coupon_types as $type)
		{
			$coupon_options[$type->coupon_type_id] = $type->coupon_type_name;
		}

		$columns = array(
						array(
							'name' => 'ID #',
							'sort_column' => 'coupon_id',
							'type' => 'id',
							'width' => '5%',
							'filter' => 'coupon_id'),
						array(
							'name' => 'Coupon Name',
							'sort_column' => 'coupon_name',
							'type' => 'text',
							'width' => '20%',
							'filter' => 'coupon_name'),
						array(
							'name' => 'Code',
							'type'	=> 'text',
							'width' => '15%',
							'filter'	=> 'coupon_code'
							),
						array(
							'name' => 'Active Dates',
							'type'	=> 'date',
							'sort_column' => 'column_start_date',
							'width' => '15%',
							'filter' => 'timestamp',
							'field_start_date' => 'coupon_start_date',
							'field_end_date' => 'coupon_end_date'
							),
						array(
							'name'	=> 'Coupon Type',
							'type'	=> 'select',
							'options' => $coupon_options,
							'width' => '15%',
							'filter'	=> 'coupon_type'
						)
					);

		// set total rows by hand to reduce database load
		$result = $this->db->select('COUNT(coupon_id) AS total_rows',FALSE)
						   ->from('coupons')
						   ->where('coupon_deleted','0')
						   ->where('client_id', $this->user->Get('client_id'))
						   ->get();
		$this->dataset->total_rows((int)$result->row()->total_rows);

		// initialize the dataset
		$this->dataset->Initialize('coupon_model','get_coupons', $columns);

		// add actions
		$this->dataset->Action('Delete','coupons/delete_coupons');

		$this->navigation->SidebarButton('New Coupon','coupons/add');

		$this->load->view(branded_view('cp/coupons.php'), array('coupon_options'=>$coupon_options));
	}

	//--------------------------------------------------------------------

	public function add()
	{
		// Get our plans
		$this->load->model('plan_model');
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'), array('plan_type' => 'paid'));

		// Grab our coupon types
		$coupon_types = $this->coupon_model->get_coupon_types();

		// Prep our page
		$data = array(
			'coupon_types'	=> $coupon_types,
			'plans'			=> $plans,
			'form_title'	=> 'Create New Coupon',
			'action'		=> 'new',
			'form_action'	=> site_url('/coupons/post_coupon/new')
		);

		$this->load->view(branded_view('cp/coupon_form'), $data);
	}

	//--------------------------------------------------------------------

	public function edit ($id = 0)
	{
		// Grab our coupon data
		$coupon = $this->coupon_model->get_coupon($this->user->Get('client_id'), $id);

		// Get our plans
		$this->load->model('plan_model');
		$plans = $this->plan_model->GetPlans($this->user->Get('client_id'), array('plan_type' => 'paid'));

		// Grab our coupon types
		$coupon_types = $this->coupon_model->get_coupon_types();

		// Prep our page
		$data = array(
			'coupon_types'	=> $coupon_types,
			'plans'			=> $plans,
			'coupon'		=> $coupon,
			'form_title'	=> 'Edit Coupon',
			'action'		=> 'edit',
			'form_action'	=> site_url('/coupons/post_coupon/edit/'.$id)
		);

		$this->load->view('cp/coupon_form', $data);
	}

	//--------------------------------------------------------------------


	public function post_coupon ($action = 'edit', $id = FALSE)
	{
		$editing = $action == 'edit' ? TRUE : FALSE;

		$validated = $this->coupon_model->validation($editing);
		if ($validated !== TRUE) {
			$this->notices->SetError(implode('<br />',$validated));
			$error = TRUE;
		}

		if (isset($error)) {
			if ($action == 'new') {
				redirect('coupons/add');
				return FALSE;
			}
			else {
				redirect('coupons/edit/' . $id);
				return FALSE;
			}
		}

		if ($action == 'new')
		{
			// New coupon
			$coupon_id = $this->coupon_model->new_coupon(
													$this->user->Get('client_id'),
													$this->input->post('coupon_name'),
													$this->input->post('coupon_code'),
													$this->input->post('coupon_start_date'),
													($this->input->post('no_expiry') == '1') ? FALSE : $this->input->post('coupon_end_date'),
													($this->input->post('no_limit') == '1') ? FALSE : $this->input->post('coupon_max_uses'),
													($this->input->post('coupon_customer_limit') == '') ? FALSE : $this->input->post('coupon_customer_limit'),
													$this->input->post('coupon_type_id'),
													$this->input->post('coupon_reduction_type'),
													($this->input->post('coupon_reduction_amt') == '') ? FALSE : $this->input->post('coupon_reduction_amt'),
													($this->input->post('coupon_trial_length') == '') ? FALSE : $this->input->post('coupon_trial_length'),
													(in_array(0,$this->input->post('plans'))) ? FALSE : $this->input->post('plans')
												);

			if ($coupon_id) {
				$this->notices->SetNotice('Coupon added successfully.');
			} else  {
				$this->notices->SetError('Unable to create coupon.');
			}
		} else  {
			// Edited coupon
			$coupon_id = $id;
			$result = $this->coupon_model->update_coupon(
												$this->user->Get('client_id'),
												$coupon_id,
												$this->input->post('coupon_name'),
												$this->input->post('coupon_code'),
												$this->input->post('coupon_start_date'),
												($this->input->post('no_expiry') == '1') ? FALSE : $this->input->post('coupon_end_date'),
												($this->input->post('no_limit') == '1') ? FALSE : $this->input->post('coupon_max_uses'),
												($this->input->post('coupon_customer_limit') == '') ? FALSE : $this->input->post('coupon_customer_limit'),
												$this->input->post('coupon_type_id'),
												$this->input->post('coupon_reduction_type'),
												($this->input->post('coupon_reduction_amt') == '') ? FALSE : $this->input->post('coupon_reduction_amt'),
												($this->input->post('coupon_trial_length') == '') ? FALSE : $this->input->post('coupon_trial_length'),
												(in_array(0,$this->input->post('plans'))) ? FALSE : $this->input->post('plans')
											);

			if ($result) {
				$this->notices->SetNotice('Coupon saved successfully.');
			} else  {
				$this->notices->SetError('Unable to save coupon.');
			}
		}

		redirect('coupons');

		return TRUE;
	}

	//--------------------------------------------------------------------

	function delete_coupons ($coupons, $return_url) {
		$this->load->library('asciihex');

		$coupons = unserialize(base64_decode($this->asciihex->HexToAscii($coupons)));
		$return_url = base64_decode($this->asciihex->HexToAscii($return_url));

		foreach ($coupons as $coupon) {
			$this->coupon_model->delete_coupon($this->user->Get('client_id'), $coupon);
		}

		$this->notices->SetNotice('Coupon(s) deleted successfully.');

		redirect($return_url);

		return TRUE;
	}
}

/* End of file Coupon.php */
/* Location: ./application/controllers/Coupon.php */
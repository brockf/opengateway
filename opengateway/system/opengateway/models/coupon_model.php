<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Coupon_model extends Model {
	
	function __construct()
	{
		parent::__construct();
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Get a single coupon.
	 *
	 * @param	int		$client_id	The id of the client to find a coupon for.
	 * @param	int		$id			The coupon_id to find.
	 *
	 * @return	array	The coupon details, or FALSE on coupon not found.
	 */
	public function get_coupon($client_id, $id) 
	{
		$this->db->where('coupon_deleted', 0);
		$this->db->where('coupon_id', $id);
		$this->db->where('client_id', $client_id);
		$query = $this->db->get('coupons');
		
		if ($query->num_rows())
		{
			$coupon = $query->row_array();
			
			// Get associated plans
			$coupon['plans'] = $this->get_related($id, 'coupons_plans', 'plan_id');
					
			return $coupon;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	
	/**
	 * Get a list of coupons
	 *
	 * @param	int		$client_id	The id of the client to find coupons for.
	 * @param	array	$filters	The filters to apply to the selection.
	 *
	 * @return	array	The coupons that match the filters.
	 */
	public function get_coupons($client_id=null, $filters=array()) 
	{
	
		//--------------------------------------------------------------------
		// setup filters
		//--------------------------------------------------------------------
		
		// ID	
		if (isset($filters['id']))
		{
			$this->db->where('coupon_id',$filters['id']);
		}
		
		// Name
		if (isset($filters['coupon_name']))
		{
			$this->db->like('coupon_name', $filters['coupon_name']);
		}
		
		// Code
		if (isset($filters['coupon_code']) && !empty($filters['coupon_code']))
		{
			$this->db->like('coupon_code', $filters['coupon_code']);
		}
		
		// Start Date
		if (isset($filters['coupon_start_date']))
		{
			$this->db->where('coupon_start_date >=', $filters['coupon_start_date'] );
		}
		// End Date
		if (isset($filters['coupon_end_date']))
		{
			$this->db->where('coupon_end_date <=', $filters['coupon_end_date'] );
		}
		
		// Reduction Type
		if (isset($filters['coupon_type']))
		{
			$this->db->where('coupon_type_id', $filters['coupon_type'] );
		}
		
		$this->db->where('coupon_deleted', 0);
		$this->db->where('coupons.client_id', $client_id);
		$query = $this->db->get('coupons');
		
		if ($query->num_rows())
		{
			return $query->result_array();
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Returns an object containing all of the coupon types in the system.
	 *
	 * @return	object	The coupon types in system, or FALSE if none exist.
	 */
	public function get_coupon_types() 
	{
		$query = $this->db->get('coupon_types');
		
		if ($query->num_rows() > 0)
		{
			return $query->result();
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Validates POST data to be appropriate for a coupon.
	 *
	 * @param	bool	$editing	Whether in editing/new mode.
	 *
	 * @return	bool	Whether it was successful or not.
	 */
	public function validation($editing=TRUE) 
	{
		$this->load->library('form_validation');
		
		$this->form_validation->set_rules('coupon_name', 'Coupon Name', 'trim|required|max_length[60]');
		$this->form_validation->set_rules('coupon_code', 'Coupon Code', 'trim|required|mx_length[20]');
		$this->form_validation->set_rules('coupon_start_date', 'Start Date', 'trim|required');
		$this->form_validation->set_rules('coupon_end_date', 'End Date', 'trim|required');
		$this->form_validation->set_rules('coupon_max_uses', 'Maximum Uses', 'trim|is_natural');
		$this->form_validation->set_rules('coupon_type_id', 'Coupon Type', 'trim|is_natural');
		
		switch ($this->input->post('coupon_type_id'))
		{
			// Price Reduction
			case 1:
			case 2:
			case 3: 
				$this->form_validation->set_rules('coupon_reduction_amt', 'Reduction Amount', 'trim|required|numeric');
				break;
			// Free Trial
			case 4: 
				$this->form_validation->set_rules('coupon_trial_length', 'Free Trial Length', 'trim|required|is_natural');
				break;			
		}
		
		
		if ($this->form_validation->run() == FALSE) {
			$errors = rtrim(validation_errors('','||'),'|');
			$errors = explode('||',str_replace('<p>','',$errors));
			return $errors;
		}
		
		return TRUE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Creates a new coupon in the database.
	 *
	 * @param	int		$client_id	The id of the client that owns the coupon.
	 * @param	array	$fields		The coupon data to be saved.
	 *
	 * @return	int		The id of the new coupon, or FALSE on failure.
	 */
	public function new_coupon($client_id=null, $fields=array()) 
	{
		if (!is_array($fields) || !count($fields))
		{
			return FALSE;
		}
			
		// Grab arrays of data to be used after the initial creation
		$products	= isset($fields['products']) ? $fields['products'] : null;
		$plans 		= isset($fields['plans']) ? $fields['plans'] : null;
		$trial_subs = isset($fields['trial_subs']) ? $fields['trial_subs'] : null;
		$ship_rates = isset($fields['ship_rates']) ? $fields['ship_rates'] : null;
		
		// Now unset these so they're not clogging up the system
		unset($fields['products'], $fields['plans'], $fields['trial_subs'], $fields['ship_rates']);
		
		// Add the created_on field
		$fields['created_on'] = date('Y-m-d H:i:s');
		$fields['client_id'] = $client_id;
		
		// Now, time to try saving the coupon itself
		$this->db->insert('coupons', $fields);
		
		$id = $this->db->insert_id();
		
		if (is_numeric($id))
		{
			// Save was successfull, so try to save our various associated parts.
			if (!empty($products))		{ $this->save_related($id, 'coupons_products', 'product_id', $products); }
			if (!empty($plans))			{ $this->save_related($id, 'coupons_plans', 'plan_id', $plans); }
			if (!empty($trial_subs))	{ $this->save_related($id, 'coupons_plans', 'plan_id', $trial_subs); }
			if (!empty($ship_rates))	{ $this->save_related($id, 'coupons_shipping', 'shipping_id', $ship_rates); }
			
			return $id;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Saves an edited coupon.
	 *
	 * @param	int		$client_id	The id of the client that owns the coupon.
	 * @param	int		$id			The coupon_id to save the data to.
	 * @param	array	$fields		The coupon data to be saved.
	 *
	 * @return	bool	Whether the coupon is succesfully saved or not.
	 */
	public function edit_coupon($client_id, $id, $fields=array()) 
	{
		if (!is_array($fields) || !count($fields))
		{
			return FALSE;
		}
		
		// Grab arrays of data to be used after the initial creation
		$plans = isset($fields['plans']) ? $fields['plans'] : null;
		$trial_subs = isset($fields['trial_subs']) ? $fields['trial_subs'] : null;
	
		// Now unset these so they're not clogging up the system
		unset($fields['plans'], $fields['trial_subs']);
		
		// Add the created_on field
		$fields['modified_on'] = date('Y-m-d H:i:s');
		
		// Now, time to try saving the coupon itself
		$this->db->where('client_id', $client_id);
		$this->db->where('coupon_id', $id);
		$this->db->update('coupons', $fields);
		
		if ($this->db->affected_rows())
		{
			// Save was successfull, so try to save our various associated parts.
			if (!empty($plans) && count($plans))	{ $this->save_related($id, 'coupons_plans', 'plan_id', $plans); }
			if (!empty($trial_subs) && count($trial_subs))	{ $this->save_related($id, 'coupons_plans', 'plan_id', $plans); }
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Sets the deleted flag on a coupon (basically, saves it for the 'recycle bin')
	 *
	 * @param	int		$client_id	The id of the client that owns the coupon.
	 * @param	int		$id			The id of the coupon to delete.
	 *
	 * @return	bool	Whether the coupon is successfully 'deleted' or not.
	 */
	public function delete_coupon($client_id=null, $id=null) 
	{
		if (!is_numeric($id))
		{
			return FALSE;
		}
		
		$this->db->set('coupon_deleted', 1);
		$this->db->where('client_id', $client_id);
		$this->db->where('coupon_id', $id);
		return $this->db->update('coupons');
	}
	
	//--------------------------------------------------------------------
	
	
	/**
	 * Saves related items into their pivot tables (like products, plans, etc)
	 * that a coupon would be associated to.
	 *
	 * @param	int		$coupon_id	The id of the coupon this data is related to.
	 * @param	string	$table		The name of the database table to save to.
	 * @param	field	$field		The name of the database table field to save to.
	 * @param	array	$items		The items to save.
	 *
	 * @return	void
	 */
	public function save_related($coupon_id=null, $table='', $field='', $items=array()) 
	{	
		// First, delete any existing entries for this coupon, just in case it's an edit
		$this->db->where('coupon_id', $coupon_id);
		$this->db->delete($table);
	
		// Now save the new values
		foreach ($items as $item)
		{ 
			$this->db->set(array('coupon_id' => $coupon_id, $field => $item));
			$this->db->insert($table);
		}
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Retrieves related records (in other db tables) to the specified coupon.
	 *
	 * @param	id		$coupon_id	The id of the coupon this data is related to.
	 * @param	string	$table		The name of the database table to pull from.
	 * @param	string	$field		The name of the database table field to retrieve.
	 *
	 * @return	array	The related object, or FALSE on failure.
	 */
	public function get_related($coupon_id=null, $table='', $field='') 
	{
		$this->db->where('coupon_id', $coupon_id);
		$query = $this->db->get($table);
		
		if ($query->num_rows())
		{
			$items = $query->result_array();
			
			foreach ($items as $item)
			{
				$collection[] = $item[$field];
			}
			
			return $collection;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
}

/* End of file coupon_model.php */
/* Location: ./application/modules/coupons/models/coupon_model.php */
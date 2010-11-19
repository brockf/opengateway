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
		$result = $this->db->get('coupons');
		
		if ($result->num_rows() === 0)
		{
			return FALSE;
		}

		$coupons = array();
		foreach ($result->result_array() as $row)
		{ 
			$coupons[] = array(
				'id'				=> $row['coupon_id'],
				'type_id'			=> $row['coupon_type_id'],
				'name'				=> $row['coupon_name'],
				'code'				=> $row['coupon_code'],
				'start_date'		=> $row['coupon_start_date'],
				'end_date'			=> $row['coupon_end_date'],
				'max_uses'			=> $row['coupon_max_uses'],
				'customer_limit'	=> $row['coupon_customer_limit'],
				'reduction_type'	=> $row['coupon_reduction_type'],
				'reduction_amt'		=> $row['coupon_reduction_amt'],
				'trial_length'		=> $row['coupon_trial_length'],
			);
		}

		return $coupons;
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
	 * @param	int			$client_id		The id of the client that has created the coupon.
	 * @param	string		$name			The coupon name
	 * @param	string		$code			The coupon code
	 * @param	string		$start_date		The first day that the coupon can be used (ie YYYY-MM-DD)
	 * @param	string		$end_date		The last day the coupon can be used
	 * @param	int			$max_uses		The maximum number of times the coupon can be used
	 * @param	bool		$customer_limit	Whether or not to restrict the customer to a single use
	 * @param	int			$type_id		The type of coupon (Price reduction, free trial or free subscription)
	 * @param	int			$reduction_type	Whether percent or fixed amount of reduction. Only applicable for Price Reductions.
	 * @param	string		$reduction_amt	How much to reduce price by. Only applicable for Price Reduction.
	 * @param	int			$trial_length	How many days the free trial will last. 
	 * @param	array		$products		An array of product ids to assign this coupon to.
	 * @param	array		$plans			An array of subscription plans to assign this coupon to.
	 * @param 	array		$ship_rates		An array of shipping rates to apply this coupon to.
	 *
	 * @return	int		The id of the new coupon, or FALSE on failure.
	 */
	public function new_coupon($client_id, $name, $code, $start_date, $end_date, $max_uses, $customer_limit, $type_id, $reduction_type, $reduction_amt, $trial_length, $products, $plans) 
	{
		$insert_fields = array(
							'client_id'				=> $client_id,
							'coupon_name'			=> $name,
							'coupon_code'			=> $code,
							'coupon_start_date'		=> $start_date,
							'coupon_end_date'		=> $end_date,
							'coupon_max_uses'		=> $max_uses,
							'coupon_customer_limit'	=> $customer_limit,
							'coupon_type_id'		=> $type_id,
							'coupon_reduction_type'	=> $reduction_type,
							'coupon_reduction_amt'	=> $reduction_amt,
							'coupon_trial_length'	=> $trial_length,
						);
		
		// Add the created_on field
		$insert_fields['created_on'] = date('Y-m-d H:i:s');
		
		// Now, time to try saving the coupon itself
		$this->db->insert('coupons', $insert_fields);
		
		$id = $this->db->insert_id();
				
		if (is_numeric($id))
		{
			// Save was successfull, so try to save our various associated parts.
			if (!empty($products))		{ $this->save_related($id, 'coupons_products', 'product_id', $products); }
			if (!empty($plans))			{ $this->save_related($id, 'coupons_plans', 'plan_id', $plans); }
			
			return $id;
		}
		
		return FALSE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Saves an updated coupon in the database.
	 *
	 * @param	int			$client_id		The id of the client that has created the coupon.
	 * @param	int			$coupon_id		The id of the coupon to save.
	 * @param	string		$name			The coupon name
	 * @param	string		$code			The coupon code
	 * @param	string		$start_date		The first day that the coupon can be used (ie YYYY-MM-DD)
	 * @param	string		$end_date		The last day the coupon can be used
	 * @param	int			$max_uses		The maximum number of times the coupon can be used
	 * @param	bool		$customer_limit	Whether or not to restrict the customer to a single use
	 * @param	int			$type_id		The type of coupon (Price reduction, free trial or free subscription)
	 * @param	int			$reduction_type	Whether percent or fixed amount of reduction. Only applicable for Price Reductions.
	 * @param	string		$reduction_amt	How much to reduce price by. Only applicable for Price Reduction.
	 * @param	int			$trial_length	How many days the free trial will last. 
	 * @param	array		$products		An array of product ids to assign this coupon to.
	 * @param	array		$plans			An array of subscription plans to assign this coupon to.
	 * @param 	array		$ship_rates		An array of shipping rates to apply this coupon to.
	 *
	 * @return	int		The id of the new coupon, or FALSE on failure.
	 */
	public function update_coupon($client_id, $coupon_id, $name, $code, $start_date, $end_date, $max_uses, $customer_limit, $type_id, $reduction_type, $reduction_amt, $trial_length, $products, $plans) 
	{
		$insert_fields = array(
							'client_id'				=> $client_id,
							'coupon_name'			=> $name,
							'coupon_code'			=> $code,
							'coupon_start_date'		=> $start_date,
							'coupon_end_date'		=> $end_date,
							'coupon_max_uses'		=> $max_uses,
							'coupon_customer_limit'	=> $customer_limit,
							'coupon_type_id'		=> $type_id,
							'coupon_reduction_type'	=> $reduction_type,
							'coupon_reduction_amt'	=> $reduction_amt,
							'coupon_trial_length'	=> $trial_length,
						);
		
		// Add the created_on field
		$insert_fields['created_on'] = date('Y-m-d H:i:s');
		
		// Now, time to try saving the coupon itself
		$this->db->where('client_id', $client_id);
		$this->db->where('coupon_id', $coupon_id);
		$this->db->update('coupons', $insert_fields);
				
		if ($this->db->affected_rows())
		{
			// Save was successfull, so try to save our various associated parts.
			if (!empty($products))		{ $this->save_related($coupon_id, 'coupons_products', 'product_id', $products); }
			if (!empty($plans))			{ $this->save_related($coupon_id, 'coupons_plans', 'plan_id', $plans); }
			
			return $id;
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
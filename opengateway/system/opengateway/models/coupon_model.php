<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// Define some vars to make this easier to
// understand the flow of...
define('CPN_CHARGE_PRICE_REDUCTION', 1);
define('CPN_RECUR_TOTAL_PRICE_REDUCTION', 2);
define('CPN_RECURRING_PRICE_REDUCTION', 3);
define('CPN_RECUR_INITIAL_PRICE_REDUCTION', 4);
define('CPN_RECUR_FREE_TRIAL', 5);


class Coupon_model extends Model {
	
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	* Add Usage
	*
	* Record a coupon usage
	*
	* @param int $coupon_id
	* @param int $subscription_id
	* @param int $charge_id
	* @param int $customer_id
	*
	* @return boolean
	*/
	function add_usage ($coupon_id, $subscription_id, $charge_id, $customer_id) {
		if (!empty($subscription_id)) {
			// it's a subscription
			$this->db->update('subscriptions',array('coupon_id' => $coupon_id),array('subscription_id' => $subscription_id, 'active' => '1'));
		}
		else {
			// it's a charge
			$this->db->update('orders',array('coupon_id' => $coupon_id),array('order_id' => $charge_id));
		}
		
		return TRUE;
	}
	
	/**
	* Is Eligible?
	*
	* @param array $coupon
	*/
	function is_eligible ($coupon, $plan_id, $customer_id, $single_charge = FALSE) {
		// coupon may be multi-dimensional array.
		// if so, we'll take key #0
		if (isset($coupon[0])) {
			$coupon = $coupon[0];
		}
		
		if ($single_charge == TRUE and $coupon['type_id'] != CPN_CHARGE_PRICE_REDUCTION) {
			// this coupon is for recurrings
			
			log_message('debug','Coupon ineligible: Coupon is for recurring charges.');
			
			return FALSE;
		}
	
		if ($coupon['end_date'] != FALSE and (strtotime($coupon['end_date'])+84600) < time()) {
			// expired
			
			log_message('debug','Coupon ineligible: Coupon expired on ' . date('Y-m-d', strtotime($coupon['end_date'])) . '.');
			
			return FALSE;
		}
		
		if (strtotime($coupon['start_date']) > time()) {
			// not yet started
			
			log_message('debug','Coupon ineligible: Coupon will not start until ' . date('Y-m-d', strtotime($coupon['start_date'])) . '.');
			
			return FALSE;
		}
		
		// linked plans
		$plans = $this->get_related($coupon['id'], 'coupons_plans', 'plan_id');
		
		if (!empty($plans) and $plan_id == FALSE) {
			// plan is required
			
			log_message('debug','Coupon ineligible: Coupon requires a plan and no plan was submitted.');
			
			return FALSE;
		}
		
		if (!empty($plans) and !in_array($plan_id, $plans)) {
			// not for this plan
			
			log_message('debug','Coupon ineligible: Coupon requires a plan and this recurring charge is not one of those plans.');
			
			return FALSE;
		}
		
		if ($coupon['max_uses'] != FALSE) {
			// count usage
			$result = $this->db->select('*')
							   ->where('coupon_id',$coupon['id'])
							   ->from('subscriptions')
							   ->get();
							   
			$result2 = $this->db->select('*')
							    ->where('coupon_id',$coupon['id'])
							    ->from('orders')
							    ->get();
							   
			$uses = $result->num_rows() + $result2->num_rows();
			
			if ($uses >= $coupon['max_uses']) {
				// too many uses
				
				log_message('debug','Coupon ineligible: Coupon has exceeded maximum uses.');
				
				return FALSE;
			}
		}
		
		if ($coupon['customer_limit'] != FALSE) {
			// count customer usage
			$result = $this->db->select('*')
							   ->where('coupon_id',$coupon['id'])
							   ->where('customer_id',$customer_id)
							   ->from('subscriptions')
							   ->get();
							   
			$result2 = $this->db->select('*')
							    ->where('coupon_id',$coupon['id'])
							    ->where('customer_id',$customer_id)
							    ->from('orders')
							    ->get();
							   
			$customer_uses = $result->num_rows() + $result2->num_rows();
			
			if ($customer_uses >= $coupon['customer_limit']) {
				// too many uses for this customer
				
				log_message('debug','Coupon ineligible: Coupon has exceeded maximum uses for this customer.');
				
				return FALSE;
			}
		}
		
		log_message('debug','Coupon ineligible: Coupon is OK.');
		
		return TRUE;
	}
	
	function subscription_adjust_trial ($free_trial, $type_id, $trial_length) {
		if ($type_id == CPN_RECUR_FREE_TRIAL) {
			$free_trial = $trial_length;
		}
		
		return $free_trial;
	}
	
	function subscription_adjust_amount(&$amount, &$recur_amount, $type_id, $reduction_type, $reduction_amount) {
		if ($type_id == CPN_CHARGE_PRICE_REDUCTION) {
			// this isn't for recurring charges
		}
	
		if ((int)$reduction_type === 0) {
			// percentage
			
			$multiplier = (100 - $reduction_amount) * 0.01;
			
			if ($type_id == CPN_RECUR_TOTAL_PRICE_REDUCTION or $type_id == CPN_RECUR_INITIAL_PRICE_REDUCTION) {
				// initial price
				$amount = $amount * $multiplier;
			}
			
			if ($type_id == CPN_RECUR_TOTAL_PRICE_REDUCTION or $type_id == CPN_RECURRING_PRICE_REDUCTION) {
				$recur_amount = $recur_amount * $multiplier;
			} 
		}
		elseif ((int)$reduction_type === 1) {
			// flat rate
			if ($type_id == CPN_RECUR_TOTAL_PRICE_REDUCTION or $type_id == CPN_RECUR_INITIAL_PRICE_REDUCTION) {
				// initial price
				$amount = $amount - $reduction_amount;
			}
			
			if ($type_id == CPN_RECUR_TOTAL_PRICE_REDUCTION or $type_id == CPN_RECURRING_PRICE_REDUCTION) {
				$recur_amount = $recur_amount - $reduction_amount;
			}
		}
		
		if ($amount < 0) {
			$amount = 0;
		}
		
		if ($recur_amount < 0) {
			$recur_amount = 0;
		}

		return TRUE;
	}
	
	function adjust_amount($amount, $type_id, $reduction_type, $reduction_amount) {
		if ($type_id != CPN_CHARGE_PRICE_REDUCTION) {
			// this isn't for single charges
			return $amount;
		}
	
		if ((int)$reduction_type === 0) {
			// percentage
			
			$multiplier = (100 - $reduction_amount) * 0.01;
			$amount = $amount * $multiplier;
		}
		elseif ((int)$reduction_type === 1) {
			// flat rate
			$amount = $amount - $reduction_amount;
		}
		
		if ($amount < 0) {
			$amount = 0;
		}
		
		return $amount;
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
		$coupon = $this->get_coupons($client_id, array('id' => $id));
		
		if (empty($coupon)) {
			return FALSE;
		}
		
		$coupon = $coupon[0];
		
		$coupon['plans'] = $this->get_related($id, 'coupons_plans', 'plan_id');
					
		return $coupon;
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
	public function get_coupons($client_id = null, $filters = array()) 
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
			// this is a WHERE search to prevent LIKE searching in an actual Charge/Request call
			$this->db->where('coupon_code', $filters['coupon_code']);
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
		
		// Client restriction
		if (!empty($client_id)) {
			$this->db->where('coupons.client_id', $client_id);
		}
		
		// limit
		if (isset($filters['offset'])) {
			$offset = $filters['offset'];
		}
		else {
			$offset = 0;
		}
		
		if(isset($filters['limit'])) {
			$this->db->limit($filters['limit'], $offset);
		}
		
		$this->db->where('coupon_deleted', 0);
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
				'end_date'			=> ($row['coupon_end_date'] != '0000-00-00') ? $row['coupon_end_date'] : FALSE,
				'max_uses'			=> ($row['coupon_max_uses'] != '0') ? $row['coupon_max_uses'] : FALSE,
				'customer_limit'	=> ($row['coupon_customer_limit'] != '0') ? $row['coupon_customer_limit'] : FALSE,
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
		$this->form_validation->set_rules('coupon_type_id', 'Coupon Type', 'trim|is_natural');
		
		if ($this->input->post('coupon_max_uses')) {
			$this->form_validation->set_rules('coupon_max_uses', 'Maximum Uses', 'trim|is_natural');
		}
		
		switch ($this->input->post('coupon_type_id'))
		{
			// Price Reduction
			case 1:
			case 2:
			case 3: 
			case 4:
				$this->form_validation->set_rules('coupon_reduction_amt', 'Reduction Amount', 'trim|required|numeric');
				break;
			// Free Trial
			case 5: 
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
	 * @param	array		$plans			An array of subscription plans to assign this coupon to.
	 *
	 * @return	int		The id of the new coupon, or FALSE on failure.
	 */
	public function new_coupon($client_id, $name, $code, $start_date, $end_date, $max_uses, $customer_limit, $type_id, $reduction_type, $reduction_amt, $trial_length, $plans = array()) 
	{
		$insert_fields = array(
							'client_id'				=> $client_id,
							'coupon_name'			=> $name,
							'coupon_code'			=> $code,
							'coupon_start_date'		=> $start_date,
							'coupon_end_date'		=> $end_date,
							'coupon_max_uses'		=> $max_uses,
							'coupon_customer_limit'	=> ($customer_limit == FALSE) ? 0 : $customer_limit,
							'coupon_type_id'		=> $type_id,
							'coupon_reduction_type'	=> ($reduction_type == FALSE) ? 0 : $reduction_type,
							'coupon_reduction_amt'	=> ($reduction_amt == FALSE) ? 0 : $reduction_amt,
							'coupon_trial_length'	=> ($trial_length == FALSE) ? 0 : $trial_length,
						);
		
		// Add the created_on field
		$insert_fields['created_on'] = date('Y-m-d H:i:s');
		
		// Now, time to try saving the coupon itself
		$this->db->insert('coupons', $insert_fields);
		
		$id = $this->db->insert_id();
				
		if (is_numeric($id))
		{
			// Save was successfull, so try to save our various associated parts.
			if (!empty($plans) && count($plans)) { $this->save_related($id, 'coupons_plans', 'plan_id', $plans); }
			
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
	 * @param	array		$plans			An array of subscription plans to assign this coupon to.
	 *
	 * @return boolean
	 */
	public function update_coupon($client_id, $coupon_id, $name, $code, $start_date, $end_date, $max_uses, $customer_limit, $type_id, $reduction_type, $reduction_amt, $trial_length, $plans) 
	{
		$insert_fields = array(
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
		
		// Now, time to try saving the coupon itself
		$this->db->where('client_id', $client_id);
		$this->db->where('coupon_id', $coupon_id);
		$this->db->update('coupons', $insert_fields);
				
		// Save was successfull, so try to save our various associated parts.
		if (!empty($plans) && count($plans)) { $this->save_related($coupon_id, 'coupons_plans', 'plan_id', $plans); }
		
		return TRUE;
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
			if (!empty($item)) {
				$this->db->set(array('coupon_id' => $coupon_id, $field => $item));
				$this->db->insert($table);
			}
		}
		
		return TRUE;
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
	public function get_related($coupon_id = null, $table='', $field='') 
	{
		$this->db->where('coupon_id', $coupon_id);
		$query = $this->db->get($table);
		
		$collection = array();
		
		if ($query->num_rows())
		{
			$items = $query->result_array();
			
			foreach ($items as $item)
			{
				$collection[] = $item[$field];
			}
		}
		
		return $collection;
	}
	
	//--------------------------------------------------------------------
	
}

/* End of file coupon_model.php */
/* Location: ./application/modules/coupons/models/coupon_model.php */
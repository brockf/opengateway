<?php
/**
* Order Authorization Model
*
* Contains all the methods used to save and retrieve order authorization details.
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Order_authorization_model extends Model
{
	function __construct()
	{
		parent::__construct();
	}

	/**
	* Save order authorization details.
	*
	* Save the order authorization number returned from the payment gateway
	*
	* @param int $order_id The order ID
	* @param string $tran_id Transaction ID. Optional
	* @param string $authorization_code Authorization code. Optional
	*
	*/

	function SaveAuthorization($order_id, $tran_id = '', $authorization_code = '', $security_key = '')
	{
		$insert_data = array(
							'order_id' => (!empty($order_id)) ? $order_id : '',
							'tran_id'	=> (!empty($tran_id)) ? $tran_id : '',
							'authorization_code' => (!empty($authorization_code)) ? $authorization_code : '',
							'security_key' => (!empty($security_key)) ? $security_key : ''
							);

		$this->db->insert('order_authorizations', $insert_data);
	}

	/**
	* Get Authorization Details.
	*
	* Gets the authorization details for an order_id
	*
	* @param int $order_id The order ID
	*
	* @return mixed Array containg authorization details
	*/

	function GetAuthorization($order_id)
	{
		$this->db->where('order_id', $order_id);
		$query = $this->db->get('order_authorizations');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			return FALSE;
		}
	}
}
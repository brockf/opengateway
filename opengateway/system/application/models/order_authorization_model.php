<?php
class Order_authorization_model extends Model
{
	function Order_authorization_model()
	{
		parent::Model();
	}
	
	function SaveAuthorization($order_id, $tran_id = '', $authorization_code = '')
	{
		$insert_data = array(
							'order_id' => $order_id,
							'tran_id'	=> $tran_id,
							'authorization_code' => $authorization_code
							);
		
		$this->db->insert('order_authorizations', $insert_data);
	}
	
	function getAuthorization($order_id)
	{
		$this->db->where('order_id', $order_id);
		$query = $this->db->get('order_authorizations');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			die($this->response->Error(4001));
		}
	}
}
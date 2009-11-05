<?php
class Authentication_model extends Model
{
	function Authentication_model()
	{
		parent::Model();
	}
	
	function Authenticate($api_id = '', $secret_key = '')
	{
		// pull the client from the db
		$this->db->where("api_id = '".$api_id."'");
		$this->db->limit(1);
		$query = $this->db->get('clients');
		
		// make sure it's a valid API ID
		if($query->num_rows() === 0) {
			return FALSE;
		}
		// make sure the secret key matches
		else {
			$row = $query->row();
			if($secret_key == $row->secret_key) {
				return TRUE;
			}
			else {
				return FALSE;
			}
		}
	}
}
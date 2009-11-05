<?php
class Authentication_model extends Model
{
	function Authentication_model()
	{
		parent::Model();
	}
	
	function authenticate($api_id = '', $secret_key = '')
	{
		//Pull the client from the db
		$this->db->where('api_id', $api_id);
		$this->db->limit(1);
		$query = $this->db->get('clients');
		
		//Make sure it's a valid API ID
		if($query->num_rows() < 1)
		{
			return FALSE;
		}
		//Make sure the secret key matches
		else
		{
			$row = $query->row();
			if($secret_key == $row->secret_key)
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
	}
}

?>
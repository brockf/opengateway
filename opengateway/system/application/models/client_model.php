<?php
class Client_model extends Model
{
	function Client_model()
	{
		parent::Model();
	}
	
	// Create a new gateway user
	function CreateUser($client_id = FALSE, $request_params = FALSE)
	{
		// Make sure this client is authorized to create a child client
		if($client_id) {
			$client = $this->GetClientDetails($client_id);
			
			
			
			
		}
		
	}
	
	function GetClientDetails($client_id)
	{
		$this->db->where('client_id', $client_id);
		$this->db->limit(1);
		$query = $this->db->get('clients');
		if($query->num_rows() > 0) {
			return $query->row();
		} else {
			return FALSE;
		}
	}
}
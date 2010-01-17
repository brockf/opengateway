<?php

class States_model extends Model
{
	function States_Model()
	{
		parent::Model();
	}
	
	function GetStateByCode($state) 
	{
		$this->db->where('name_short', strtoupper($state));
		$query = $this->db->get('states');
		if($query->num_rows() > 0) {
			return $query->row()->name_short;
		}
		
		return FALSE;
	}
	
	function GetStateByName($state) 
	{
		$this->db->where('name_long', ucwords($state));
		$query = $this->db->get('states');
		if($query->num_rows() > 0) {
			return $query->row()->name_short;
		}
		
		return FALSE;
	}
	
	
}
<?php
class Request_type_model extends Model
{
	function Request_type_model()
	{
		parent::Model();
	}
	
	// Validate the request
	function ValidateRequestType($request_type = FALSE)
	{
		if($request_type) {
			$this->db->where("name = '".$request_type."'");
			$query = $this->db->get('request_types');
			if($query->num_rows() > 0) {
				$row = $query->row();
				return $row->model;
			}
			else {
				return FALSE;
			}
		}
	}
}
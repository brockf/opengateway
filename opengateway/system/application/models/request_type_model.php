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
			$this->db->where('name', $request_type);
			$query = $this->db->get('request_types');
			if($query->num_rows() > 0) {
				$row = $query->row();
				if($row->model == '') {
					return TRUE;
				} else {
					return $row->model;
				}
			}else {
				return FALSE;
			}
		}
	}
	
	function GetRequiredFields($request_type)
	{
		$this->db->select('required_fields.field_name');
		$this->db->join('required_fields', 'required_fields.request_type_id = request_types.request_type_id', 'inner');
		$this->db->where('request_types.name', $request_type);
		$query = $this->db->get('request_types');
		if($query->num_rows() > 0) {
			return $query->result_array();	
		}else {
			return FALSE;
		}
	}
}
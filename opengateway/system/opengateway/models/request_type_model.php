<?php
/**
* Request Type Model
*
* Contains all the methods used to validate and get required fields for request types.
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Request_type_model extends Model
{
	function __construct()
	{
		parent::__construct();
	}

	/**
	* Validate Request Type.
	*
	* Validates a request type and returns the request_type model to be used
	*
	* @param string $request_type The request type name
	*
	* @return string The model
	*/

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

	/**
	* Get Required Fields.
	*
	* Returns the required fields for a request_type.
	*
	* @param string $request_type The request type name
	*
	* @return mixed Array containing the required field.
	*/

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
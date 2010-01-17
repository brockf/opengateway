<?php
/**
* States Model 
*
* Contains all the methods used to get State/Province details.
*
* @version 1.0
* @author David Ryan
* @package OpenGateway

*/

class States_model extends Model
{
	function States_Model()
	{
		parent::Model();
	}
	
	/**
	* Get State Name by Code.
	*
	* Validate the 2-letter State abbreviation
	*
	* @param string $state The state abbreviation
	*
	* @return string The abbreviation
	*/
	
	function GetStateByCode($state) 
	{
		$this->db->where('name_short', strtoupper($state));
		$query = $this->db->get('states');
		if($query->num_rows() > 0) {
			return $query->row()->name_short;
		}
		
		return FALSE;
	}
	
	/**
	* Get State Code by Name.
	*
	* Returns the State abbreviation based on state name
	*
	* @param string $state The state name
	*
	* @return string The abbreviation
	*/
	
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
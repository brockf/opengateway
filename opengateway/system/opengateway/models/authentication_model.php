<?php
/**
* Authentication Model
*
* Contains the methods used to authenticate clients and allow access to the API.
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Authentication_model extends Model
{
	function __construct()
	{
		parent::__construct();
	}

	/**
	* Authenticate the client.
	*
	* Authenticates the client using the api_id and secret_key.  Will only authenticate if the Client is not suspended
	* or deleted.  Returns object containg client details on success or FALSE on authentication failure.
	*
	* @param string $api_id The API identifier used by the client
	* @param string $secret_key
	*
	* @return mixed Object containg all client details on success of FALSE on failure.
	*/

	function Authenticate($api_id = '', $secret_key = '')
	{
		// pull the client from the db
		$this->db->where('api_id', (string)$api_id);
		$this->db->where('suspended', 0);
		$this->db->where('deleted', 0);
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
				return $row;
			}
			else {
				return FALSE;
			}
		}
	}
}
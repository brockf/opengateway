<?php

class Log_model extends Model
{
	function Log_Model()
	{
		parent::Model();
	}
	
	function LogRequest($request = FALSE)
	{
		if($request) {
			$timestamp = date('Y-m-d H:i:s');
			$insert_data = array('timestamp' 	=> $timestamp,
								 'remote_ip' 	=> $_SERVER['REMOTE_ADDR'],
								 'request' 		=> $request
								 );
			
			$this->db->insert('request_log', $insert_data);
		} else {
			return FALSE;
		}
	}
	
	function LogApiResponse($gateway_name, $insert_data)
	{
		$this->db->insert($gateway_name.'_log', $insert_data);
	}
	
	function LogError($error = FALSE)
	{
		if($error) {
			$timestamp = date('Y-m-d H:i:s');
			$insert_data = array('timestamp' 	=> $timestamp,
								 'remote_ip' 	=> $_SERVER['REMOTE_ADDR'],
								 'error' 		=> $error
								 );
			
			$this->db->insert('error_log', $insert_data);
		} else {
			return FALSE;
		}
	}
}
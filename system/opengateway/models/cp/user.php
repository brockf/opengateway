<?php

class User extends Model {
	var $active_user;

    function __construct() {
        parent::__construct();

        // check for session
        if ($this->session->userdata('client_id') != '') {
        	$this->SetActive($this->session->userdata('client_id'));
        }
    }

    function Login ($username, $password) {
		$this->db->where('username',$username);
		$this->db->where('suspended','0');
		$this->db->where('deleted','0');
		$query = $this->db->get('clients');

		if ($query->num_rows() > 0) {
			$client = $query->row_array();
			
			if ($client['password'] != md5($password) && ($client['password'] != md5($password . $client['email']))) {
				return FALSE;
			}
		}
		else {
			return false;
		}

    	$this->session->set_userdata('client_id',$client['client_id']);
    	$this->session->set_userdata('login_time',now());

		$this->SetActive($client['client_id']);

		return true;
    }

    function Logout () {
    	$this->session->unset_userdata('client_id','login_time');

    	return true;
    }

    function SetActive ($client_id) {
    	$CI =& get_instance();
    	$CI->load->model('client_model');

    	if (!$client = $CI->client_model->GetClientDetails($client_id)) {
    		return false;
    	}

    	$this->active_user = $client;

    	return true;
    }

    function LoggedIn () {
    	if (empty($this->active_user)) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    function Get ($parameter = false) {
    	if ($parameter) {
    		return $this->active_user->$parameter;
    	}
    	else {
    		return $this->active_user;
    	}
    }
}
<?php

class Notices extends Model {
	var $CI;

    function Notices() {
        parent::Model();
        
        $this->CI =& get_instance();
    }
    
    function SetError($message) {
    	$errors = $this->GetErrors(false);
    	
    	$errors[] = $message;
    	
    	$errors = serialize($errors);
    	
    	$this->CI->session->set_flashdata('errors', $errors);
    	
    	return true;
    }

	function GetErrors ($clear = true) {
		$errors = $this->CI->session->flashdata('errors');
		
		if (!empty($errors) and is_array(unserialize($errors))) {
			if ($clear == true) {
				$this->CI->session->set_flashdata('errors','');
			}
			return unserialize($errors);
		}
		else {
			return array();
		}
	}
	
	function SetNotice($message) {
    	$notices = $this->GetNotices(false);
    	
    	$notices[] = $message;
    	
    	$notices = serialize($notices);
    	
    	$this->CI->session->set_flashdata('notices', $notices);
    	
    	return true;
    }

	function GetNotices ($clear = true) {
		$notices = $this->CI->session->flashdata('notices');
		
		if (!empty($notices) and is_array(unserialize($notices))) {
			if ($clear == true) {
				$this->CI->session->set_flashdata('notices','');
			}
			return unserialize($notices);
		}
		else {
			return array();
		}
	}
}
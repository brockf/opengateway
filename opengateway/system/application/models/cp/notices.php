<?php

class Notices extends Model {
    function Notices() {
        parent::Model();
    }
    
    function SetError($message) {    	
    	$errors = $this->GetErrors(false);
    	
    	$errors[] = $message;
    	
    	$errors = serialize($errors);
    	
    	$this->session->set_userdata(array('errors' => $errors));
    	
    	return true;
    }

	function GetErrors ($clear = true) {
		$errors = $this->session->userdata('errors');
		
		if (!empty($errors) and is_array(unserialize($errors))) {
			if ($clear == true) {
				$this->session->set_userdata(array('errors' => ''));
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
    	
    	$this->session->set_userdata(array('notices' => $notices));
    	
    	return true;
    }

	function GetNotices ($clear = true) {
		$notices = $this->session->userdata('notices');
		
		if (!empty($notices) and is_array(unserialize($notices))) {
			if ($clear == true) {
				$this->session->set_userdata(array('notices' => ''));
			}
			return unserialize($notices);
		}
		else {
			return array();
		}
	}
}
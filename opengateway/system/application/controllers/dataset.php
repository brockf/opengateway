<?php

class Dataset extends Controller {

	function Transactions()
	{
		parent::Controller();
	}
	
	function prep_filters()
	{	
		$serialize = array();
	
		$values = explode('&',$this->input->post('filters'));
		foreach ($values as $value) {
			list($name,$value) = explode('=',$value);
			
			if (!empty($value) and $value != 'filter+results') {
				$serialize[$name] = $value;
			}	
		}
		
		$this->load->library('asciihex');
	
		echo $this->asciihex->AsciiToHex(base64_encode(serialize($serialize)));
	}
}
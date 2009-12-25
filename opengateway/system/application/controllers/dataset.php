<?php
/**
* Dataset Controller 
*
* Handles miscellaneous dataset features
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Dataset extends Controller {

	function Transactions()
	{
		parent::Controller();
	}
	
	/**
	* Prep Filters
	*
	* Handles AJAX posts of dataset filters and returns an encoded array
	*
	* @return string Encoded string.
	*/
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
<?php
class MY_Input extends CI_Input {

    function MY_Input()
    {
        parent::CI_Input();
    }
    
// --------------------------------------------------------------------

	/**
	* Clean Keys
	*
	* This is a helper function. To prevent malicious users
	* from trying to exploit keys we make sure that keys are
	* only named with alpha-numeric text and a few other items.
	*
	* @access	private
	* @param	string
	* @return	string
	*/
	function _clean_input_keys($str)
	{
		if ( ! preg_match("/^[a-z0-9:_\/-?]+$/i", $str))
		{
			exit('Disallowed Key Characters: '.$str);
		}

		return $str;
	}
    
}